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

```php
// In your framework's shutdown function or middleware:
try {
    $duration = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
    
    $db->query(
        'INSERT INTO request_log (method, uri, status_code, duration_ms) 
         VALUES (:method, :uri, :status, :duration)',
        [
            'method'   => $_SERVER['REQUEST_METHOD'],
            'uri'      => $_SERVER['REQUEST_URI'],
            'status'   => http_response_code(),
            'duration' => $duration
        ]
    );
} catch (\Exception $e) {
    // Log to a local file, but DO NOT rethrow or crash.
    // The user's request is already complete.
    error_log("Failed to insert request log: " . $e->getMessage());
}
```

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

header('Content-Type: application/json');
echo json_encode(['data' => $queries]);
```

This gives you a real-time dashboard of database health without logging into the server.

---

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
