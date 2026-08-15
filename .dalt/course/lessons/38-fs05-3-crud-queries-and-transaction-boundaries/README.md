# FS05.3 — CRUD, queries and transaction boundaries

Lesson ID: FS05.3
Title: CRUD, queries and transaction boundaries
Part: 05 — DALT API and PostgreSQL
Order: 3
Status: Published
Estimated effort: 110–140 minutes
Difficulty: Integration
Prerequisites: FS05.2 — Relational modeling and migrations
Project milestone: B05 — Persistent application
Primary source dossier: POSTGRESQL_DOCS.md
Last reviewed: 2026-08-14

## Why this matters

A schema rejects impossible rows, but it does not yet let React do useful work. This lesson
connects the API contract to durable queries: list a project's issues, find one, create,
change, and remove them. The dangerous shortcut is to build SQL by joining strings from a
request. It appears to work until a title contains an apostrophe, a filter changes query
structure, or a client controls a sort expression.

A dependable handler makes several decisions in order. It validates a client proposal, chooses
parameterized SQL, maps a database row into public JSON, and classifies absence or failure. A
transaction adds one more: when several writes express a single business fact, all of them
become visible or none do. Catching an exception is not atomicity; rollback is. This is the
lesson where the fixture stops being a stand-in and becomes an application backend.

## Before you start

Required: FS05.2's PostgreSQL schema and FS05.1's documented envelopes. Read
`framework/Core/Database.php` and confirm its surface for yourself:

```php
$database->query($sql, $params);   // prepares, then executes with bound values
$database->find();                 // one associative row, or false
$database->findOrFail();           // one row, or abort(404)
$database->get();                  // list of rows, possibly empty
$database->getConnection();        // the PDO instance
```

PDO is configured with `ERRMODE_EXCEPTION`, `EMULATE_PREPARES => false`, and
`STRINGIFY_FETCHES => false`. Real prepared statements mean values are never interpolated into
SQL text by PHP. `getConnection()` gives you `beginTransaction()`, `commit()`, `rollBack()`,
and `inTransaction()`. DALT has no ORM and no hidden transaction helper — the boundary is
yours to draw.

Going deeper in DALT Core — optional:

- [DALT database layer](/learn/lessons/11-dalt-db-layer) and [PostgreSQL intermediate](/learn/lessons/10-postgres-intermediate) are reference, not prerequisites.

## By the end

- implement issue CRUD through DALT routes and prepared SQL;
- join and map rows into stable API shapes;
- allowlist filters, sort keys, and pagination values;
- distinguish a missing row, a validation failure, and a database failure;
- use a transaction for one deliberate multi-write operation;
- prove HTTP behaviour, stored rows, and rollback independently.

## Predict before reading

1. Why is an interpolated title unsafe even after validation?
2. Which parts of `ORDER BY` can a parameter bind?
3. If an UPDATE affects no matching row, what result should a client see?
4. If activity insertion fails after issue insertion, which result is truthful?

## Mental model

~~~text
request → validate → allowlist choices → prepared SQL → rows → response mapper
                      page/sort/filter      │
                                             ↓
business event → begin transaction → write A → write B → commit or rollback
~~~

Values are data and belong in parameters. SQL identifiers and keywords are *structure* and
cannot be parameters at all, so the server selects them from a small allowlist it wrote. A
mapper is a third boundary: database columns, numeric ids, timestamps, and internal fields are
not automatically the API that React consumes.

## 1. Values are parameters; structure is not

Start with the relation the listing screen actually needs:

```php
$sql = 'SELECT i.id, i.title, i.description, i.status, i.priority,
               i.created_at, i.updated_at,
               p.id AS project_id, p.name AS project_name
        FROM issues i
        JOIN projects p ON p.id = i.project_id
        WHERE i.project_id = ?';

$params = [$projectId];
```

Never interpolate a title, an id, a limit, or filter text. Prediction 1 asks why validation is
not enough, and the answer has two halves. Validation and escaping are separate concerns —
a title that passes every rule you wrote can still contain `'`, and the moment your
concatenation meets it the SQL is malformed or, worse, altered. And validation drifts: someone
relaxes a rule in six months without knowing a query downstream depended on it. A prepared
statement sends structure and values over separate channels, so a value can never become
structure no matter what it contains.

