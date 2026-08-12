# Lesson 11: DALT Database Layer — Queries Inside Controllers

## From raw SQL to controller code

In the last two lessons you wrote SQL directly in `psql`. Now you need to run those same queries from PHP controllers and return the results as JSON. DALT's database layer handles the PDO connection, prepared statements, and result fetching — you write the SQL and pass the parameters.

## What you will be able to do

- resolve the shared `Database` instance from the container;
- choose correctly between `get()`, `find()`, and `findOrFail()`;
- return JSON by returning data, not by printing it;
- write a paginated endpoint with bound `LIMIT` and `OFFSET`;
- run a transaction through `getConnection()` and roll it back safely.

## Recommended prerequisites

- [Lesson 02: Routing](../02-routing/README.md) — the response boundary this lesson depends on
- [Lesson 05: Database](../05-database/README.md) — the connection and fetch contract
- [Lesson 10: PostgreSQL Core](../10-postgres-intermediate/README.md) — the JOINs used here

## Predict before reading

| Controller ends with | Response |
|---|---|
| `return ['a' => 1];` | JSON, status 200 |
| `echo json_encode(['a' => 1]);` | JSON-ish, but see section 3 |
| `return Response::json(['error' => 'nope'], 404);` | JSON, status 404 |
| `echo json_encode($x); exit;` | body reaches the client, middleware never finishes |
| `findOrFail()` with no matching row | `HttpException` → 404 page |

## 1. Resolving the database

```php
$db = \Core\App::resolve(\Core\Database::class);
```

`Database` is registered as a **singleton**, so every resolution in a request returns the same instance over one connection. Resolving it is also what opens the connection — a controller that never resolves it never connects.

## 2. The three fetch methods

```php
// Many rows — [] when nothing matches, never false
$users = $db->query('SELECT id, name, email FROM users ORDER BY created_at DESC')->get();

// One row, or false
$user = $db->query('SELECT id, name FROM users WHERE id = :id', ['id' => $id])->find();

// One row, or a 404
$user = $db->query('SELECT id, name FROM users WHERE id = :id', ['id' => $id])->findOrFail();
```

`findOrFail()` calls `abort()`, which throws `Core\HttpException` with status 404. It does not print anything or stop the process — the front controller catches the exception and renders it. That is why nothing after the call runs, and why the failure still passes through the normal response path.

## 3. Return the data; do not print it

This is the part most commonly written the old way. A controller's **return value** becomes the response:

```php
<?php

$db = \Core\App::resolve(\Core\Database::class);

return $db->query(
    'SELECT posts.id, posts.title, posts.created_at, users.name AS author
     FROM posts
     LEFT JOIN users ON posts.user_id = users.id
     ORDER BY posts.created_at DESC'
)->get();
```

An array is normalized to a JSON response with status 200. No `header()`, no `json_encode()`, no `echo`.

When you need a different status or extra headers, return a `Response`:

```php
use Core\Response;

return Response::json(['error' => 'User not found'], 404);
```

### Why not `header()` and `echo`

Three concrete reasons, all covered by the routing lesson:

- **Printed output overrides the return value.** `Response::fromHandler()` buffers the handler; if anything was printed, that output becomes the body and the returned value is discarded. A stray `echo` turns a JSON endpoint into an HTML one.
- **`exit` skips the outward path.** Middleware transforms the response on the way back out, and the front controller sends it exactly once. `exit` abandons both. The bytes may still reach the browser through PHP's shutdown flush, which is precisely what makes this bug hard to see.
- **Status and headers belong to the `Response`.** `http_response_code()` mutates global state that the response object does not know about.

The rule: build a value, return it, and let the boundary send it.

## 4. Pagination with bound LIMIT and OFFSET

Returning every row of a large table is a common mistake. Accept `?page=` and `?limit=`, and bind both:

```php
<?php

$db = \Core\App::resolve(\Core\Database::class);

$page   = max(1, (int) ($_GET['page'] ?? 1));
$limit  = max(1, min(100, (int) ($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

return [
    'data' => $db->query(
        'SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
        ['limit' => $limit, 'offset' => $offset],
    )->get(),
    'page'  => $page,
    'limit' => $limit,
];
```

- `LIMIT 10` returns at most 10 rows; `OFFSET 20` skips the first 20.
- `($page - 1) * $limit` converts a 1-based page number into an offset.
- **Cap the limit.** `min(100, ...)` stops a client from requesting `?limit=1000000`.
- **Cast and clamp before binding.** `(int)` and `max(1, ...)` mean a negative or non-numeric page cannot reach the query.

