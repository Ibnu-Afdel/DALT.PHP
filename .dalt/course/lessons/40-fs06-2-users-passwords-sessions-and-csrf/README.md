# FS06.2 — Users, passwords, sessions and CSRF

Lesson ID: FS06.2  
Title: Users, passwords, sessions and CSRF  
Part: 06 — Testing, users and authentication  
Order: 2  
Status: Published  
Estimated effort: 120–150 minutes  
Difficulty: Integration  
Prerequisites: FS06.1 — Backend API behavior tests  
Project milestone: B06 — Multi-user protected system  
Primary source dossier: `FSO_PART_04.md`  
Last reviewed: 2026-08-14

## Why this matters

Until now every request to your API was anonymous and equal. Adding users changes what a request
*is*: it arrives carrying a claim about who is making it, and the server has to decide whether
to believe that claim. Getting this wrong is not like getting a filter wrong. A weak password
store leaks credentials that people reuse elsewhere; a session that never rotates lets an
attacker choose a victim's identifier; a mutation with no CSRF proof lets any website on the
internet act as your logged-in user.

The reassuring part is that none of the mechanisms are complicated, and PHP supplies most of
them. The dangerous part is that every one of them fails silently when done wrong. A login form
that stores plaintext passwords works perfectly. A CSRF check you forgot to apply produces no
error. Nothing on screen distinguishes a secure implementation from an insecure one, which is
exactly why this lesson insists you observe each mechanism working rather than assume it.

## Before you start

Complete FS06.1, and read the framework's existing implementation rather than inventing your own:

```sh
less framework/Core/Authenticator.php        # attempt, login, user, id, check, logout
less framework/Core/Session.php              # regenerate, put, get, forget, destroy
less framework/Core/Middleware/Csrf.php      # the actual token comparison
less framework/Core/functions.php            # csrf_token(), csrf_field(), authorize()
```

DALT already gives you a working authenticator over a `users` table with `id`, `email`, and
`password` columns. Your job in this lesson is not to reimplement it — it is to understand what
each part guarantees, expose it as JSON endpoints your React client can use, and prove the
guarantees hold.

Going deeper in DALT Core — optional:

- [Authentication](/learn/lessons/04-authentication) and [middleware](/learn/lessons/03-middleware) are optional reference; they are not gates for this track.

## By the end

- store password verifiers rather than passwords, and explain the difference;
- establish and destroy identity through the session, with rotation at login;
- describe what an HttpOnly session cookie does and does not protect;
- explain why cookie authentication requires CSRF proof, and implement it for JSON;
- return 401 and 419 as distinct, documented outcomes;
- prove each of these with a test rather than by inspection.

## Predict before reading

1. Two users choose the same password. Should their stored values match?
2. Why is `SELECT * FROM users WHERE email = ? AND password = ?` wrong even with a hash?
3. If a hostile site submits a form to your API, whose cookies does the browser attach?
4. Why does rotating the session ID at login matter if the old session was anonymous?

## Mental model

~~~text
password → password_hash → users.password
login request → password_verify → rotate session ID → server session { user }
browser ← opaque HttpOnly cookie ────────────────────────────────┘
unsafe request → cookie identity + CSRF token → allowed mutation
logout → destroy server session + expire cookie → old cookie is useless
~~~

The password is evidence presented once; it is not a reusable database value. The cookie is a
lookup key, not an identity document — it says "session 7f3a", and the server decides what that
session means. The session record on the server holds the authenticated identity. And the CSRF
token proves a request was deliberately made *by your application*, rather than by a cross-site
form that happens to travel with the browser's cookie.

Those last two are the pair people conflate. Authentication answers "who is this?"; CSRF answers
"did they mean to do this?" A request can be perfectly authenticated and still be something the
user never intended.

## 1. Store verifiers, not secrets

Add a `users` migration. The column width matters more than it looks:

