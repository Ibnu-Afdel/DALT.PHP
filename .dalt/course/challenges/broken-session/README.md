# Challenge: Broken Session

## Difficulty: Easy

This challenge practices the session boundary in the request lifecycle. The broken file is a current-compatible `framework/Core/Session.php`, so the rest of the framework remains available while you debug two flash-data defects.

## Start the challenge

From the project root, run:

```bash
php artisan challenge:start broken-session
```

The command records the exact files it replaces. Do not copy files manually. When you are finished, `challenge:stop` restores your files and removes challenge-created files.

## Observe the broken behavior

Start the server with `php artisan serve`, then try these paths:

- `/contact/precedence` should display `flash value`, but the broken `Session::get()` returns the persistent value first.
- Submit the contact form with an empty field. The redirect should show validation errors and old input.
- Submit valid contact data. The success message should appear on `/contact/success`, then disappear on the next refresh. The broken request-start aging carries old flash data forward, so it persists.

The form includes a CSRF field because the POST route uses the same `csrf` middleware as the application runtime.

## What the request lifecycle is supposed to do

The front controller starts the native session before dispatch. `Session::start()` then ages flash data once:

```text
request start: old expires, new becomes old, new becomes empty
handler:       flash() writes into new
next request:  new becomes old and is readable
following request: old expires
```

`redirect()` returns a `Response`; it does not terminate PHP. The response travels back through the router and is sent once by `public/index.php`. Therefore flash data cannot depend on skipping an end-of-request cleanup call.

When a key exists in both persistent session data and flash data, `Session::get()` must inspect flash data first. This gives a validation or status message its intended one-request meaning without deleting the persistent value.

## The bugs

### Bug 1: persistent data wins over flash data

`Session::get()` currently checks `$_SESSION[$key]` before calling the flash lookup. The `/contact/precedence` probe writes both values under `probe`, making the wrong precedence visible.

Fix the lookup order while preserving the `exists()`/`has()` distinction and null-safe lookup behavior. Do not replace the current session implementation with the old flat-bag example from historical tutorials.

### Bug 2: old flash data is carried into the next request

`ageFlashData()` should discard the previous `old` bag. It should build the next store from legacy values and the previous `new` bag, then reset `new` to an empty array. Carrying `old` forward makes a one-request flash value live indefinitely.

## Hints

1. Trace `public/index.php` into `Session::start()` before reading the contact controllers.
2. Inspect the two bags under `$_SESSION['_flash']`; `new` is written during the current request and `old` is read during the following request.
3. In `Session::get()`, find the existing `flashValue()` helper and ask which result should win when both sources contain the key.
4. In `ageFlashData()`, compare the values used to construct `old` with the lifecycle diagram above.

## Files to inspect

- `public/index.php` — the one front-controller entry point and final `Response::send()` boundary.
- `framework/Core/Session.php` — the two defects.
- `framework/Core/Response.php` and `framework/Core/functions.php` — response and redirect contracts.
- `app/Http/controllers/contact/` — the challenge's form, redirect, and probe handlers after `challenge:start`.
- `routes/routes.php` — the challenge routes after `challenge:start`.

## Verify your fix

```bash
php artisan challenge:verify
```

Three checks run the `contact/precedence.php` and `contact/success.php` controllers against a seeded session and judge the result — including one that reads the session state left behind, since a stale flash value never shows up in a response body on its own. The rest check structural evidence in `framework/Core/Session.php` directly. Still use the browser/server observations as your own proof; the verifier is a completion signal, not a substitute for reading the trace yourself.

## Stop and restore

```bash
php artisan challenge:stop
```

This restores the exact pre-challenge files, including files that did not exist before the challenge. If you need another attempt without losing your original state, use `php artisan challenge:reset`.

## Transfer exercise

Add a small route that flashes a value, redirects to another page, and displays the value exactly once. Write a request-level test that proves:

1. the value is available immediately after `flash()`;
2. it survives the redirect to the next request;
3. it is absent on the following request; and
4. a flash value wins over a persistent value with the same key.