An optional filter appends a condition and a parameter together, so the two can never fall out
of step:

```php
if ($status !== null) {
    $sql .= ' AND i.status = ?';
    $params[] = $status;
}
```

Sorting is genuinely different, and prediction 2's answer is *none of it*. A placeholder is a
value, and `ORDER BY ?` sorts by a constant string — which silently does nothing rather than
failing loudly. Column names and directions must come from a map you control:

```php
$sortable = [
    'created_at' => 'i.created_at',
    'priority'   => 'i.priority',
    'title'      => 'i.title',
];

$sort = $sortable[$request->query('sort')] ?? 'i.created_at';
$direction = $request->query('direction') === 'asc' ? 'ASC' : 'DESC';

$sql .= " ORDER BY {$sort} {$direction} LIMIT ? OFFSET ?";
```

Only values that came out of `$sortable` — strings you typed — reach the SQL text. An unknown
sort key falls back to the default rather than erroring, which is a deliberate product choice:
a stale bookmark should still render a list. Rejecting it with 422 is equally defensible.
What is not defensible is passing it through.

Pagination values are bound, but bound as integers you have already range-checked, because an
unbounded `limit` is a way for one request to read the whole table:

```php
$page  = max(1, (int) $request->query('page', 1));
$limit = min(100, max(1, (int) $request->query('limit', 25)));

$params[] = $limit;
$params[] = ($page - 1) * $limit;

$rows = $database->query($sql, $params)->get();
```

Run this against your own PostgreSQL and confirm the binding works before building on it — some
driver and server combinations want an explicit type in `LIMIT` position, and if yours
complains, `LIMIT CAST(? AS BIGINT)` settles it. Check rather than assume; this is a two-minute
experiment that saves an hour of confusion later.

## 2. Map rows, and classify absence

A database row is an implementation shape. Map it deliberately, in one function used by both
the list and the detail endpoint:

```php
/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function issueResponse(array $row): array
{
    return [
        'id' => (string) $row['id'],
        'projectId' => (string) $row['project_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'status' => $row['status'],
        'priority' => $row['priority'],
        'createdAt' => $row['created_at'],
        'updatedAt' => $row['updated_at'],
    ];
}
```

One mapper for both endpoints is the point: it is what makes a list item and a detail response
the same shape, so FS04.3's single `parseIssue` can validate both.

Now the handlers. Listing treats emptiness as an ordinary answer:

```php
// GET /api/issues?project_id=7
return apiJson([
    'data' => array_map(issueResponse(...), $rows),
    'meta' => ['page' => $page, 'limit' => $limit],
]);
```

An empty `$rows` yields `"data": []` with a 200. Detail treats absence as a different fact:

```php
// GET /api/issues/{id}
$row = $database->query(
    'SELECT i.*, p.id AS project_id FROM issues i
     JOIN projects p ON p.id = i.project_id WHERE i.id = ?',
    [$id],
)->find();

if ($row === false) {
    return apiJson(['error' => [
        'code' => 'not_found',
        'message' => 'That issue does not exist.',
    ]], 404);
}

return apiJson(['data' => issueResponse($row)]);
```

Create inserts with parameters and returns the *stored* row, using `RETURNING` so there is no
second query and no window in which another writer could change it:

```php
// POST /api/issues  — after the FS05.1 allowlist has produced $title, $projectId, $priority
$row = $database->query(
    'INSERT INTO issues (project_id, title, priority)
     VALUES (?, ?, ?)
     RETURNING id, project_id, title, description, status, priority, created_at, updated_at',
    [$projectId, $title, $priority],
)->find();

return apiJson(['data' => issueResponse($row)], 201);
```

Notice `status` is absent from the INSERT. The schema's `DEFAULT 'todo'` supplies it, and
`RETURNING` hands back what was actually stored — which is exactly the value React should
display, and exactly what FS04.2 argued for from the other side of the wire.

Partial update validates only the fields that are present, and rejects a patch that asks for
nothing:

```php
// PATCH /api/issues/{id}
$assignments = [];
$params = [];

if (array_key_exists('title', $input)) {
    $assignments[] = 'title = ?';
    $params[] = $title;
}
if (array_key_exists('status', $input)) {
    $assignments[] = 'status = ?';
    $params[] = $status;
}

if ($assignments === []) {
    return apiJson(['error' => [
        'code' => 'validation_failed',
        'message' => 'No supported fields were supplied.',
    ]], 422);
}

$assignments[] = 'updated_at = CURRENT_TIMESTAMP';
$params[] = $id;

$row = $database->query(
    'UPDATE issues SET ' . implode(', ', $assignments) . ' WHERE id = ? RETURNING *',
    $params,
)->find();

if ($row === false) {
    return apiJson(['error' => ['code' => 'not_found', 'message' => 'That issue does not exist.']], 404);
}
```

The assignment fragments are strings you wrote, chosen by whether a key was present — the same
allowlist discipline as sorting. `array_key_exists` rather than `isset` matters here, because
`isset` is false for a key explicitly set to null, and "set this to null" is a different request
from "do not touch this field".

That `RETURNING` also answers prediction 3. An UPDATE matching no row is not an error in SQL —
it succeeds, affecting zero rows. Without `RETURNING` you would have to check `rowCount()` and
remember that zero can also mean "matched, but the values were already identical". With
`RETURNING`, `find()` gives you `false` for a genuinely missing row, and the 404 writes itself.

Delete confirms the row existed, so that a client learns the difference between deleting
something and deleting nothing:

```php
// DELETE /api/issues/{id}
$deleted = $database->query('DELETE FROM issues WHERE id = ? RETURNING id', [$id])->find();

return $deleted === false
    ? apiJson(['error' => ['code' => 'not_found', 'message' => 'That issue does not exist.']], 404)
    : new Response('', 204, corsHeaders());
```

## 3. Classify failures instead of flattening them

Three different things can go wrong, and they deserve three different responses. Expected input
failures are 422 in the FS05.1 envelope. A missing parent project is worth checking before the
insert so the client gets a helpful 404 — while the foreign key still protects you against the
race between that check and the write. Everything else is a server defect and belongs in a 500.

The temptation is one broad catch that calls every failure user error:

```php
// Do not do this.
try {
    $row = $database->query($sql, $params)->find();
} catch (Throwable) {
    return Response::json(['error' => ['code' => 'validation_failed', 'message' => 'Invalid input']], 422);
}
```

A misspelled column name now reports itself as the user's fault, in a message that will send
someone hunting through the React form for a bug that is in the SQL. Catch narrowly, and only
where you can name the cause:

```php
use PDOException;

try {
    $row = $database->query($insertSql, $params)->find();
} catch (PDOException $exception) {
    // 23503 = foreign_key_violation, 23505 = unique_violation. Everything else is ours.
    if ($exception->getCode() === '23503') {
        return apiJson(['error' => [
            'code' => 'not_found',
            'message' => 'That project does not exist.',
        ]], 404);
    }

    throw $exception;
}
```

Re-throwing is the important half. An unhandled exception reaches DALT's exception handler and
becomes a 500 that gets logged — which is what you want for a defect. Swallowing it produces a
polite response and no evidence.

Never forward a raw PDO message to a client. It names your tables, your columns, and sometimes
your values. Log it; return the envelope.

## 4. Draw the transaction around one business fact

Creating an issue and recording an activity event is one fact only if the history is promised
whenever the issue exists. If it is, both writes belong in one transaction:

~~~php
$connection = $database->getConnection();
$connection->beginTransaction();

try {
    $issue = $database->query(
        'INSERT INTO issues (project_id, title, priority)
         VALUES (?, ?, ?)
         RETURNING id, project_id, title, description, status, priority, created_at, updated_at',
        [$projectId, $title, $priority],
    )->find();

    $database->query(
        'INSERT INTO activity (issue_id, kind, message) VALUES (?, ?, ?)',
        [$issue['id'], 'created', 'Issue created'],
    );

    $connection->commit();
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    throw $exception;
}
~~~

The `inTransaction()` guard is not defensive noise. Some failures — a lost connection, certain
server-side errors — abort the transaction before your `catch` runs, and calling `rollBack()`
on a connection that is no longer in a transaction throws a second exception that hides the
first. You then debug the wrong error.

Prediction 4's answer is that neither row should exist. An issue with no creation record is
precisely the inconsistency the transaction exists to prevent, and returning 201 while the
history is missing would be a successful-looking lie.