```sql
CREATE TABLE users (
  id BIGSERIAL PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

`VARCHAR(255)`, not `VARCHAR(60)`. Today's `PASSWORD_DEFAULT` produces a 60-character bcrypt
hash, so 60 appears to work — and then PHP's default algorithm changes, the hash gets longer,
and PostgreSQL silently refuses or truncates. A truncated hash never verifies, so every user is
locked out at once, and the cause is a column width nobody has looked at in two years. The PHP
manual recommends 255 for exactly this reason.

Hash on the way in, and verify on the way out:

```php
// Registration, or whatever your documented user-creation path is.
$hash = password_hash($password, PASSWORD_DEFAULT);

$database->query(
    'INSERT INTO users (email, password) VALUES (?, ?)',
    [mb_strtolower(trim($email)), $hash],
);
```

```php
// Login. This is what Authenticator::hasValidCredentials already does.
return is_string($hash) && password_verify($password, $hash);
```

Prediction 1's answer is no — and prediction 2 follows from it. `password_hash` generates a
random salt per call and embeds it in the output, so two users with the password `hunter2` have
completely different stored strings:

```text
$2y$12$LQv3c1yqBWVHxkd0LHAkCO...   ← same password
$2y$12$e0MYzXyjpJS7Pd0RVvHwHe...   ← different salt, different hash
```

That is what makes `WHERE email = ? AND password = ?` wrong even when you remember to hash: you
cannot hash the submitted password and compare strings, because the salts differ. You must fetch
the stored hash and let `password_verify` extract the salt and cost from it. Comparing hashes
with `==` is also how timing leaks happen; `password_verify` compares in constant time.

One thing `password_hash` gives you for free is worth knowing about: the cost factor is embedded
in the output, so `password_needs_rehash($hash, PASSWORD_DEFAULT)` can tell you at login time
that a stored hash was made with weaker settings. Re-hashing there — while you legitimately hold
the plaintext for one moment — is how a system upgrades its password storage without asking
anyone to reset anything. You do not need it today; know it exists.

Never return the `password` column from a response, log it, put it in an exception message, or
accept a client-supplied hash as though it were a password. The FS05.1 mapper is what enforces
the first of those — a `userResource()` that names `id` and `email` cannot leak a hash even if
someone writes `SELECT *`.

Finally, keep the database uniqueness constraint on `email` even though your handler checks
availability first. Two concurrent registrations can both pass the handler check and both
proceed; only the constraint stops the second one. And normalize the email before storing it, or
`Alice@example.com` and `alice@example.com` become two accounts.

Give unknown-user and wrong-password the same public failure:

```php
if (!$auth->attempt($email, $password)) {
    return Response::json(['error' => [
        'code' => 'invalid_credentials',
        'message' => 'Those credentials do not match our records.',
    ]], 401);
}
```

Distinguishing them turns login into an account-discovery endpoint. Your tests may know which
case occurred; the response need not say.

## 2. Establish and destroy identity deliberately

Three endpoints are enough: `POST /api/login`, `POST /api/logout`, and `GET /api/me`.

```php
// POST /api/login
$auth = App::resolve(Authenticator::class);

if (!$auth->attempt($email, $password)) {
    return Response::json(['error' => [
        'code' => 'invalid_credentials',
        'message' => 'Those credentials do not match our records.',
    ]], 401);
}

return Response::json(['data' => ['id' => (string) $auth->id(), 'email' => $email]]);
```

```php
// GET /api/me — the endpoint the React client calls on load to find out who it is.
$user = $auth->user();

return $user === null
    ? Response::json(['error' => ['code' => 'unauthenticated', 'message' => 'Not signed in.']], 401)
    : Response::json(['data' => ['id' => (string) $user['id'], 'email' => $user['email']]]);
