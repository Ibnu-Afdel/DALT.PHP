# Errors and Debugging

DALT exposes the failure at the boundary that caused it. Read the exception class, status, and first useful file before changing code. The framework is intentionally small enough that the exception usually points to the contract you need to inspect.

## What the browser receives

`public/index.php` catches failures and passes them to `Core\ExceptionHandler`:

- `Core\HttpException` keeps its 4xx or 5xx status.
- An HTTP status below 500 is rendered with its message and is not written to the application log.
- A 500-level failure is written to `storage/logs/app.log`.
- With `APP_DEBUG=true`, a 500 response includes the exception class, message, source location, and trace.
- With `APP_DEBUG=false`, a 500 response contains only `Internal Server Error`.

Do not enable debug output on a public deployment. Use the log and a safe reproduction instead.

## Fast diagnosis table

| Symptom or exception | Meaning | First place to inspect |
|---|---|---|
| `Core\HttpException` with 404 | no route matched, or `findOrFail()`/`abort(404)` was reached | method, path, route order, lookup key |
| `Core\HttpException` with 403 | `authorize()` or an authorization branch rejected the request | authorization condition and current user |
| response status 419 | CSRF middleware rejected a mutating request | session token, `_token`, or `X-CSRF-Token` |
| `RuntimeException: Controller not found` | the controller is outside an allowed root or the path is wrong | `routes/routes.php`, `app/Http/controllers/` |
| `RuntimeException: No middleware found` | an alias or class name cannot be resolved | `->only(...)` and middleware class name |
| `... must implement Core\Middleware\MiddlewareInterface` | the middleware class has the wrong contract | `handle(Request, Closure): Response` |
| `UnexpectedValueException` from `Response` | a handler returned an unsupported value | return `Response`, string, array, or `null` |
| `LogicException: Run query() before fetching results.` | `get()`, `find()`, or `findOrFail()` ran without a query | the database call chain |
| `InvalidArgumentException: Unsupported database driver` | configuration selected a driver DALT does not support | `DB_DRIVER` and `config/database.php` |
| `RuntimeException: Database connection failed` | PDO could not connect using the selected configuration | driver, host, port, database, credentials |
| `UnexpectedValueException` from `Config` | a config value has the wrong type | the relevant `config/*.php` file and `env()` cast |
| `CourseMetadataException` | a lesson or challenge is missing, malformed, or inconsistent metadata | that item's `meta.json`, README, order, or relationship |

An HTTP method mismatch is a 404 in DALT, not a 405. The router only considers routes whose method and URI both match.

## Trace a request

Use this order for a browser or curl failure:

1. Confirm the request method and path. Query strings do not become part of `Request::path()`.
2. Read `routes/routes.php` in registration order. Check the method, placeholder shape, and controller path.
3. If a route matches, inspect `->only(...)`. Middleware may return a redirect or 419 before the handler runs.
4. Inspect the handler's return value. Route handlers must return `Response`, string, array, or `null`.
5. If the handler resolves a service, inspect its container binding and constructor dependencies.
6. If the failure is database-related, print the selected `DB_DRIVER` and verify the connection outside the request before changing SQL.
7. Read `storage/logs/app.log` for 500-level failures when debug output is disabled.

The source path that usually answers the question is the first framework boundary named in the exception, not the last application file in the trace.

## Inspect values deliberately

`dd()` prints values in a readable HTML block and exits with status 1:

```php
dd($request->all(), $request->route('id'));
```

Use it only during local debugging. It writes output, and printed output changes response normalization: if a handler prints anything, that output becomes the body and a non-`Response` return value is discarded. Remove `dd()`, `echo`, and `var_dump()` before testing a JSON route.

For a non-terminating breadcrumb, use:

```php
app_log('Reached users controller');
```

The default log file is `storage/logs/app.log`. Set `APP_LOG_PATH` to use another path. Do not log passwords, session tokens, CSRF tokens, or full request bodies.

## Reproduce contracts without a browser

The framework's public behavior can be checked from the CLI. For example, this checks handler normalization:

```bash
php -r '
require "vendor/autoload.php";
require "framework/Core/functions.php";
$response = Core\Response::fromHandler(fn () => ["ok" => true]);
echo $response->status(), " ", $response->headers()["Content-Type"], " ", $response->content(), PHP_EOL;
try { Core\Response::fromHandler(fn () => 42); } catch (Throwable $e) { echo $e::class, PHP_EOL; }
'
```

The output is:

```text
200 application/json; charset=UTF-8 {"ok":true}
UnexpectedValueException
```

Run the automated suite after a fix:

```bash
composer test
```

For a learning challenge, use `php artisan challenge:verify` after editing and always stop the challenge before switching tasks or committing:

```bash
php artisan challenge:stop
```

## Database-specific debugging

The database layer uses real prepared statements and supports only SQLite and PostgreSQL. Put values in the parameter map:

```php
$rows = $db->query(
    'SELECT * FROM posts WHERE user_id = :user_id',
    ['user_id' => $userId],
)->get();
```

Do not interpolate request data into SQL. Table and column names cannot be bound; if they must vary, validate them against an application-owned allowlist.

When a transaction fails, catch `Throwable`, check `inTransaction()` before calling `rollBack()`, and return an explicit error response. Calling `rollBack()` when no transaction is active throws another `PDOException` and can hide the original failure.

## Safe fixes

Make one small change, rerun the smallest reproduction, then run the full suite. Do not “fix” an unclear error by weakening validation, enabling debug in production, accepting arbitrary controller paths, or printing exception traces into JSON. If the desired behavior is not supported by the code, document the gap or add a contract test before changing the public behavior.
