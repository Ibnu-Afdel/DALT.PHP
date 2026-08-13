# Lesson 17: Observability

> **Status: Completed** — The slow-query indexing challenge has been fixed and verified.

## You Can't Fix What You Can't See

Your application is running in production. Suddenly, page loads take 5 seconds. Users are complaining. CPU usage on the database server is at 100%.

What do you do?

If you don't have observability, you guess. You add random indexes. You restart the server.

With observability, you ask the database exactly which query is causing the problem, and it tells you. This lesson covers how to find slow queries and how to safely track request metrics in your PHP application.

## Learning Objectives

- Enable and query `pg_stat_statements` to find slow queries
- Read `EXPLAIN ANALYZE` output to verify if an index is missing
- Safely log request metrics in PHP without crashing the user's request

## Predict before reading

Before reading further, write down what you expect for each:

| Question | What do you expect? |
|---|---|
| `pg_stat_statements` shows a query with `calls = 50,000` and `mean_exec_time = 0.3ms`. Is this the query to investigate first? | ? |
| The same table shows a different query with `calls = 12` and `mean_exec_time = 400ms`. Which of the two is more likely missing an index, and which is more likely an N+1 problem? | ? |
| A request-logging middleware reads `http_response_code()` to record the status, instead of the `Response` object's own status. What value does it record for a request that fails with a 500? | ? |
| The logging `INSERT` inside the middleware throws (say, the table is locked). The middleware's `catch` block swallows it. Does the user's actual request still succeed? | ? |
| `CREATE INDEX idx_posts_user_status ON posts(user_id, status)` — without `CONCURRENTLY` — runs against a live table receiving writes. Does it block those writes while building? | ? |

The third is the one worth being wrong about — it produces a plausible-looking dashboard that is wrong for every failed request, with no error anywhere to notice.

---

## `pg_stat_statements`

`pg_stat_statements` is a built-in Postgres extension that records statistics about all SQL queries executed. It tracks how many times a query was run, the total time it took, and how much CPU/IO it consumed.

### Enabling it

In Docker Compose, you must tell Postgres to load the library on boot:

```yaml
  db:
    image: postgres:16-alpine
    command: postgres -c shared_preload_libraries=pg_stat_statements
```

Then, connect to your database and create the extension:

```sql
CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
```

### Finding Slow Queries

Run this to find the top 5 queries taking the most cumulative time:

```sql
SELECT 
    query, 
    calls, 
    total_exec_time, 
    mean_exec_time, 
    rows
FROM pg_stat_statements 
ORDER BY total_exec_time DESC 
LIMIT 5;
```

**What to look for:**
- High `mean_exec_time` (e.g., > 100ms) indicates a query that is fundamentally slow (probably missing an index).
- High `calls` with low `mean_exec_time` but high `total_exec_time` indicates an N+1 query problem in your PHP code.

---

## The Missing Index Problem

If `pg_stat_statements` points to a query like this:

```sql
SELECT id, title FROM posts WHERE user_id = $1 AND status = $2;
```

You need to figure out *why* it's slow. Run it through `EXPLAIN ANALYZE` in `psql`:

```sql
EXPLAIN ANALYZE SELECT id, title FROM posts WHERE user_id = 5 AND status = 'published';
```

If the output says `Seq Scan on posts` and the table has 1 million rows, Postgres is reading the entire table from disk.

The fix is to add an index. Because the query filters on both `user_id` and `status`, a composite index is best:

```sql
CREATE INDEX CONCURRENTLY idx_posts_user_status ON posts(user_id, status);
```

*(Note: `CONCURRENTLY` allows Postgres to build the index without locking the table for writes. Always use it in production on large tables.)*

---

## Request Logging in PHP

It's useful to log every HTTP request to a database table to monitor traffic, response times, and errors.

```sql
CREATE TABLE request_log (
    id BIGSERIAL PRIMARY KEY,
    method TEXT,
    uri TEXT,
    status_code INTEGER,
    duration_ms INTEGER,
    created_at TIMESTAMPTZ DEFAULT NOW()
);
```

### Safe Logging

If your logging query fails (e.g., the `request_log` table is locked), it should **never** crash the user's actual request.

Wrap the logging logic in a `try/catch` and swallow the exception.

The natural place for this in DALT is a middleware, because middleware sees the request on the way in **and** the response on the way out (Lesson 03):