Binding `LIMIT` and `OFFSET` works on both supported drivers, whether the values are passed as integers or as strings. This is worth knowing because it is *not* universally true: on some drivers a bound `LIMIT` fails when prepare emulation is off, which is why you will see older tutorials interpolating those two values by hand. In DALT, bind them.

## 5. Transactions

When several writes must succeed or fail together, reach the PDO object:

```php
<?php

use Core\Response;

$db  = \Core\App::resolve(\Core\Database::class);
$pdo = $db->getConnection();

$amount = (int) ($_POST['amount'] ?? 0);

try {
    $pdo->beginTransaction();

    $db->query('UPDATE users SET credits = credits - :amount WHERE id = :id',
        ['amount' => $amount, 'id' => $_POST['from_id'] ?? null]);

    $db->query('UPDATE users SET credits = credits + :amount WHERE id = :id',
        ['amount' => $amount, 'id' => $_POST['to_id'] ?? null]);

    $pdo->commit();

    return ['success' => true];
} catch (\Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    return Response::json(['error' => 'Transfer failed'], 500);
}
```

Key points:

- `$db->query()` uses the same connection, so it participates in the transaction started on `$pdo`.
- `rollBack()` undoes everything since `beginTransaction()`. Verified: a transfer that violates a `CHECK` constraint leaves both balances untouched.
- **Guard the rollback with `inTransaction()`.** Calling `rollBack()` when no transaction is active throws `PDOException: There is no active transaction`. If `beginTransaction()` itself was what failed, an unguarded `rollBack()` in the `catch` throws a second exception that hides the first.
- Catch `\Throwable`, not `\Exception`, so a `TypeError` inside the block still triggers the rollback.

## Debugging checklist

- **JSON endpoint returns HTML:** something printed before the return; find the stray `echo`, `var_dump`, or whitespace outside `?>`.
- **Status code ignored:** you used `http_response_code()` instead of returning a `Response`.
- **Middleware after-logic never runs:** a controller called `exit`.
- **Pagination returns the same rows on every page:** `$offset` was not passed, or `page` was not converted to an offset.
- **`There is no active transaction`:** `rollBack()` ran without a live transaction; guard with `inTransaction()`.
- **Partial write survived a failure:** the `catch` returned without rolling back.
- **`Cannot access offset on bool`:** `find()` returned `false`; use `findOrFail()` or test it.

## Trace exercise

1. `app/Http/controllers/` — an existing endpoint
2. `framework/Core/Database.php` — `query()`, `get()`, `find()`, `findOrFail()`, `getConnection()`
3. `framework/Core/Response.php` — `fromHandler()` and `fromHandlerResult()`
4. `framework/Core/functions.php` — `abort()`

Then run:

```bash
composer test -- --filter='Database|Response'
```

## Checkpoint

Close the source files and answer from memory:

1. Explain what a controller must do to produce a JSON response with status 422.
2. A controller ends with `echo json_encode($data);` and also `return $data;`. Describe the response and say which one wins.
3. Explain why `exit` in a controller is a bug even when the correct bytes reach the browser.
4. Explain what `findOrFail()` actually does when no row matches — name the function it calls and the class it throws.
5. Give two reasons to cast and clamp `?limit=` before binding it.
6. Explain why `rollBack()` should be guarded by `inTransaction()`.

## Your task

```bash
php artisan challenge:start db-missing-pagination
php artisan challenge:verify
php artisan challenge:stop
```

`GET /db/users` currently returns every user. Add bound `LIMIT` and `OFFSET`, and shape the response as `{"data": [...], "page": 1, "limit": 10}`.

## Laravel bridge

Compared against Laravel 13.x ([laravel.com/docs/13.x/database](https://laravel.com/docs/13.x/database), consulted 2026-08-12).

| Laravel 13.x | DALT |
|---|---|
| returning an array from a controller becomes a JSON response | same |
| `response()->json($data, 404)` | `Response::json($data, 404)` |
| `DB::transaction(fn () => ...)` with automatic rollback and retries | manual `beginTransaction`/`commit`/`rollBack` |
| `paginate()` producing links and totals | manual `LIMIT`/`OFFSET` and your own envelope |
| `findOrFail()` throwing `ModelNotFoundException` → 404 | `findOrFail()` calling `abort()` → `HttpException` |

Laravel's `DB::transaction()` closure is the same three PDO calls with the rollback guaranteed. Writing them by hand once is what makes the closure version legible.

## Next steps

- **Challenge: db-missing-pagination** — add LIMIT/OFFSET pagination
- **Challenge: db-broken-join** — fix a wrong JOIN type and ON clause
- **Challenge: db-broken-transaction** — add the missing rollback