```

Read `Authenticator::login()` and notice the ordering, because it is the answer to prediction 4:

```php
// framework/Core/Authenticator.php
Session::regenerate();
Session::put(self::USER_KEY, $identity);
```

Rotation happens *before* the identity is recorded, so the old session never holds authenticated
state. This defends against **session fixation**: an attacker who can persuade a victim to start
browsing with a session id the attacker already knows — through a link, a subdomain, or an
injected cookie — would otherwise still know that id after the victim logs in, and would inherit
the login. Rotating means the id the attacker planted is discarded at exactly the moment it
would become valuable. The anonymous session's harmlessness is the point: it is only worth
stealing after the login, and after the login it no longer exists.

Logout must destroy server state, not just client state:

```php
$auth->logout();   // Session::destroy()
```

Deleting a React variable is not logout. Neither is deleting the cookie client-side: the session
record still exists on the server, and anyone holding the old cookie value can still use it.

Cookie flags express transport boundaries and are worth stating precisely, because each is
narrower than people assume:

| Flag | Protects against | Does not protect against |
|---|---|---|
| `HttpOnly` | JavaScript reading the cookie, so an XSS cannot exfiltrate it | XSS acting *as* the user in the page |
| `Secure` | The cookie travelling over plain HTTP | Anything once HTTPS is established |
| `SameSite=Lax` | Most cross-site sends, including hostile form POSTs | Same-site attacks, and it is not a CSRF token |

They complement CSRF protection, HTTPS, and careful output escaping; they replace none of them.
Session lifetime is an expiry policy, not a promise that a server-side file vanishes at a
precise minute.

## 3. CSRF follows cookie authentication

A browser attaches cookies for the destination origin automatically, whoever caused the request.
That is prediction 3's answer, and it is the entire vulnerability: a form on `evil.example` that
posts to your API travels with your user's session cookie, and your server sees a perfectly
authenticated request. Same-origin policy may stop the attacker reading the response, but the
state change has already happened — and for `DELETE /api/issues/42`, not reading the response
costs them nothing.

The defence is an unpredictable token tied to the session, required on every unsafe method. Read
what DALT's middleware actually does:

```php
// framework/Core/Middleware/Csrf.php
private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

$sessionToken = $_SESSION['_csrf'] ?? null;
$requestToken = $request->input('_token') ?? $request->server('HTTP_X_CSRF_TOKEN');

if (!is_string($sessionToken) || $sessionToken === ''
    || !is_string($requestToken) || $requestToken === ''
    || !hash_equals($sessionToken, $requestToken)) {
    return Response::text('CSRF token mismatch', 419);
}
```

Three details in that block are load-bearing for your JSON API.

Safe methods are skipped, because GET must not change state — if one of your GET routes does
change state, CSRF is not the bug to fix first.

`$request->input('_token')` reads `$_POST`, so an HTML form supplies `_token` via `csrf_field()`.
A JSON client has no `$_POST`, and must therefore use the `X-CSRF-Token` header — which is why
the middleware checks `HTTP_X_CSRF_TOKEN` as well. Your React client needs the token from
somewhere: either rendered into the application page, or served by a deliberate bootstrap
endpoint such as `GET /api/csrf-token`, which is safe to expose because it is scoped to the
caller's own session.

And `hash_equals` is a constant-time comparison. `===` on secrets leaks length and prefix
information through timing.

Attach it per route, and notice what is protected and what is not:

```php
// routes/routes.php
$router->post('/api/issues', 'api/issues/store.php')->only('csrf');
$router->patch('/api/issues/{id}', 'api/issues/update.php')->only('csrf');
$router->delete('/api/issues/{id}', 'api/issues/destroy.php')->only('csrf');
```

One thing to decide before your client hits it: a failed check returns
`Response::text('CSRF token mismatch', 419)` — **plain text**, not your JSON envelope. That is
the same content-type mismatch you met with `abort()` in FS05.1, and FS04.3's parser will fall
back to a generic message. Either accept it and document 419 as a non-JSON outcome, or wrap the
middleware for your API routes so it emits the envelope. Choose deliberately; do not discover it
in Part 07.

CSRF does not authenticate a request, and authentication does not prove intent. A missing token
must fail before the mutation runs, which is why the middleware sits in front of the handler
rather than inside it.

## 4. Prove each mechanism

None of this is visible on screen, so tests are the only honest evidence. Each of these has a
plausible fake it rules out:

```php
test('the stored password is not the submitted password', function () {
    $user = createUser('alice@example.com', 'correct horse battery staple');

    expect($user['password'])->not->toBe('correct horse battery staple');
    expect(password_verify('correct horse battery staple', $user['password']))->toBeTrue();
    expect(password_verify('wrong password', $user['password']))->toBeFalse();
});