```php
final class RequestLog implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        try {
            \Core\App::resolve(\Core\Database::class)->query(
                'INSERT INTO request_log (method, uri, status_code, duration_ms)
                 VALUES (:method, :uri, :status, :duration)',
                [
                    'method'   => $request->method(),
                    'uri'      => $request->path(),
                    'status'   => $response->status(),
                    'duration' => (int) ((microtime(true) - $start) * 1000),
                ],
            );
        } catch (\Throwable $e) {
            // Never let telemetry break the response the user is waiting on.
            error_log('Failed to insert request log: ' . $e->getMessage());
        }

        return $response;
    }
}
```

Two details worth naming:

- **Read the status from the `Response`, not from `http_response_code()`.** The global is only populated when `Response::send()` runs, which happens *after* middleware. Ask it too early and you get the default `200` for every request, including the failures you most wanted to see.
- **Catch `\Throwable`, not `\Exception`.** A `TypeError` in the logging block would otherwise escape and take down a request that had already succeeded.

---

## Building an Admin Dashboard Endpoint

You can expose these metrics to an admin panel by creating a specific endpoint:

```php
// GET /admin/slow-queries
$db = \Core\App::resolve(\Core\Database::class);

$queries = $db->query(
    'SELECT query, calls, mean_exec_time 
     FROM pg_stat_statements 
     ORDER BY mean_exec_time DESC 
     LIMIT 10'
)->get();

return ['data' => $queries];
```

This gives you a real-time dashboard of database health without logging into the server.

---

## Checkpoint

Answer from memory:

1. Explain what `Rows Removed by Filter` tells you in an `EXPLAIN ANALYZE` plan.
2. State why an index is a trade rather than a free win.
3. Explain why request logging belongs in middleware rather than at the end of a controller.
4. A logging middleware records `200` for every request including failures. Name the cause.
5. Explain why the logging block catches and swallows, and what it must never do.
6. Your table has ten rows and the plan shows a `Seq Scan`. Explain why that is not evidence of a problem.

## Your Task

Load the broken challenge:

```bash
php artisan challenge:start db-slow-queries
```

A migration file `database/migrations/004_add_indexes.sql` has been provided, but it is empty.

There are two controllers executing queries that filter on columns without indexes, resulting in sequential scans.

1. Check the controllers to see what columns they are filtering on in their `WHERE` clauses.
2. Update the migration file to add the missing indexes on those columns.

Verify:

```bash
php artisan challenge:verify
```

## Laravel bridge

Compared against Laravel 13.x ([laravel.com/docs/13.x/database#listening-for-query-events](https://laravel.com/docs/13.x/database) and [laravel.com/docs/13.x/telescope](https://laravel.com/docs/13.x/telescope), consulted 2026-08-13).

| Laravel 13.x | DALT |
|---|---|
| `DB::listen(function (QueryExecuted $query) { ... })` registered in a service provider's `boot()` — fires for every query, with `$query->sql`, `->bindings`, `->time` already parsed out | no query-event hook; the `RequestLog` middleware above times the *whole request*, not individual queries — there is nothing built in that observes SQL as it runs |
| **Telescope** — a first-party package with a `QueryWatcher` that records every query's raw SQL, bindings, and execution time, and flags anything over a configurable threshold (`'slow' => 100`, milliseconds) as slow, all with a dashboard UI included | `pg_stat_statements` does the equivalent job *inside Postgres itself* — aggregated by query shape, not per-request, and queried with plain SQL rather than viewed in a bundled UI |
| Telescope's watcher is opt-in per environment (typically local/staging only — it adds overhead and stores everything) | `pg_stat_statements` has near-zero overhead by design (it's the extension Postgres itself recommends leaving on in production) and this lesson's own admin endpoint is a deliberately minimal hand-built substitute for a Telescope-style dashboard |
| `DB::listen()` and Telescope both operate at the **query** level — one row per SQL statement executed | `pg_stat_statements` operates at the **query shape** level — identical queries with different literal values are grouped into one row with a `calls` counter, which is exactly what makes the N+1-vs-genuinely-slow distinction in "What to look for" above possible from aggregate stats alone |

The N+1 detection technique this lesson teaches — many `calls`, low `mean_exec_time`, high `total_exec_time` — is a Postgres-native version of what Telescope's request timeline view shows visually: the same query, run far more times than a human would expect for one page load. Telescope tells you by showing you the repeated rows; `pg_stat_statements` tells you by aggregating them into one row with a suspicious `calls` count. Recognizing the pattern from raw numbers, without the visualization, is the skill this lesson is actually after.
