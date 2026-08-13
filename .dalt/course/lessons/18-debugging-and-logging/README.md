# Lesson 18: Errors, Exceptions, and Debugging

## What you will be able to do

By the end of this lesson, you can:

- name the exception boundary a given failure will reach before you open any source file;
- explain why a 404 is not reported the same way a 500 is;
- predict exactly what a client sees for a 4xx, a debug-mode 5xx, and a production-mode 5xx;
- read `storage/logs/app.log` and match a line back to the request that wrote it;
- use `dd()` for a fast local dump and remove it before the code is shared;
- diagnose a failure by naming the responsible layer before changing any code.

## Recommended prerequisite

Complete [Lesson 01: Request Lifecycle](../01-request-lifecycle/README.md) first. That lesson's §9 introduced the exception boundary in outline; this lesson is the full trace. It also assumes the request-to-response path — front controller, router, middleware, handler — is already familiar.

## The problem a framework must solve

Every request can fail in three different ways, and a framework has to answer three different questions about each one:

```text
expected failure (a missing post, bad credentials)
  → what status code communicates this to the client?

unexpected failure (a bug, a downed dependency)
  → what does the client see, and does it differ between environments?

either kind
  → what, if anything, gets written down for someone to read later?
```

Conflating these is a recurring real defect class: a production error page that leaks a stack trace and a database password; a log so full of routine 404s that a real outage is buried in noise; a `dd()` call left in code that ships to a client. DALT's answer is one small class, `ExceptionHandler`, sitting at exactly one boundary — the outer `catch` in `public/index.php` — with two methods that must never be confused with each other: **report** decides what gets logged, **render** decides what the client sees.

## Predict before reading the source

For each thrown exception, predict the client-visible status and body, and whether it reaches `storage/logs/app.log`:

| Thrown | `APP_DEBUG` | Status | Body discloses class/message/trace? | Logged? |
|---|---|---|---|---|
| `abort(404)` | `true` or `false` | ? | ? | ? |
| `new RuntimeException('db password is secret')` | `false` | ? | ? | ? |
| `new RuntimeException('db password is secret')` | `true` | ? | ? | ? |
| `HttpException(503, 'Database host is secret')` | `false` | ? | ? | ? |

Then verify every cell against the trace below — and against `tests/Unit/ExceptionHandlerTest.php`, which already pins these exact answers.

## 1. `HttpException` is a status code wearing an exception

`framework/Core/HttpException.php` is a small `RuntimeException` subtype:

```php
class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
    ) {
        if ($statusCode < 400 || $statusCode > 599) {
            throw new InvalidArgumentException(/* ... */);
        }
        parent::__construct($message !== '' ? $message : "HTTP {$statusCode}", $statusCode);
    }
}
```

`abort()` (`framework/Core/functions.php`) is the usual way one gets thrown:

```php
function abort(int $code = 404, string $message = ''): never
{
    $defaultMessages = [400 => 'Bad Request', 401 => 'Unauthorized', /* ... */];
    throw new \Core\HttpException($code, $message !== '' ? $message : ($defaultMessages[$code] ?? 'Error'));
}
```

Any other thrown value — `RuntimeException`, `TypeError`, a driver's `PDOException` — is not an `HttpException`. That single `instanceof` check is the fork in the road for everything that follows.

## 2. `report()` decides what gets logged — and skips routine 4xx

```php
public function report(Throwable $exception): void
{
    if ($exception instanceof HttpException && $exception->statusCode < 500) {
        return;
    }

    app_log(sprintf(
        "%s: %s in %s:%d\n%s",
        $exception::class, $exception->getMessage(),
        $exception->getFile(), $exception->getLine(),
        $exception->getTraceAsString(),
    ));
}
```

A `404` from a mistyped URL is not a bug in your code; logging every one of them would drown the log in noise no one reads. A `500` — or any exception that never chose to be an `HttpException` at all — is, by definition, something nobody planned for, and it is written to `storage/logs/app.log` with its class, message, file, line, and full trace: enough to find the fault without reproducing it.

`app_log()` itself is a five-line function:

```php
function app_log(string $message): void
{
    $file = $_ENV['APP_LOG_PATH'] ?? base_path('storage/logs/app.log');
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    @file_put_contents($file, $line, FILE_APPEND);
}
```

No channels, no levels, no rotation — one file, appended to, timestamped. Reading it after reproducing a failure is often faster than reading the code.

## 3. `render()` decides what the client sees — and debug mode is the only variable

