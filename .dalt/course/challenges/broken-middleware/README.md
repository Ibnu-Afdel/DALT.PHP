# Challenge: Broken Middleware

**Difficulty:** Intermediate · **Bugs:** 2 · **Lesson:** [03 — Middleware](../../lessons/03-middleware/README.md)

## Start

```bash
php artisan challenge:start broken-middleware
php artisan serve
```

The command backs up your files and copies the broken versions in. When you are done, `php artisan challenge:stop` restores everything — never copy or delete these files by hand.

## What you are looking at

Two routes are protected:

```php
$router->get('/dashboard', 'dashboard/index.php')->only('auth');
$router->post('/dashboard/update', 'dashboard/update.php')->only(['auth', 'csrf']);
```

Both middleware classes run — the application is not crashing. They are simply reaching the wrong conclusions.

## Observe the symptoms first

Do this before opening any source file.

1. Visit `/dashboard`. You are sent to `/login` even after signing in. No session ever satisfies this check.
2. Submit the form on `/dashboard` with its CSRF field intact. It is rejected with **419 CSRF token mismatch** — the token was valid.
3. Now send the same POST with no token at all:

   ```bash
   curl -i -X POST http://localhost:8000/dashboard/update
   ```

   It is **accepted**. That is the more serious of the two problems.

Write down what those three observations imply before continuing.

## Hints

Work down this list only as far as you need.

<details>
<summary>Hint 1 — where to look</summary>

Both defects are in `framework/Core/Middleware/`. Neither is in `Middleware.php`; the pipeline itself is correct.
</details>

<details>
<summary>Hint 2 — narrowing the auth problem</summary>

Search the whole repository for the session key `Auth.php` reads. How many places write it? Compare that with what `Core\Authenticator` uses when someone logs in.
</details>

<details>
<summary>Hint 3 — narrowing the CSRF problem</summary>

Read the condition that produces the 419 response and trace two cases by hand: tokens equal, and request token absent. Which branch does each take, and is that the branch you want?
</details>

<details>
<summary>Hint 4 — the concepts</summary>

- Middleware should ask the component that owns a fact rather than re-deriving it from raw session data.
- A comparison guarding a security decision needs to reject on failure, and must not treat "both sides missing" as agreement. Loose comparison with `==` also leaks timing information.
</details>

## Success criteria

- A signed-in visitor reaches `/dashboard`; a guest is redirected to `/login`.
- A form with a valid token submits successfully.
- A request with a missing, empty, or wrong token is rejected with 419.
- Token comparison is timing-safe.

## Verify

```bash
php artisan challenge:verify
```

Then confirm the behavior yourself — the checks are a completion signal, not proof:

```bash
curl -i -X POST http://localhost:8000/dashboard/update          # expect 419
curl -i http://localhost:8000/dashboard                         # expect redirect for a guest
```

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 03: Middleware** — read this first
- **Next challenge:** Broken Authentication
