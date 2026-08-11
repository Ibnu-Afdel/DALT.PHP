# Lesson 01: One HTTP Request from Server to Response

## What you will be able to do

By the end of this lesson, you can:

- trace a dynamic request from the built-in server to the final response;
- distinguish the server router, front controller, bootstrap, router, handler, and response boundary;
- predict where request input, session state, and exceptions are available;
- explain why DALT sends one `Core\Response` instead of letting every layer write headers;
- diagnose a lifecycle bug by locating the first layer whose contract is violated.

## Prerequisite checkpoint

Before starting, locate these files and say what each one owns:

1. `public/router.php`
2. `public/index.php`
3. `framework/Core/bootstrap.php`
4. `framework/Core/Request.php`
5. `framework/Core/Router.php`
6. `framework/Core/Response.php`

You do not need to understand every method yet. The goal is to know where to look.

## The problem a framework solves

PHP can serve a page with one file and a few `echo` statements. A framework must make the same sequence predictable for every URL:

```text
incoming HTTP request
        ↓
server routing decision
        ↓
front controller and framework bootstrap
        ↓
request snapshot and session start
        ↓
route matching and middleware
        ↓
handler/controller
        ↓
one normalized Response
        ↓
HTTP status, headers, and body sent to the client
```

The important debugging question is not only “which controller ran?” It is “which boundary did the request reach, and what kind of value or side effect crossed that boundary?”

## Predict before reading the answer

For each request, predict the result before opening the source:

| Request | Expected path | Result |
|---|---|---|
| `GET /` | application route | welcome response |
| `GET /missing` | no matching route | HTTP 404 response |
| `GET /build/assets/app.js` | existing public file | server serves the file directly |
| `POST /api/verify/example` without a valid token | middleware | HTTP 419 response without calling the handler |

Then verify your predictions with the tests and source trace below.

## 1. The server router chooses static or dynamic handling

When using PHP's built-in server, `public/router.php` parses the request path. If the path points to an existing file below `public/`, it returns `false`; the built-in server serves that file. Otherwise it requires `public/index.php`.

This file is a development-server adapter, not the framework's route table. A production Nginx or Apache configuration must provide an equivalent “public document root plus front controller” boundary.

Try the trace:

```text
public/router.php
  ├─ existing public file → server handles it
  └─ anything else → require public/index.php
```

The static decision happens before DALT's `Router` sees the request.

## 2. `public/index.php` is the front controller

Every dynamic request enters the same PHP file. Its current order is:

1. define `BASE_PATH` and load Composer;
2. load framework helpers and `framework/Core/bootstrap.php`;
3. resolve validated `Config` and optional `Platform` objects;
4. start the native session through `Session::start()`;
5. boot the optional `.dalt` platform, if installed;
6. create the router and load application/platform routes;
7. capture the request and bind that snapshot in the container;
8. dispatch the path and effective HTTP method;
9. catch a `ValidationException` as a redirect-and-flash flow;
10. catch other throwables through `ExceptionHandler`;
11. call `$response->send()` exactly once.

The order matters. Configuration must exist before validated session configuration is used. The request must exist before route handlers and middleware receive it. The response must be selected before PHP headers and body output are sent.

## 3. Bootstrap prepares services, but the database stays lazy

`framework/Core/bootstrap.php` loads `.env`, validates the configuration directory, discovers the optional platform, creates a `Container`, and registers `Config`, `Platform`, `View`, and `Database`.

The database is registered as a singleton factory. Registration does not open a database connection. The connection is created only when a handler resolves `Core\Database`. This distinction matters when debugging a request that renders without touching the database.

The application route file is currently small:

```php
global $router;

$router->get('/', 'welcome.php');
```

The router also loads platform routes when `.dalt` is installed. Application controller files live below `app/Http/controllers`; platform controller files are a separate fallback root. This keeps learner code and optional learning code visibly distinct.

## 4. `Request` is a snapshot

`Request::capture()` copies `$_GET`, `$_POST`, and `$_SERVER` at one point in time. It does not keep reading the superglobals later.

The useful boundaries are:

- `method()` returns the uppercased effective method;
- a form `_method` override is accepted only for a real `POST` and only for `PUT`, `PATCH`, or `DELETE`;
- `path()` excludes the query string;
- `query()` reads query parameters;
- `input()` reads form input;
- `all()` merges query data first and form data second, so form data wins on duplicate keys;
- `route()` reads parameters extracted from the matched URI;
- `server()` reads the captured server array.

Cookies, uploaded files, JSON bodies, headers, and trusted proxy handling are intentionally not claimed by this small object.

Experiment: capture a request, mutate `$_GET`, and compare `Request::query()` with the superglobal. The request object should retain the original value.

## 5. The router matches, then dispatches

`framework/Core/Router.php` loops through registered `Route` objects. It compares the effective method, matches literal and `{parameter}` URI segments, stores route parameters on the request, and retains a temporary `$_GET` bridge for older controllers.

The matched route runs through middleware before its handler. A closure is invoked through the container, so a closure can receive the current `Request` and named route parameters. A string handler is resolved below the application controller root or the optional platform controller root and then required.

The router does not send output. Its contract is to return a `Response`.

## 6. Middleware can stop or wrap dispatch