Prove it rather than believing it. Add a temporary constraint violation to the second insert,
count rows before and after in a *separate* `psql` session, and confirm both counts are
unchanged:

```sql
SELECT (SELECT count(*) FROM issues) AS issues, (SELECT count(*) FROM activity) AS activity;
```

A separate session matters: inside your own uncommitted transaction you would see your own
writes, and conclude the rollback failed when it had not yet happened. Then remove the
sabotage and confirm both counts increase together.

Do not wrap a single INSERT in a transaction to be able to say the word. One statement is
already atomic.

## 5. Wire the routes, then retire the fixture

Five handlers need five routes. Register them together so the whole surface is readable in one
place:

```php
// routes/routes.php
$router->get('/api/issues', 'api/issues/index.php');
$router->post('/api/issues', 'api/issues/store.php');
$router->get('/api/issues/{id}', 'api/issues/show.php');
$router->patch('/api/issues/{id}', 'api/issues/update.php');
$router->delete('/api/issues/{id}', 'api/issues/destroy.php');
```

Order matters less than you might expect — DALT matches on method and path together, so
`GET /api/issues` and `GET /api/issues/{id}` cannot collide. What does matter is that a request
with no matching **method** falls through to the same 404 as a request with no matching path,
so a missing `patch` line looks exactly like a missing route. When a mutation 404s and the URL
is obviously right, check the verb first.

Each controller resolves what it needs from the container:

```php
<?php
// app/Http/controllers/api/issues/index.php

declare(strict_types=1);

use Core\App;
use Core\Database;
use Core\Request;
use Core\Response;

$request = App::resolve(Request::class);
$database = App::resolve(Database::class);
```

Now retire the fixture, and do it in the one order that keeps a working checkpoint:

1. Bring up the DALT server and prove all five operations with curl. The React application is
   still pointed at the fixture and still works.
2. Change `VITE_API_BASE_URL` to `http://127.0.0.1:8000`. Nothing else. If FS04.3 did its job,
   this is the entire frontend change.
3. Exercise the UI. Fix what breaks — and read carefully, because what breaks now is
   *contract drift*, not React: an id that is a number instead of a string, a `created_at`
   your mapper renamed, an error envelope shaped differently from the fixture's.
4. Delete the fixture copy from your workspace.

Step 3 is the one that pays for FS05.1. Every difference you find is a place where the fixture
and your contract disagreed, and the parser catching them is the runtime boundary earning its
keep. A project without that parser would have rendered `undefined` and left you guessing.

Two things will still differ from the fixture and are worth expecting. CORS is now your
server's problem — the fixture reflected loopback origins, and your DALT server does not do
that by default. FS05.1's `apiJson()` and `OPTIONS /api/{*}` route are what make a browser
request behave the same as the curl proof above; if a request that works under curl fails
silently in the browser here, that is the symptom of a handler that still calls
`Response::json()` directly, not a new problem. And ids are now `BIGSERIAL` values from
PostgreSQL rather than `ISS-41` strings, so any code that assumed the `ISS-` prefix has to
go. Both are honest consequences of leaving a teaching prop behind.

## Try it

Prove every operation directly, before React is involved: list a project's issues, request a
missing id, create a valid issue, send a whitespace title, PATCH its status, PATCH with an
empty object, and DELETE it twice. For the list, exercise `page`, `limit`, `sort`, and
`direction`, and send a bogus sort value to confirm the allowlist behaves as you decided. Query
PostgreSQL after every mutation — the response body is a claim, and the row is the fact. Then
force the activity write to fail and prove the issue count is unchanged.

## Common mistakes

- Concatenating request values into SQL because they were "validated."
- Assuming a bound parameter can control `ORDER BY` structure.
- Leaving `limit` unbounded, so one request can read the whole table.
- Using `isset` where `array_key_exists` is meant, losing explicit nulls in a PATCH.
- Returning raw rows, or raw PDO exception messages, to a client.
- Calling an empty collection a 404, or a missing detail an empty 200.
- Trusting a client-supplied issue after POST instead of the `RETURNING` row.
- Catching every exception as a validation failure, hiding server defects.
- Catching failure without rolling back, or rolling back without checking `inTransaction()`.

## When this goes wrong

