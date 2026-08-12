# Lesson 04: Authentication — Proving and Remembering Who Someone Is

## What you will be able to do

By the end of this lesson, you can:

- describe the identity DALT stores in the session, and why it refuses anything less;
- explain why the session ID is rotated *before* authenticated state is written;
- trace a login attempt from form submission to redirect;
- explain how a rejected login reaches the form again with errors and old input;
- describe how an intended destination is remembered without creating an open redirect;
- read authentication state through `Authenticator` instead of `$_SESSION`.

## Recommended prerequisites

- [Lesson 01: Request Lifecycle](../01-request-lifecycle/README.md) — sessions and flash data
- [Lesson 03: Middleware](../03-middleware/README.md) — the `auth` and `guest` guards

The authentication example is optional. Install it with:

```bash
php artisan example:install auth
```

## Predict before reading the source

| Situation | What happens |
|---|---|
| `login(['email' => 'a@b.com'])` with no ID | throws `InvalidArgumentException` |
| Correct password, user row has `id = 0` | login fails; 0 is not a valid identity |
| Login succeeds | session ID is rotated, then identity is stored |
| Login fails validation | redirect back with `errors` and `old` flashed |
| Guest visits `/dashboard`, then logs in | returns to `/dashboard`, not `/` |
| Guest POSTs to `/dashboard/update`, then logs in | returns to `/`; only GET/HEAD are remembered |

## 1. Identity is a validated shape, not whatever the database returned

```php
private const USER_KEY = 'user';

public function login(array $user): void
{
    $identity = $this->identityFrom($user);

    if ($identity === null) {
        throw new InvalidArgumentException(
            'An authenticated user requires a positive integer ID and non-empty email.',
        );
    }

    Session::regenerate();
    Session::put(self::USER_KEY, $identity);
}
```

`identityFrom()` accepts only a positive integer ID and a non-empty email, returning `['id' => int, 'email' => string]`. A numeric string ID such as `"42"` is converted to `42`; `0`, `-1`, a missing ID, or a blank email all produce `null`.

Two consequences:

- **The session never holds a partial user.** Every later read can assume both fields exist.
- **The password hash is never stored in the session**, because `identityFrom()` copies only the two fields it validated.

Storing an email alone is not enough. Emails change and are not a stable key; the integer ID is the identity.

## 2. Rotate the session ID before recording privilege

The ordering in `login()` is deliberate:

```php
// Rotate before privilege is recorded so the old session never
// contains authenticated state.
Session::regenerate();
Session::put(self::USER_KEY, $identity);
```

This defends against **session fixation**. An attacker who plants a known session ID in a victim's browser wants that ID to become an authenticated session. Rotating first means the ID the attacker knows is abandoned while still anonymous, and the identity is written only into the fresh one.

Reverse these two lines and the code still "works" in every manual test — the user logs in and sees the dashboard. That is what makes the bug dangerous: only the attacker notices the difference.

## 3. `attempt()` verifies credentials

```php
public function attempt(string $email, string $password): bool
{
    $user = $this->database()->query(
        'SELECT id, email, password FROM users WHERE email = :email',
        ['email' => $email],
    )->find();

    if (!is_array($user) || !$this->hasValidCredentials($user, $password)) {
        return false;
    }

    $this->login($user);

    return true;
}
```

Details worth naming:

- **The column list is explicit**, not `SELECT *`. The query returns exactly what authentication needs.
- **The email is a bound parameter**, so a crafted email cannot alter the query.
- **`hasValidCredentials()` requires three things**: a string hash, a valid identity, and `password_verify($password, $hash)`.
- **One boolean is returned.** The caller learns whether authentication succeeded, never *why*. "No such email" and "wrong password" are indistinguishable to an attacker enumerating accounts.

## 4. Registration hashes, then defers to the login form

From `.dalt/stubs/auth/Http/controllers/registration/store.php`:

```php
$errors = [];
if (!Validator::string($name)) $errors['name'] = 'Name is required';
if (!Validator::email($email)) $errors['email'] = 'Valid email required';
if (!Validator::string($password, 8, 72)) $errors['password'] = 'Password must be between 8 and 72 characters';
if ($password !== $confirm) $errors['password_confirmation'] = 'Passwords do not match';

if (!empty($errors)) {
    ValidationException::throw($errors, ['name' => $name, 'email' => $email]);
}
```

Then:

```php
$hashed = password_hash($password, PASSWORD_DEFAULT);
```

`PASSWORD_DEFAULT` follows PHP's current recommendation rather than pinning one algorithm forever. The 72-character maximum is not arbitrary — bcrypt silently ignores bytes past 72, so accepting longer input would mean the tail of a long password is never checked.

Note what the old input excludes: `['name' => $name, 'email' => $email]`. **A password is never flashed back.**

## 5. Failure travels through one exception boundary

`ValidationException::throw()` does not render a view. It throws, and `public/index.php` catches it:

```php
try {
    $response = $router->route($uri, $method, $request);
} catch (ValidationException $exception) {
    Session::flash('errors', $exception->errors);
    Session::flash('old', $exception->old);
    $response = redirect($router->previousUrl());
}
```

So every validation failure follows the same path: flash `errors`, flash `old`, redirect back. The form re-renders on the *next* request and reads the flashed data — which is exactly the request-start flash aging from Lesson 01.

The exception constructor is strict: an empty error array, a non-string field name, or a non-string message each throw `InvalidArgumentException`. A malformed error bag fails at the throw site rather than producing a blank form later.

## 6. Login returns the user where they were going

```php
$auth = new Authenticator();
if ($auth->attempt($email, $password)) {
    return redirect($auth->intended());
}

ValidationException::throw(['email' => 'Invalid credentials'], ['email' => $email]);
```