test('two users with the same password store different hashes', function () {
    // Catches a hand-rolled unsalted hash, which passes the test above.
    expect(createUser('a@example.com', 'same')['password'])
        ->not->toBe(createUser('b@example.com', 'same')['password']);
});

test('an unsafe request without a CSRF token is refused and changes nothing', function () {
    $before = countRows('issues');

    $response = api('POST', '/api/issues', ['title' => 'No token']);

    expect($response->statusCode)->toBe(419);
    expect(countRows('issues'))->toBe($before);   // the assertion people omit
});

test('the same request with a valid token succeeds', function () {
    // Without this, a route that rejects everything would pass the test above.
    $response = api('POST', '/api/issues', ['title' => 'With token'], token: csrfToken());

    expect($response->statusCode)->toBe(201);
});
```

The last pair matters as much as any of them. A broken route that 419s unconditionally satisfies
the negative test perfectly. Only the positive test distinguishes "CSRF is enforced" from
"mutations are broken", and this is the shape of every security test you will write: prove the
door is locked, then prove your key opens it.

Also confirm that logout invalidates the server session — send an authenticated `GET /api/me`,
log out, and send the same request again expecting 401. Test the behaviour available to your
harness rather than printing a live session id.

## Try it

Create one user and inspect the row directly: the stored value must not be the submitted
password, and it must start with an algorithm marker such as `$2y$`. Create a second user with
the identical password and confirm the two rows differ.

Then, with the browser DevTools Application panel open, log in and look at the session cookie —
check that it is marked HttpOnly, and try to read `document.cookie` in the console to confirm it
is absent. Note the cookie value, log out, and confirm the old value no longer authenticates.

Finally, send a mutation with curl and no token, read the 419, then repeat it with a valid
`X-CSRF-Token` header and watch it succeed.

## Common mistakes

- Sizing the password column at 60 characters because today's hash fits.
- Hashing the submitted password and comparing strings instead of using `password_verify`.
- Comparing tokens or hashes with `===` rather than `hash_equals`.
- Telling the client whether the email or the password was wrong.
- Dropping the unique constraint on email because the handler already checks.
- Storing identity before rotating the session, leaving fixation open.
- Treating a deleted React variable, or a client-side cookie delete, as logout.
- Believing `SameSite=Lax` or `HttpOnly` replaces a CSRF token.
- Applying CSRF middleware to some mutations and forgetting one.
- Testing that a tokenless request is refused, without testing that a valid one succeeds.

## When this goes wrong

Trace one request in order: cookie sent, session found, identity present, CSRF compared, handler
reached. Log safe identifiers and decision names in development — never a password, a hash, or a
raw session id.

A 419 when you expected 401 means the request never reached authentication; CSRF runs first.
A 401 on a request you believe is logged in usually means the cookie was not sent at all, which
in a browser is a `credentials` or origin problem rather than an authentication one — remember
that `localhost` and `127.0.0.1` are different origins and do not share cookies. If login
succeeds but the next request is anonymous, check that the session cookie's path and domain
cover it, and that you are not regenerating the session on every request.

If `password_verify` always returns false for a user you just created, print the stored hash
length. A value shorter than 60 characters means the column truncated it.

## Exercise

**Goal:** Give the API real identity, and prove the protections around it.

**Starting state:** FS06.1 has behaviour tests over a persistent issue API.

**Requirements:** Add a `users` migration with a normalized unique email and a 255-character
password column. Implement `POST /api/login`, `POST /api/logout`, and `GET /api/me` returning the
documented envelopes, with 401 for anonymous or failed authentication. Apply CSRF middleware to
every issue mutation and decide how a 419 is represented to a JSON client. Store only hashes.

**Verification:** Tests covering — a stored hash that is not the password; two identical
passwords producing different hashes; a wrong password rejected with the same message as an
unknown email; `GET /api/me` returning 401 anonymously and the user when signed in; a mutation
without a token returning 419 with no database change; the same mutation with a token
succeeding; and logout making the previous session unusable.

**Mode: tool-run — Pest behavior tests, plus browser and curl evidence for cookie flags.** The
platform does not grade this; your tests and your recorded observations are the evidence.

**Hints:** Get login and `/api/me` working before adding CSRF, so you are debugging one mechanism
at a time. Write the negative CSRF test and the positive one together — neither is meaningful
alone.

## In the project

B06 turns the issue tracker from a shared demo into a system with real users. FS06.3 adds the
question this lesson deliberately leaves out: now that the server knows *who* is asking, what are
they allowed to do? Part 07 puts an authenticated shell and routing on top, and Part 11 revisits
the database side with row-level security.

Expect some FS06.1 tests to fail after this lesson. Anonymous mutations should stop succeeding —
that is the contract recording a purposeful change, and updating those tests deliberately is part
of the work rather than an interruption to it.

## Closed-book checkpoint

1. Why do two users with the same password have different stored values?
2. Why can you not hash a submitted password and compare it to the stored string?
3. What attack does rotating the session id at login prevent, and how?
4. Why does an authenticated request still need CSRF proof?
5. How does a JSON client supply a CSRF token when it has no `$_POST`?
6. What does `HttpOnly` protect against, and what does it not?

## Resources

### Read

- [PHP: `password_hash`](https://www.php.net/manual/en/function.password-hash.php)
- [PHP: `password_verify`](https://www.php.net/manual/en/function.password-verify.php)
- [OWASP: Cross-Site Request Forgery Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)

### Go deeper

- [OWASP: Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- [OWASP: Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [MDN: `Set-Cookie` and SameSite](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie)

## You are done when

- [ ] The `users` table has a normalized unique email and a 255-character password column.
- [ ] A stored value is never the submitted password, and two identical passwords differ.
- [ ] Login uses `password_verify`, and failure reveals nothing about which field was wrong.
- [ ] The session id rotates before identity is recorded.
- [ ] Logout destroys the server session, and the old cookie no longer authenticates.
- [ ] `GET /api/me` returns 401 anonymously and the safe user when signed in.
- [ ] Every unsafe issue route requires CSRF proof, and I checked that none was missed.
- [ ] I decided and documented how a 419 appears to a JSON client.
- [ ] Tests prove both that a tokenless mutation fails and that a valid one succeeds.
- [ ] `php artisan test` passes, including the FS06.1 tests I updated on purpose.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_04.md`
- Official sources: PHP `password_hash` and `password_verify` manual pages; OWASP CSRF, Password Storage and Session Management cheat sheets; MDN `Set-Cookie` reference
- Versions: PHP 8.4; PostgreSQL as configured by the learner
- Consulted: 2026-08-14
- DALT files inspected: `framework/Core/Authenticator.php`; `framework/Core/Session.php`; `framework/Core/Middleware/Csrf.php`; `framework/Core/Middleware/Middleware.php`; `framework/Core/functions.php`
- Curriculum authority: `CURRICULUM.md` §17 FS06.2
- Laravel bridge: Laravel's `Auth`, session guard and `VerifyCsrfToken` middleware perform these same steps; DALT's versions are short enough to read end to end, which is why this lesson reads them instead of describing them.