Middleware receives a request and a `next` closure and must return a `Response`.

```text
outer middleware before
  inner middleware before
    handler
  inner middleware after
outer middleware after
```

An authentication, guest, or CSRF middleware may return a response without calling `next`. If it calls `next`, it can inspect or transform the response on the way back out. This is why a handler must not terminate PHP with `exit` for ordinary redirects.

For a state-changing request, the CSRF middleware compares the submitted form token or same-origin `X-CSRF-TOKEN` header with the session token. Safe methods continue without that check.

## 7. Handlers produce values; `Response` normalizes them

`Core\Response` is a small immutable value containing:

- body content;
- status code;
- headers.

The router boundary accepts these handler results:

| Handler result | Normalized response |
|---|---|
| `Response` | unchanged |
| `string` | HTML response, status 200 |
| `array` | JSON response, status 200 |
| `null` | empty response, status 200 |
| legacy direct output | captured as the response body |

`redirect('/somewhere')` returns a redirect `Response`; it does not set a header or exit. The front controller sends the final response once. This gives middleware a genuine outward path and makes response behavior testable without a browser.

The output capture around legacy controller files is a compatibility bridge. New handlers should return a value or `Response` instead of mixing `echo` with return values.

## 8. Sessions are part of the request boundary

`Session::start()` runs after configuration is loaded and before routing. It starts PHP's native file session with cookie-only IDs, strict ID acceptance, configured cookie flags, and a validated lifetime.

Flash data uses two bags:

```text
new: values flashed during this request
old: values available during this request because they were new last request
```

At request start:

```text
discard old
move previous new → old
start a fresh new bag
```

Therefore `flash('notice', 'Saved')` is immediately readable, remains readable on the next request, and expires on the request after that. `now()` is current-request-only; `keep()` and `reflash()` intentionally extend old values.

There is no end-of-request `unflash()` step in the current lifecycle. Flash persistence must not depend on a redirect terminating PHP. This is the focus of the linked `broken-session` challenge.

When a key exists in both persistent session data and flash data, `Session::get()` gives flash data precedence. `exists()` can report a stored `null` as present, while `has()` requires a non-null value.

## 9. Exceptions become responses at the edge

Expected HTTP failures use `abort()` and become `HttpException` values. The `ExceptionHandler` renders them as responses. Unexpected throwables are reported and become a generic 500 response when debug mode is off; debug mode includes escaped diagnostic detail.

Validation has a separate flow because it mutates session state:

```text
handler throws ValidationException
  ↓
front controller flashes errors and explicitly selected old input
  ↓
front controller returns a safe redirect Response
  ↓
one final send
```

Old input is an explicit caller-selected array. A controller must not flash passwords, CSRF tokens, or other secrets.

## Complete trace exercise

Use the welcome route and a missing route to trace these files in order:

1. `public/router.php`
2. `public/index.php`
3. `framework/Core/bootstrap.php`
4. `framework/Core/Request.php`
5. `framework/Core/Router.php`
6. `framework/Core/Middleware/Middleware.php`
7. `framework/Core/Response.php`
8. `framework/Core/ExceptionHandler.php`

For each file, write down:

- what enters the file;
- what state it reads or mutates;
- what it returns;
- whether it is allowed to send output.

Then run:

```bash
composer test -- --filter='Request|Response|welcome|404'
```

## Debugging signals

- A static asset 404 points first to the public document root or server router, not the application route table.
- A valid route with an unexpected 404 points to method/path matching or controller-root resolution.
- A response that bypasses middleware often comes from direct output or a terminating helper.
- A flash value that disappears after a redirect points to request-start aging, session startup, or the redirect caller—not to an end-of-request cleanup step.
- A detailed 500 in production points to the exception boundary or debug configuration.

## Laravel bridge

Laravel follows the same essential shape: the request enters a front controller, the application bootstraps a container, middleware surrounds route dispatch, the action is normalized to a response, and the response travels outward before being sent.

DALT deliberately omits Laravel's service providers, PSR-7 bridges, advanced response types, content negotiation, streamed/download responses, named middleware groups, and production proxy configuration. The smaller contract makes the request-to-response mechanism visible.

Compare the DALT source with the current Laravel lifecycle, responses, middleware, and session documentation after you can narrate this trace. Treat Laravel as a larger comparison target, not as a requirement that DALT reproduce every feature.

## Challenge and transfer build

Complete **Challenge: Broken Session**. It asks you to repair flash precedence and request-start aging while preserving the current session API.

For a transfer exercise, build a tiny “saved notice” form with one GET page and one POST handler. The POST handler should flash a notice and return a redirect response. Add tests that prove the notice is available on the redirected request, expires after one more request, and wins over a persistent value with the same key.

## Explain it back

Without opening the source, answer these questions:

1. Why can the built-in server serve a file without invoking `Router`?
2. Why is `Request` a snapshot rather than a live view of `$_GET`?
3. What does the middleware pipeline gain by requiring `Response` returns?
4. Where is the only final response sent?
5. Why does flash aging happen at request start?
6. What is the difference between a 404 `HttpException`, a validation redirect, and an unexpected 500?
7. Name one feature Laravel provides that DALT intentionally leaves out.

If you can answer these and identify the responsible file from a failing request, this lesson's lifecycle checkpoint is complete.
