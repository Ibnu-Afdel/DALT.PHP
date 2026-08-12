# DALT.PHP Framework Reference

The public API of the framework, for people building an application with it.

This is reference material, not a tutorial. If you are learning the concepts, work through the lessons in `.dalt/course/lessons/`. If you already know what you want and need to know how DALT spells it, you are in the right place.

**Every behavior on this page was executed against the framework, not inferred from the source.** Where something is surprising, the surprising part is stated rather than smoothed over.

---

## Routing

Routes live in `routes/routes.php` and are registered against the global `$router`.

```php
$router->get('/posts', 'posts/index.php');
$router->post('/posts', 'posts/store.php');
$router->patch('/posts/{id}', 'posts/update.php');
$router->put('/posts/{id}', 'posts/replace.php');
$router->delete('/posts/{id}', 'posts/destroy.php');
```

A handler is either a **controller path** relative to `app/Http/controllers/`, or a **closure**.

```php
$router->get('/hello', fn () => 'Hello');
$router->get('/posts/{id}', fn (Request $request) => ['id' => $request->route('id')]);
```

Closures are resolved through the container, so they may type-hint `Request`, a named route parameter, or any registered service.

### Matching

The route table is an **ordered list** and the first entry matching both method and URI wins. A placeholder matches one or more characters other than `/`, so a generic pattern placed above a specific one shadows it permanently:

```php
$router->get('/posts/{id}', 'posts/show.php');
$router->get('/posts/create', 'posts/create.php');  // unreachable
```

Static text in a pattern is escaped before matching, so `.`, `+`, `(` and friends stay literal. `'/files/{name}.json'` matches `/files/report.json` and not `/files/reportXjson`.

Placeholder names must start with a letter or underscore, then letters, digits, or underscores.

### No match

When nothing matches, the router calls `abort(404)`, which throws `Core\HttpException` with code `404`. A route registered for a different method is not a match — `POST /posts/create` against a `GET`-only registration is a 404, not a 405.

### Middleware

```php
$router->post('/posts', 'posts/store.php')->only(['auth', 'csrf']);
```

`only()` attaches to the most recently registered route. Calling it before registering any route throws `LogicException`.

Middleware is not reached at all when no route matches, so a 404 can never be fixed inside a middleware class.

---

## Request

```php
$request = \Core\App::resolve(\Core\Request::class);
```

Controller files are dispatched with `require` and receive no arguments, so resolve the request from the container rather than expecting a `$request` variable.

| Method | Returns |
|---|---|
| `method()` | the effective HTTP method, uppercase |
| `path()` | the URI path without the query string |
| `route($key)` | a value captured from the URI pattern |
| `query($key, $default)` | a value from the query string |
| `input($key, $default)` | a value from submitted form data |
| `all()` | query data merged with form data, form data winning |
| `server($key)` | a raw server value |

Verified with `?page=2&q=x`, form data `title=T`, URI `/posts/9?page=2`, and route parameter `id=9`:

```
method() = PATCH      path() = /posts/9
route('id') = 9       query('page') = 2      input('title') = T
all() = {"page":"2","q":"x","title":"T","_method":"PATCH"}
```

**Route values are always strings.** `route('id')` on `/posts/42` returns `"42"`, not `42`. Cast before using it as a number.

### Method override

HTML forms submit only `GET` and `POST`, so a `POST` carrying `_method` reaches `PUT`, `PATCH`, or `DELETE`:

```html
<form method="POST" action="/posts/42">
    <input type="hidden" name="_method" value="PATCH">
</form>
```

The override is honored **only on POST**, and only for those three verbs. A `GET` with `?_method=DELETE` stays `GET` — verified.

---

## Response

A handler's **return value** becomes the response. You do not call `header()` or `echo`.

```php
return ['status' => 'ok'];                    // JSON, 200
return 'Hello';                               // HTML, 200
return Response::json(['error' => 'x'], 404); // JSON, 404
return null;                                  // empty, 200
```

### Factories

| Call | Status | Content-Type |
|---|---|---|
| `Response::html($body)` | 200 | `text/html; charset=UTF-8` |
| `Response::text($body)` | 200 | `text/plain; charset=UTF-8` |
| `Response::json($data)` | 200 | `application/json; charset=UTF-8` |
| `Response::redirect($to)` | 302 | — (sets `Location`) |

All four take an optional status as the second argument. `withHeader()` and `withContent()` return a modified copy.

### Normalization

| Handler returns | Result |
|---|---|
| `Response` | used unchanged |
| string | HTML, 200 |
| array | JSON, 200 |
| `null` | empty body, 200 |
| anything else | throws `UnexpectedValueException` |

### Printed output wins

This is the one that catches people. `Response::fromHandler()` buffers the handler, and **if anything was printed, that output becomes the body and the return value is discarded**:

```php
echo 'printed';
return ['a' => 1];
// Content-Type: text/html; charset=UTF-8   body: printed
```

A stray `var_dump` turns a JSON endpoint into an HTML one. If your JSON arrives as HTML, look for output before the return.

Likewise, `http_response_code(404)` in a controller is discarded — `Response::send()` sets the status from the `Response` object afterwards. Return `Response::json($data, 404)` instead.

And do not call `exit`: it abandons the outward middleware path and the single `send()`.

---

## Middleware

Built-in aliases: `auth`, `guest`, `csrf`.

A middleware implements `Core\Middleware\MiddlewareInterface`:

```php
public function handle(Request $request, Closure $next): Response;
```

Call `$next($request)` to continue, or return a `Response` to stop. The return value of `$next()` is the response from everything further in, so a layer may inspect or replace it on the way out.

Layers wrap **inside out**: `->only(['a', 'b'])` runs `a` before `b` on the way in, and `b` before `a` on the way out.

Resolution has three failure modes, each with its own message:

- the key is neither an alias nor an existing class — `No middleware found for '<key>'.`
- the class does not implement the interface — `... must implement Core\Middleware\MiddlewareInterface.`
- the constructor cannot be built — `Unable to construct middleware '<class>': ...`

An unaliased fully-qualified class name works directly:

```php
$router->get('/admin', 'admin.php')->only(['auth', \App\Middleware\Admin::class]);
```

### CSRF

`GET`, `HEAD`, and `OPTIONS` skip the check. Other methods require a token from the `_token` field or the `X-CSRF-Token` header, compared with `hash_equals()`. A mismatch returns **419**, not 403.

---

## Database

```php
$db = \Core\App::resolve(\Core\Database::class);
```

Registered as a singleton, so one connection per request. Resolving it is what opens the connection — a request that never resolves it never connects.

**Supported drivers: `sqlite` and `pgsql`.** Anything else throws `InvalidArgumentException: Unsupported database driver: mysql` before connecting.

### Queries

```php
$rows = $db->query('SELECT * FROM posts WHERE user_id = :id', ['id' => $id])->get();
$row  = $db->query('SELECT * FROM posts WHERE id = :id', ['id' => $id])->find();
$row  = $db->query('SELECT * FROM posts WHERE id = :id', ['id' => $id])->findOrFail();
```

| Method | Returns |
|---|---|
| `get()` | list of rows, `[]` when none match |
| `find()` | one row, or `false` |
| `findOrFail()` | one row, or aborts with 404 |
| `getConnection()` | the underlying `PDO` |

Fetching before querying throws `LogicException: Run query() before fetching results.`

### Types and safety

Prepared statements are real, not emulated, so the statement and its values travel separately and a bound value can never be parsed as SQL. Passing `'1 OR 1=1'` as a parameter matches zero rows rather than every row.

Integer columns come back as PHP `int`, not string — `STRINGIFY_FETCHES` is off. Comparisons with `===` behave as you would expect.

Table and column names cannot be parameterized. If they must vary, validate against an allowlist you wrote.

### Transactions

```php
$pdo = $db->getConnection();

try {
    $pdo->beginTransaction();
    // ... $db->query(...) calls share this connection and this transaction
    $pdo->commit();

    return ['ok' => true];
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    return Response::json(['error' => 'failed'], 500);
}
```

Guard the rollback: `rollBack()` with no active transaction throws `PDOException: There is no active transaction`, which would mask the original error.

---

## Session and flash

```php
Session::put($key, $value);          // persists across requests
Session::get($key, $default);
Session::flash($key, $value);        // readable this request and the next only
Session::now($key, $value);          // readable this request only
Session::forget($keys);
Session::flush();
Session::destroy();
```

Flash data ages **once per request start**. Verified across three consecutive requests:

```
same request  : get('notice') = 'saved'
next request  : get('notice') = 'saved'
request after : get('notice') = NULL
```

Persistent values set with `put()` are unaffected.

**Flash shadows a persistent value of the same key.** With `put('k', 'from-put')` and `flash('k', 'from-flash')`, `get('k')` returns `'from-flash'` while the flash is live.

`keep()` extends flash data by one more request; `reflash()` extends all of it. Verified: a flashed value survives two agings with `keep()` and is gone after two without it.

The key `_flash` is reserved — using it throws `InvalidArgumentException`.

---

## Validation and errors

```php
Validator::string($value, $min = 1, $max = null);
Validator::email($value);
```

Verified: `string('abc')` → `true`; `string('')` → `false`; `string('ab', 3)` → `false`; `string('abcdef', 1, 3)` → `false`. `email('a@b.com')` → `true`; `email('not-an-email')` and `email(123)` → `false`.

To reject input, throw:

```php
ValidationException::throw(['email' => 'Valid email required'], ['email' => $email]);
```

