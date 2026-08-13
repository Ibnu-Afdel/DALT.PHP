# Challenge: Broken Error Handling

**Difficulty:** Medium · **Bugs:** 2 · **Lesson:** [18 — Errors, Exceptions, and Debugging](../../lessons/18-debugging-and-logging/README.md)

## Start

```bash
php artisan challenge:start broken-error-handling
php artisan serve
```

`php artisan challenge:stop` restores everything when you are done.

## Observe the symptom first

The challenge ships a probe route, `GET /debug/render-error`, that constructs a known exception and calls `ExceptionHandler::render()` on it directly — the same call `public/index.php`'s outer `catch` makes on every unexpected failure. It takes two query parameters:

- `kind=http` — an `HttpException(404, 'Post not found')`
- `kind=fatal` — a plain `RuntimeException('Card charge failed: card ending 4242 declined')`
- `debug=1` or `debug=0` — which `ExceptionHandler` mode renders it

Try the "expected" failure first:

```bash
curl -i "http://localhost:8000/debug/render-error?kind=http&debug=0"
```

**You get a 500**, not a 404. A 404 is not an error — it is normal, expected behavior for a URL that does not exist — and yet the client cannot tell it apart from a real server fault.

Now try the unexpected failure in what should be production mode:

```bash
curl -i "http://localhost:8000/debug/render-error?kind=fatal&debug=0"
```

**The response contains `RuntimeException` and the full message**, `Card charge failed: card ending 4242 declined`. `debug=0` is supposed to mean "a stranger on the internet is looking at this." A real version of this bug is a database password, an internal file path, or a stack trace handed to whoever happens to trigger a 500.

## Why both of these are the same defect

`ExceptionHandler::render()` is supposed to make two decisions, independently:

1. What HTTP status does the client see? (`HttpException`'s own code, or 500 for anything else.)
2. How much detail does the client see? (nothing but the message for a 4xx; nothing but "Internal Server Error" for a production 5xx; everything for a debug-mode 5xx.)

The broken version stopped making either decision — it hardcodes `$status = 500` and always returns the fully detailed page. Restoring both is what makes the checks below pass.

## Hints

<details>
<summary>Hint 1 — the shape of the original method</summary>

```php
public function render(Throwable $exception): Response
{
    $status = /* real status, or 500 for anything that isn't an HttpException */;

    if ($status < 500) {
        return $this->errorResponse($status, $exception->getMessage());
    }

    if (/* not debug */) {
        return $this->errorResponse($status, 'Internal Server Error');
    }

    return Response::html(/* the detailed page, unchanged */);
}
```

`errorResponse()` already exists below `render()` — it is currently unused. You do not need to write it.
</details>

<details>
<summary>Hint 2 — where the status comes from</summary>

`HttpException` has a public readonly `statusCode` property. Use `instanceof` to check whether `$exception` is one before trusting it.
</details>

<details>
<summary>Hint 3 — where the debug flag comes from</summary>

`$this->debug` is set once, in the constructor, from `$config->boolean('app.debug')` in `public/index.php`. Nothing else should decide this.
</details>

## Success criteria

- `GET /debug/render-error?kind=http&debug=0` returns **404** with just `Post not found`.
- `GET /debug/render-error?kind=fatal&debug=0` returns **500** with `Internal Server Error` only — no exception class, no message.
- `GET /debug/render-error?kind=fatal&debug=1` still returns the detailed page — fixing the leak must not also break local debugging.

## Verify

```bash
php artisan challenge:verify
```

Then confirm the behavior yourself with `curl` — the checks are a completion signal, not proof.

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 18: Errors, Exceptions, and Debugging** — read this first
- **Next challenge:** untested-contract