The destination was recorded earlier by the `auth` middleware:

```php
public function rememberIntended(Request $request): void
{
    if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
        return;
    }

    $target = $request->server('REQUEST_URI');

    if (is_string($target) && self::isSafeLocalPath($target)) {
        Session::put(self::INTENDED_KEY, $target);
    }
}
```

Two guards matter:

- **Only GET and HEAD are remembered.** Replaying a POST after login would repeat a state-changing action the user never re-confirmed.
- **Only safe local paths are stored.** `isSafeLocalPath()` rejects anything with a scheme, host, user, password, port, or fragment, anything starting with `//`, backslashes, and control characters. Without this, `?intended=https://evil.example` would turn your login form into an **open redirect** that lends your domain's credibility to an attacker's page.

`intended()` reads the value, forgets it, and falls back to `/`. The fallback is validated too — `intended('https://evil.test/')` throws `InvalidArgumentException`.

## 7. Read state through the Authenticator

```php
$auth = new Authenticator();

$auth->check();   // bool: is someone logged in?
$auth->guest();   // bool: the negation
$auth->user();    // ['id' => int, 'email' => string] or null
$auth->id();      // int or null
```

Prefer these over `$_SESSION['user']`. `user()` re-validates the stored shape on every read, so a session corrupted by other code returns `null` rather than a half-user that fails deep inside a controller.

## 8. Logout destroys the session

```php
public function logout(): void
{
    Session::destroy();
}
```

`Session::destroy()` clears `$_SESSION`, destroys the native session, and expires the cookie with the same path, domain, secure, httponly, and samesite attributes it was set with. Clearing `$_SESSION` alone would leave a live session file and a valid cookie.

## The installed routes

```php
$router->get('/register', 'registration/create.php')->only('guest');
$router->post('/register', 'registration/store.php')->only(['guest', 'csrf']);
$router->get('/login', 'session/create.php')->only('guest');
$router->post('/session', 'session/store.php')->only(['guest', 'csrf']);
$router->delete('/session', 'session/destroy.php')->only(['auth', 'csrf']);
```

Logging in creates a session resource, so it is a `POST /session`. Logging out deletes it, so it is a `DELETE /session` — reached from an HTML form through the `_method` override from Lesson 02. Every state-changing route carries `csrf`, and each carries `guest` or `auth` so it cannot be reached from the wrong state.

## Debugging checklist

- **`InvalidArgumentException: An authenticated user requires...`:** `login()` received a row without a positive integer ID or with a blank email.
- **Login reports success but the next request is anonymous:** something wrote to the session after `regenerate()` rotated it, or the session cookie is not being returned.
- **Correct password always rejected:** confirm the stored value is a hash, not plain text, and that registration did not truncate past 72 bytes.
- **Blank form after a failed submit:** the view is not reading the flashed `errors` and `old`.
- **Always redirected to `/` after login:** the intended URL was a POST, or failed `isSafeLocalPath()`.
- **Redirect loop between `/login` and a guarded page:** a route carries `auth` when it should carry `guest`.

## Trace exercise

Read in order and write down what each contributes:

1. `.dalt/stubs/auth/routes/auth.php`
2. `.dalt/stubs/auth/Http/controllers/session/store.php`
3. `framework/Core/Authenticator.php`
4. `framework/Core/Session.php` — `regenerate()` and `destroy()`
5. `framework/Core/ValidationException.php`
6. `public/index.php` — the `ValidationException` catch

Then run:

```bash
composer test -- --filter='Auth|Validation|Session'
```

## Checkpoint

Close the source files and answer from memory:

1. Name the two fields stored as identity and give three inputs `identityFrom()` rejects.
2. Explain what breaks if `Session::regenerate()` runs *after* `Session::put()`, and why manual testing will not reveal it.
3. `attempt()` returns only `true` or `false`. Explain what an attacker learns from that, and what they would learn from distinct messages.
4. Trace a failed registration from `ValidationException::throw()` to the re-rendered form, naming every step.
5. Explain why only GET and HEAD requests are remembered as an intended destination.
6. Explain what an open redirect is and which method prevents it here.

## Challenge: Broken Authentication

```bash
php artisan challenge:start broken-auth
php artisan migrate
php artisan challenge:verify
php artisan challenge:stop
```

## Laravel bridge

Compared against Laravel 13.x ([laravel.com/docs/13.x/authentication](https://laravel.com/docs/13.x/authentication), consulted 2026-08-12).

Laravel's controller does the same four steps:

```php
if (Auth::attempt($credentials)) {
    $request->session()->regenerate();

    return redirect()->intended('dashboard');
}

return back()->withErrors([...])->onlyInput('email');
```

| Laravel 13.x | DALT |
|---|---|
| `Auth::attempt($credentials)` | `(new Authenticator())->attempt($email, $password)` |
| `session()->regenerate()` called by the controller after attempt | `Session::regenerate()` called inside `login()` before storing identity |
| `redirect()->intended('dashboard')` | `redirect($auth->intended())` |
| `back()->withErrors()->onlyInput('email')` | `ValidationException::throw($errors, $old)` caught in the front controller |
| guards, providers, `Authenticatable`, hashing driver config | one `Authenticator` on one `users` table |

The one real divergence is worth noticing: Laravel leaves session regeneration to the controller, so forgetting that line is a real application bug. DALT moves it inside `login()`, where it cannot be forgotten. Same defence, different place to put the responsibility.

## Next steps

- **Lesson 05: Database** — the query layer `attempt()` depends on
- **Challenge: Broken Authentication** — find a login flow that authenticates the wrong people