```php
public function render(Throwable $exception): Response
{
    $status = $exception instanceof HttpException ? $exception->statusCode : 500;

    if ($status < 500) {
        return $this->errorResponse($status, $exception->getMessage());
    }

    if (!$this->debug) {
        return $this->errorResponse($status, 'Internal Server Error');
    }

    return Response::html(sprintf(
        '<h1>%d</h1><p><strong>%s:</strong> %s</p><p>%s:%d</p><pre>%s</pre>',
        $status, $this->escape($exception::class), $this->escape($exception->getMessage()),
        $this->escape($exception->getFile()), $exception->getLine(), $this->escape($exception->getTraceAsString()),
    ), $status);
}
```

Three branches, three audiences:

| Branch | Who sees it | What it contains |
|---|---|---|
| `status < 500` | anyone | the exception's own message only — `HttpException`'s message is meant to be client-safe |
| `status >= 500`, `debug = false` | production users | a bare `Internal Server Error`, no message, no class, no trace |
| `status >= 500`, `debug = true` | you, locally | class, message, file, line, and the full trace, HTML-escaped |

The middle row is the one that matters most. `$exception->getMessage()` for an unexpected exception was written for a developer reading a log or a local error page — `'database password is secret'` is a real example from this exact repository's own test suite (`tests/Unit/ExceptionHandlerTest.php`). Showing that message to a production user is a disclosure bug, not a UX inconvenience, which is exactly why the `debug` flag — not the exception's own class or content — is what gates it. `ExceptionHandler` is constructed once, in `public/index.php`, from `$config->boolean('app.debug')`, which reads `APP_DEBUG` from `.env`.

Escaping matters independently of debug mode: `$this->escape()` runs `htmlspecialchars()` on every dynamic value before it reaches `sprintf()`, so an exception message containing `<script>` cannot execute in the browser that shows it — a debug page is still a page.

## 4. The boundary is exactly one `catch`, and it is the last one

From `public/index.php`:

```php
$exceptionHandler = new ExceptionHandler($config->boolean('app.debug'));

try {
    Session::start($config->array('session'));
    $platform->boot();
    // ...
    try {
        $response = $router->route($uri, $method, $request);
    } catch (ValidationException $exception) {
        Session::flash('errors', $exception->errors);
        Session::flash('old', $exception->old);
        $response = redirect($router->previousUrl());
    }
} catch (\Throwable $exception) {
    $exceptionHandler->report($exception);
    $response = $exceptionHandler->render($exception);
}

$response->send();
```

`ValidationException` — thrown by `Validator`/`ValidationException::throw()` — gets its own inner `catch` because a failed validation is not an error page at all; it is a redirect back to the form with flashed errors and old input (Lesson 03 covers this flow). Everything else — a thrown `HttpException`, a `TypeError` from a bad type hint, an uncaught `PDOException` from a broken query — falls through to the one outer `catch`, in registration order: `report()` runs first (so a fatal in `render()` still gets logged), then `render()` builds the response that is sent exactly once.