The front controller catches it, flashes `errors` and `old`, and redirects back. The form re-renders on the **next** request and reads the flashed values with `old('email')`.

Never put a password in the old-input array.

The constructor is strict: an empty error bag, a non-string field, or a non-string message each throw `InvalidArgumentException` at the throw site rather than producing a blank form later.

### Aborting

```php
abort(404);              // Core\HttpException, code 404, message "Not Found"
abort(403, 'Custom');    // your message
authorize($condition);   // aborts 403 when false, returns silently when true
authorize($cond, 401);   // choose the status
```

Default messages exist for 400, 401, 403, 404, 405, 419, 422, 429, 500, and 503.

---

## Authentication

```php
$auth = new \Core\Authenticator();

$auth->attempt($email, $password);   // bool
$auth->login($userRow);
$auth->logout();

$auth->check();   // bool
$auth->guest();   // bool
$auth->user();    // ['id' => int, 'email' => string] or null
$auth->id();      // int or null
```

Prefer these over reading `$_SESSION['user']`: `user()` re-validates the stored shape on every read, so a corrupted session returns `null` rather than a half-built user.

### Identity rules

`login()` accepts only a positive integer id and a non-empty email, and throws `InvalidArgumentException` otherwise. Verified:

| Input | Result |
|---|---|
| `['email' => 'u@e.com']` | rejected — no id |
| `['id' => 0, 'email' => 'u@e.com']` | rejected — id must be positive |
| `['id' => 1, 'email' => '  ']` | rejected — blank email |
| `['id' => '42', 'email' => 'u@e.com']` | **accepted**, stored as `['id' => 42, ...]` |

A numeric string id is coerced to `int`. The password hash is never stored in the session — only the two validated fields are.

`login()` regenerates the session id **before** writing identity, so the pre-authentication id is abandoned while still anonymous. That ordering is the session-fixation defence and is handled for you.

### Intended destination

The `auth` middleware records where a guest was heading, and `intended()` returns it once:

```php
return redirect($auth->intended());   // falls back to '/'
```

Only `GET` and `HEAD` targets are remembered, and only local absolute paths. Anything with a scheme, host, port, or fragment is refused, which is what stops the login form becoming an open redirect. `intended('https://evil.test/')` throws `InvalidArgumentException`.

---

## Container

```php
App::bind($key, $factory);       // new instance per resolution
App::singleton($key, $factory);  // one instance, built on first resolution
App::instance($key, $object);    // already-built value
App::resolve($key);
```

Verified: `bind` returns a different object each time; `singleton` returns the same object. Concrete classes with type-hinted constructor dependencies are built automatically.

---

## Helpers

| Helper | Behavior |
|---|---|
| `base_path($path)` | absolute path from the project root |
| `env($key, $default)` | reads `$_ENV`, `$_SERVER`, then `getenv()` |
| `config($key, $default)` | dot-notation configuration lookup |
| `view($path, $data)` | renders a view |
| `redirect($to)` | redirect response |
| `old($key, $default = '')` | flashed old input; default `''` |
| `abort($code, $message)` | throws `HttpException` |
| `authorize($condition, $status)` | aborts unless true |
| `csrf_token()` | 64-character token, stable within a request |
| `csrf_field()` | `<input type="hidden" name="_token" value="...">` |
| `dd(...)` | dump and stop |
| `urlIs($path)` | current path comparison |
| `app_log($message)` | appends to the application log |
| `vite($entry)` | built asset URL |

`env()` reads the environment only. It has **no `_FILE` convention**, so mounting a Docker secret at `/run/secrets/db_password` does nothing on its own — bridge it in your entrypoint if you need it.

`config()` resolves `Core\Config` from the container, so it works inside a request or an artisan command but throws `LogicException: The application container has not been bootstrapped.` in a bare script. Verified: `config('database.database.driver')` returns `'sqlite'`, and a missing key returns the default.

`urlIs()` compares against the current path, ignoring the query string — with `REQUEST_URI` of `/posts?x=1`, `urlIs('/posts')` is `true`.

`csrf_token()` returns a 64-character hex string and is stable within a request.

---

## What DALT does not have

Named routes and a URL generator; route groups; parameter constraints; implicit model binding; resource routes; middleware groups or a global stack; terminable middleware; a query builder or ORM; multiple database connections; MySQL.

These are omissions, not gaps to work around. Each is a convenience layer over the mechanism above, and the framework is small so that mechanism stays visible.

---

## Verifying anything here yourself

Every claim on this page can be re-run. The pattern:

```bash
php -r '
require "vendor/autoload.php";
use Core\Response;
$r = Response::fromHandler(fn () => ["a" => 1]);
echo $r->status(), " ", $r->headers()["Content-Type"], " ", $r->content(), "\n";
'
```

The full suite is the other source of truth:

```bash
composer test
```

If this page and the framework ever disagree, the framework is right and this page is the bug. Report it.
