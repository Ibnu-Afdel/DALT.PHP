# Lesson 03: Middleware — Wrapping the Handler

## What you will be able to do

By the end of this lesson, you can:

- attach middleware to a route and predict which layer sees the request first;
- explain why middleware surrounds the handler instead of merely preceding it;
- read and write a middleware class against the current interface;
- describe how a middleware key becomes an object;
- explain why CSRF failures return 419 while auth failures redirect;
- diagnose a middleware problem without editing the pipeline itself.

## Recommended prerequisite

Complete [Lesson 02: Routing](../02-routing/README.md) first. Middleware runs only after a route has matched:

```text
Router matches → middleware pipeline → handler → Response travels back out
```

If no route matches, the router calls `abort(404)` and no middleware runs at all. A 404 can never be fixed inside a middleware class.

## Middleware is a wrapper, not a gate

The common mental model is that middleware runs *before* the controller. That is only half of it. Each layer calls the next and receives the response back, so it can act on the way in **and** on the way out:

```text
$router->post('/posts', 'posts/store.php')->only(['auth', 'csrf']);

auth  before
  csrf  before
    handler
  csrf  after
auth  after
```

The first key in the list is the outermost layer. It sees the request first and the response last.

## Predict before reading the source

Given `->only(['auth', 'csrf'])`, predict each outcome:

| Situation | What happens |
|---|---|
| Guest user, valid CSRF token | `auth` redirects to `/login`; `csrf` never runs |
| Logged-in user, missing token | `auth` passes; `csrf` returns 419; handler never runs |
| Logged-in user, valid token | both pass; handler runs; both see the response on the way out |
| `GET` request to a CSRF-protected route | `csrf` skips the check entirely |
| No route matches the URI | nothing above happens; 404 from the route boundary |

The last row is the one learners most often get wrong.

## 1. Registration attaches keys to the matched route

```php
// Single middleware
$router->get('/dashboard', 'dashboard.php')->only('auth');

// Multiple middleware, outermost first
$router->post('/posts', 'posts/store.php')->only(['auth', 'csrf']);
```

`only()` stores the keys on the `Core\Route` object that was registered last. Registering middleware before any route throws a `LogicException`.

## 2. Keys become objects through the container

`Core\Middleware\Middleware` holds the alias map:

```php
public const MAP = [
    'guest' => Guest::class,
    'auth'  => Auth::class,
    'csrf'  => Csrf::class,
];
```

Resolution has three steps, and each one can fail with a distinct message:

1. **Alias lookup, with a fallback.** `$this->aliases[$key] ?? $key` — an unknown key is treated as a class name, so a fully-qualified class works on a route without being added to `MAP`. Only a key that is neither an alias nor an existing class fails, with `No middleware found for '<key>'.`
2. **Contract check.** The class must implement `MiddlewareInterface`, or resolution throws `Middleware '<class>' must implement Core\Middleware\MiddlewareInterface.`
3. **Construction.** The class is built by the container, so a middleware may declare constructor dependencies. A failure here is rethrown as `Unable to construct middleware '<class>': ...` with the original exception attached.

## 3. The pipeline is assembled inside out

```php
foreach (array_reverse($layers) as $key) {
    $middleware = $this->resolve($key);
    $next = $pipeline;
    $pipeline = static fn (Request $request): Response => $middleware->handle($request, $next);
}

return $pipeline($request);
```

The list is reversed so that wrapping ends with the first-declared middleware on the outside. Nothing is executed during assembly; the single `$pipeline($request)` call at the end runs every layer.

## 4. The middleware contract

```php
interface MiddlewareInterface
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response;
}
```

Three consequences follow from this one signature:

- a middleware **must** return a `Response`, so it can never silently fall through;
- calling `$next($request)` continues the pipeline, and *not* calling it stops the request;
- the return value of `$next($request)` is the response from everything further in, so a layer can inspect or replace it before returning.

## 5. The three built-in middleware

### Auth — require a logged-in user

```php
public function handle(Request $request, Closure $next): Response
{
    if ($this->auth->guest()) {
        $this->auth->rememberIntended($request);

        return Response::redirect('/login');
    }

    return $next($request);
}
```

It asks `Core\Authenticator` rather than reading `$_SESSION` directly, and it records the intended destination before redirecting so the user can be returned there after logging in.

### Guest — require *no* logged-in user

```php
public function handle(Request $request, Closure $next): Response
{
    if ($this->auth->check()) {
        return Response::redirect('/');
    }

    return $next($request);
}
```

### Csrf — verify the request originated from your own form

```php
private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

public function handle(Request $request, Closure $next): Response
{
    if (in_array($request->method(), self::SAFE_METHODS, true)) {
        return $next($request);
    }

    $sessionToken = $_SESSION['_csrf'] ?? null;
    $requestToken = $request->input('_token') ?? $request->server('HTTP_X_CSRF_TOKEN');

    if (
        !is_string($sessionToken)
        || $sessionToken === ''
        || !is_string($requestToken)
        || $requestToken === ''
        || !hash_equals($sessionToken, $requestToken)
    ) {
        return Response::text('CSRF token mismatch', 419);
    }

    return $next($request);
}
```