There is no per-controller `try`/`catch` anywhere in this path. A handler that wants a *specific* recoverable failure to become a *specific* response (Lesson 10's `db/transfer.php` is the working example) still has to catch it locally — this outer boundary is the backstop for everything a handler did not anticipate, not a replacement for handling what it did.

## Experiment: make the boundary visible

Temporarily add a route that throws, and watch each variable change the outcome independently:

```php
$router->get('/boom', function (): never {
    throw new RuntimeException('temporary experiment');
});
```

1. Request it with `APP_DEBUG=true` — full trace in the browser.
2. Flip to `APP_DEBUG=false`, restart the server, request it again — bare `Internal Server Error`, same 500 status.
3. Check `storage/logs/app.log` after each request — a new line both times; `report()` does not consult `debug` at all.
4. Change the route to `abort(404)` instead — nothing new appears in the log, because `report()` returns early for a sub-500 `HttpException`.

Remove the route when you are done; it is not part of the application.

## 5. `dd()` is a scalpel, not a logging strategy

```php
function dd(mixed ...$values): never
{
    // ... var_export()s each value inside a <pre>, then exit(1)
}
```

`dd()` halts the script and dumps whatever you hand it — a variable, a query result, a whole request object. It is the fastest way to answer "what does this actually contain right now?" while working locally. It is also a `never`-return function: the request stops there, headers included, so it must never reach a deployed branch. There is no build step that strips it for you; removing it is a manual, deliberate step before the code ships — the same discipline `git diff` catches for any other leftover.

## Debugging checklist

Given an observed failure, work outward from the exception, not inward from a guess:

- **The response is a plain, unstyled error page with no message.** `status >= 500` and `debug = false` — this is `render()` working correctly. Read `storage/logs/app.log`, not the response, for the real cause.
- **The response has a class name, a message, a file, and a trace.** `debug = true`. If you see this in anything other than local development, that is the bug — check `.env`'s `APP_DEBUG` before anything else.
- **A 404/422/419-shaped failure is missing from the log entirely.** Working as intended — `report()` skips every `HttpException` under 500. If you need to see it anyway while debugging, temporarily log at the call site; do not lower the threshold in `ExceptionHandler` and forget to revert it.
- **The status code does not match what the code threw.** Confirm the value actually reaching `render()` is an `HttpException` — a caught-and-rethrown-as-something-else exception, or a value that only *looks* like `HttpException` (wrong class, wrong namespace), loses its status and becomes a generic 500.
- **A validation failure produces an error page instead of a redirect with flashed errors.** The exception is not actually a `ValidationException` reaching the inner `catch` — confirm what was thrown, not what was intended.
- **`dd()` output made it to a deployed environment.** Not a framework bug; grep the diff for `dd(` before every commit that touches a handler.

## Trace exercise

Read these files in order and, for each, write down what it decides and what it deliberately leaves to the next file:

1. `framework/Core/HttpException.php`
2. `framework/Core/ValidationException.php`
3. `framework/Core/ExceptionHandler.php`
4. `framework/Core/functions.php` — `abort()`, `app_log()`, `dd()`
5. `public/index.php` — the two `try`/`catch` blocks

Then run:

```bash
composer test -- --filter='ExceptionHandler'
```

Read the three tests in `tests/Unit/ExceptionHandlerTest.php` before reading their assertions as a spoiler — they are exactly the prediction table from the top of this lesson, executed.

## Laravel bridge

Compared against Laravel 13.x [Error Handling](https://laravel.com/docs/13.x/errors) (consulted 2026-08-13).

Laravel separates the same two concerns, at much greater scale:

| Laravel 13.x | DALT |
|---|---|
| `withExceptions()` in `bootstrap/app.php`, registering `report()`/`render()` closures per exception type | one `ExceptionHandler` class with one `report()` and one `render()`, no per-type registration |
| `app.debug`, read from `APP_DEBUG`, gates trace disclosure the same way | identical mechanism: one boolean, constructor-injected |
| pluggable log channels (`config/logging.php`), external reporters (Sentry, Flare, Nightwatch) | one file, `storage/logs/app.log`, no channels |
| `dontReport()` / `ShouldntReport` to exclude specific exception classes from logging | one fixed rule: any `HttpException` under 500 is skipped; everything else is logged |
| exception throttling (`Lottery`, `Limit`) to sample high-volume errors | none — every reportable exception is logged, every time |
| content-negotiated JSON vs. HTML rendering based on the `Accept` header | HTML only |
| custom `resources/views/errors/{code}.blade.php` pages per status code | one generic HTML template shared by every status |

DALT keeps only the fork that actually teaches something: report versus render, and one boolean that changes what a human — developer or user — is allowed to see. Laravel's version is the same fork, wrapped in enough configuration to run a large application's error budget across many exception types and destinations.

## Complete the challenge

```bash
php artisan challenge:start broken-error-handling
php artisan challenge:verify
```

The fixture's `ExceptionHandler::render()` has two real defects: it discards an `HttpException`'s real status code, and it no longer checks `debug` before choosing what to disclose. Fix both, verify, then:

```bash
php artisan challenge:stop
```

## Transfer exercise

Add a small route whose handler throws a plain `RuntimeException`, and write a focused Pest test (`tests/Feature/` or `tests/Unit/`, following `ExceptionHandlerTest.php`'s shape) that proves, without hitting the network or a browser:

1. with `debug: true`, the response contains the exception's class and message;
2. with `debug: false`, the response contains neither, only `Internal Server Error`;
3. an `HttpException(422, 'Email already taken')` renders with status 422 and that exact message, regardless of `debug`.

This is the same contract `ExceptionHandlerTest.php` already proves — write it again from a blank file, without looking at the original, then compare.

## Explain it back

Without opening the source, answer these from memory:

1. Which method decides what gets logged, and which decides what the client sees? Can either one see what the other is doing?
2. Why does `report()` skip a 404 but not a 500?
3. Name the one value that changes between a debug-mode 500 response and a production-mode 500 response. Is it the exception's type, its message, or something else?
4. Where in `public/index.php` does a `ValidationException` diverge from every other throwable, and why does it need its own `catch`?
5. `dd()` halts execution and prints a value. Why is that exactly the property that makes it unsafe to leave in shipped code?

If you can answer these and name the responsible layer from an observed failure alone — page, status code, or log line — before opening any file, this lesson's checkpoint is complete.