If prepared SQL fails, log the template and the parameter *types* — never the values, which may
be secrets — and run a safe equivalent in `psql`. If a join duplicates rows, find which relation
is one-to-many; a one-to-many join multiplies the parent row once per child, and the fix is
usually an aggregate rather than `DISTINCT`.

If a transaction seems to stay open, check `inTransaction()` on every exit path including early
returns. If React disagrees with curl, compare the raw JSON against your mapper and FS04.3's
parser before touching either — one of them changed and the other did not. And do not use the
appearance of the UI as proof about the database: the screen shows what the last response said,
which is not the same as what is stored.

## Exercise

**Goal:** Replace the fixture's issue operations with persistent DALT CRUD.

**Starting state:** FS05.2 created the relational schema and FS05.1 fixed the public contract.

**Requirements:** Implement project and issue list and detail, create, partial update, and
delete. Use parameterized values, a join where project context is returned, allowlisted
filter/sort/pagination choices, one shared response mapper, and 404/422 distinctions. Classify
at least one PDO error narrowly and re-throw the rest. Add one real two-write transaction and a
controlled second-write failure.

**Verification:** Prove each operation with direct HTTP and with PostgreSQL rows, then through
the React client. Capture missing detail, invalid create, empty patch, valid mutation, double
delete, and rollback.

**Mode: manual HTTP, PostgreSQL, browser, and command evidence.** The learner owns this
implementation; evidence must show behaviour rather than source-text resemblance.

**Hints:** Get a plain list working before filters. Keep one mapper for list and detail. Use
`RETURNING` rather than guessing defaults or issuing a follow-up SELECT. Add activity only when
it gives the transaction a truthful second write.

## In the project

B05 is now a route-to-row-to-screen loop. The fixture is removed, while the typed client stays
the seam that stopped a backend replacement from forcing a component rewrite — which is the
return on FS04.3. Part 06 adds tests, users, sessions, and authorization to these same
resources, and it will be much easier because the handlers are already small and the contract is
already written down.

## Closed-book checkpoint

1. Why do values use parameters while sort columns need an allowlist?
2. What distinguishes a 404 detail result from an empty 200 collection?
3. Why map a database row before returning JSON?
4. Why does `RETURNING` classify a missing row better than `rowCount()`?
5. What makes two writes one transaction-worthy business fact?
6. How do you prove rollback rather than merely an exception?

## Resources

### Read

- [PostgreSQL: INSERT ... RETURNING](https://www.postgresql.org/docs/current/sql-insert.html)
- [PostgreSQL: transactions](https://www.postgresql.org/docs/current/tutorial-transactions.html)
- [PHP: PDO prepared statements](https://www.php.net/manual/en/pdo.prepared-statements.php)

### Go deeper

- [PostgreSQL: error codes](https://www.postgresql.org/docs/current/errcodes-appendix.html)
- [Full Stack Open Relational Databases](https://fullstackopen.com/en/relational_databases)

## You are done when

- [ ] Every request value reaches SQL as a bound parameter.
- [ ] Sort keys and directions come from a map I wrote, and a bogus one behaves as I decided.
- [ ] `limit` is bounded, and I know what the maximum is.
- [ ] One mapper produces both list items and detail responses.
- [ ] Missing detail is 404 and an empty collection is 200 with `[]`.
- [ ] An empty PATCH is rejected, and an explicit null is distinguishable from an absent field.
- [ ] At least one PDO error is classified narrowly and the rest are re-thrown.
- [ ] A forced second-write failure leaves neither row, proven from a separate session.
- [ ] The React client works against the real backend with no component changes.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/POSTGRESQL_DOCS.md`; `docs/dalt-fullstack/sources/FSO_RELATIONAL_DATABASES.md`
- Official sources: PostgreSQL INSERT/RETURNING, transaction, and error-code documentation; PHP PDO prepared-statement manual
- Versions: PostgreSQL official current documentation consulted 2026-08-14; PHP 8.4; DALT current `Database` implementation
- Consulted: 2026-08-14
- DALT files inspected: `framework/Core/Database.php`; `framework/Core/Response.php`; `framework/Core/Migration.php`
- Curriculum authority: `CURRICULUM.md` §15 FS05.3
- Laravel bridge: Laravel's query builder and `DB::transaction()` wrap this same PDO behaviour; writing the boundary by hand here makes the commit and rollback points visible before a helper hides them.
