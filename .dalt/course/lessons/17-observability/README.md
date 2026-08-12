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