Four details worth naming:

- **Safe methods skip the check.** `GET`, `HEAD`, and `OPTIONS` are not expected to change state.
- **Two token sources.** A form field `_token`, or an `X-CSRF-Token` header for JavaScript requests.
- **`hash_equals()`, not `===`.** A timing-safe comparison, so response time does not leak how much of the token was correct.
- **419, not 403.** 403 means "you are not allowed to do this"; 419 distinguishes "your token was missing or stale", which is usually fixed by reloading the form rather than by gaining permission.

Add the field to every state-changing form:

```php
<form method="POST" action="/posts">
    <?= csrf_field() ?>
</form>
```

`csrf_token()` generates a 32-byte random token on first use and stores it in the session; `csrf_field()` wraps it in a hidden input.

## 6. Writing your own middleware

```php
<?php

declare(strict_types=1);

namespace Core\Middleware;

use Closure;
use Core\Request;
use Core\Response;

final class Admin implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user) || ($user['is_admin'] ?? false) !== true) {
            return Response::text('Forbidden', 403);
        }

        return $next($request);
    }
}
```

Add it to `MAP` as `'admin' => Admin::class` for a short key, or reference the class directly:

```php
$router->get('/admin', 'admin/dashboard.php')->only(['auth', \Core\Middleware\Admin::class]);
```

To transform the response instead of blocking it, call `$next()` first and act on what comes back:

```php
$response = $next($request);

return $response->withHeader('X-Frame-Options', 'DENY');
```

## 7. Order is a security decision

`->only(['auth', 'csrf'])` and `->only(['csrf', 'auth'])` both protect the handler, but they answer differently for a logged-out user posting a stale token: the first redirects to `/login`, the second returns 419. Put `auth` first so an expired session reads as "log in again" rather than as a token error.

## Debugging checklist

- **Middleware never runs:** prove the route matched first. A 404 happens before the pipeline exists.
- **`No middleware found for 'x'.`** the key is not in `MAP` and is not an existing class name.
- **`... must implement Core\Middleware\MiddlewareInterface`:** the class exists but does not implement the interface.
- **`Unable to construct middleware ...`:** a constructor dependency could not be resolved; read the attached previous exception.
- **Infinite redirect loop:** a route required by the redirect target also requires `auth`. Login routes take `guest`, not `auth`.
- **419 on a valid-looking form:** the form is missing `csrf_field()`, or the session was regenerated after the page rendered.
- **A layer's "after" code never runs:** something further in returned without calling `$next()`, which is the intended way to stop a request.

## Trace exercise

Read these files in order and write down what each contributes:

1. `routes/routes.php` — where keys are attached
2. `framework/Core/Route.php` — how keys are stored
3. `framework/Core/Router.php` — where the pipeline is invoked
4. `framework/Core/Middleware/Middleware.php` — assembly and resolution
5. `framework/Core/Middleware/MiddlewareInterface.php` — the contract
6. `framework/Core/Middleware/Csrf.php` — a layer that can stop the request

Then run:

```bash
composer test -- --filter='Middleware|Csrf'
```

## Checkpoint

Close the source files and answer from memory:

1. `->only(['a', 'b'])` is declared. Write the full order of "before" and "after" steps around the handler.
2. Explain why `Middleware::run()` reverses the list before wrapping.
3. A middleware returns a `Response` without calling `$next()`. Describe exactly which code does and does not run.
4. Explain why CSRF answers 419 rather than 403, and what a user should do about each.
5. A route uses a middleware class that is not in `MAP`. State the two conditions under which this still works.
6. Explain why `hash_equals()` is used instead of `===`.

## Challenge: Broken Middleware

```bash
php artisan challenge:start broken-middleware
php artisan challenge:verify
php artisan challenge:stop
```

## Laravel bridge

Compared against Laravel 13.x ([laravel.com/docs/13.x/middleware](https://laravel.com/docs/13.x/middleware), consulted 2026-08-12).

Laravel uses the same onion: `handle($request, Closure $next)`, returning `$next($request)` to continue.

| Laravel 13.x | DALT |
|---|---|
| aliases registered in `bootstrap/app.php` via `$middleware->alias([...])` | the `Middleware::MAP` constant |
| global stack via `$middleware->append()` / `use()` | no global stack; attach per route |
| middleware groups (`web`, `api`) | no groups; list the keys on each route |
| terminable middleware (`terminate()` after the response is sent) | not supported; the outward path ends at the front controller |
| CSRF via `VerifyCsrfToken`, throwing `TokenMismatchException` → 419 | `Csrf` returns a 419 `Response` directly |

DALT keeps the mechanism and omits the configuration surface. Both frameworks answer 419 for a token mismatch, so the habit transfers.

## Next steps

- **Lesson 04: Authentication** — what `Authenticator` actually checks
- **Challenge: Broken Middleware** — diagnose a pipeline that lets the wrong request through
