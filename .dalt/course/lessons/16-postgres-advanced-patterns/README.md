# Lesson 16: Advanced PostgreSQL

> **Status: Completed** — The missing-RLS challenge has been fixed and verified.

## Scaling and Security at the Data Layer

As your application grows, doing everything in application code becomes risky and slow. If you build a multi-tenant SaaS, filtering by `tenant_id` in every single PHP query is a bug waiting to happen. If your logs table reaches 100 million rows, `DELETE FROM logs WHERE created_at < NOW() - INTERVAL '30 days'` will lock the table and bring down production.

Postgres has advanced features specifically designed to handle these scale and security issues at the database level.

## Learning Objectives

- Use **Row-Level Security (RLS)** to guarantee tenant isolation
- Use **Range Partitioning** to split massive tables without changing application code
- Understand **`pg_cron`** for scheduling tasks directly inside Postgres

---

## Row-Level Security (RLS)

In a multi-tenant application, users from "Company A" must never see data belonging to "Company B".

The standard (flawed) approach is to add `WHERE tenant_id = :id` to every query in PHP. If a developer forgets that `WHERE` clause on one API endpoint, data leaks.

**Row-Level Security (RLS)** enforces this at the Postgres level. Even if the PHP developer runs `SELECT * FROM posts`, Postgres will intercept it and only return the rows the current tenant is allowed to see.

### Step 1: Enable RLS

First, enable RLS on the table:

```sql
ALTER TABLE posts ENABLE ROW LEVEL SECURITY;
```

*(Note: If you enable RLS but create no policies, the default policy is DENY ALL. No rows will be visible.)*

### Step 2: Create a Policy

Create a policy that restricts access based on a session variable:

```sql
CREATE POLICY tenant_isolation ON posts
USING (tenant_id = current_setting('app.tenant_id', true)::INT);
```

The `USING` clause defines what rows can be read and updated.
- `current_setting('app.tenant_id', true)` reads a custom configuration variable. The `true` means "don't throw an error if the setting doesn't exist (return null)".
- `::INT` casts it to an integer to match the `tenant_id` column type.

### Step 3: Set the context in PHP

Before running queries for a specific tenant, tell Postgres who the current tenant is. Do this once per HTTP request (e.g., in your framework's middleware or base controller).

```php
$tenantId = 5; // e.g., determined from the logged-in user or the subdomain
$pdo = $db->getConnection();
$pdo->exec("SET app.tenant_id = {$tenantId}");
```

Now, when you run this query:

```php
$posts = $db->query('SELECT * FROM posts')->get();
```

Postgres automatically rewrites it to effectively be `SELECT * FROM posts WHERE tenant_id = 5`. The isolation is guaranteed by the database engine.

---

## Partitioning

When a table gets too large (e.g., tens of gigabytes), standard indexes become too large to fit in memory, and deleting old data causes massive I/O spikes.

**Table Partitioning** splits one large logical table into multiple smaller physical tables. Your application still queries the main table as if nothing changed.

### Range Partitioning (e.g., by month)

This is ideal for time-series data like logs, events, or posts.

Create the parent table and declare the partition key:

```sql
CREATE TABLE event_logs (
    id BIGSERIAL,
    event_type TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
) PARTITION BY RANGE (created_at);
```

*(Note: The partition key `created_at` must be part of the primary key constraint if you have one, which complicates things slightly. Often, partitioned event tables don't use primary keys at all.)*

Create the child tables (partitions) for specific date ranges:

```sql
CREATE TABLE event_logs_2024_01 PARTITION OF event_logs 
FOR VALUES FROM ('2024-01-01') TO ('2024-02-01');

CREATE TABLE event_logs_2024_02 PARTITION OF event_logs 
FOR VALUES FROM ('2024-02-01') TO ('2024-03-01');
```

When you `INSERT INTO event_logs`, Postgres automatically routes the row to the correct child table.

### Why Partitioning Matters

1. **Partition Pruning:** If you query `WHERE created_at BETWEEN '2024-01-15' AND '2024-01-20'`, Postgres entirely ignores `event_logs_2024_02` and scans only the January table. Run `EXPLAIN ANALYZE` to see this in action.
2. **Instant Deletion:** To delete data older than January, you don't run `DELETE`. You just drop the partition: `DROP TABLE event_logs_2024_01`. It happens instantly and reclaims disk space immediately with zero locking.

---

## `pg_cron`

If you need to run a task every hour (e.g., deleting expired sessions, refreshing materialized views), you usually set up a Linux cron job that calls a PHP script.

If the task is purely data manipulation, you can use the `pg_cron` extension to run the job entirely inside Postgres.

### Enabling `pg_cron`

In your `docker-compose.yml`, you must preload the library:
```yaml
  db:
    image: postgres:16-alpine
    command: postgres -c shared_preload_libraries=pg_cron
```

Then in Postgres:
```sql
CREATE EXTENSION IF NOT EXISTS pg_cron;
```

### Scheduling Jobs

Delete expired sessions every day at 3 AM:
```sql
SELECT cron.schedule('0 3 * * *', $$DELETE FROM sessions WHERE expires_at < NOW()$$);
```

Check job status:
```sql
SELECT * FROM cron.job_run_details ORDER BY start_time DESC LIMIT 5;
```

---

## End-of-Phase Project: Multi-Tenant Blog Platform

You have all the building blocks. Your final project is to build a multi-tenant blog platform (like Medium or Substack) using these advanced features.

### Schema Requirements

1. **Tenants:** `id`, `name`, `domain`
2. **Users:** `id`, `tenant_id`, `email`, `password_hash`
3. **Posts:** `id`, `tenant_id`, `user_id`, `title`, `body`, `search_vector`, `created_at`

### Database Architecture Rules

- **Must use RLS:** All queries to `users` and `posts` must be protected by Row-Level Security. Application code should never contain `WHERE tenant_id = ?`.
- **Must use Full-Text Search:** The `posts` table must use a `tsvector` generated column, indexed with GIN, for searching articles.
- **Migrations:** All schema changes must be written as numbered migration files in `database/migrations/`.
- **Connection Pooling:** Your `docker-compose.yml` must include `pgbouncer`. PHP must connect to PgBouncer, not directly to Postgres.

### API Requirements

- Middleware that extracts the `tenant_id` from the request (e.g., from an `X-Tenant-Domain` header) and executes `SET app.tenant_id = ?`.
- `POST /posts` — Create a post.
- `GET /posts` — List posts (paginated). Postgres RLS will automatically ensure they only see their tenant's posts.
- `GET /search?q=docker` — Full text search across the tenant's posts.
- `GET /export` — *(Bonus)* Stream the tenant's posts out as CSV.

---

## Your Task

Load the broken challenge:

```bash
php artisan challenge:start db-missing-rls
```

A controller `Http/controllers/tenant/posts.php` lists posts for a tenant. It *tries* to isolate data by fetching the tenant ID and doing `WHERE tenant_id = :id`. But if another developer modifies this query later and forgets the `WHERE` clause, data will leak.

You must implement Row-Level Security to protect the data at the DB level.

1. **Fix the Migration:** In `database/migrations/003_enable_rls.sql`, write the SQL to enable RLS on the `posts` table and create a policy that checks `tenant_id = current_setting('app.tenant_id')::INT`.
2. **Fix the Controller:** In `Http/controllers/tenant/posts.php`, execute `SET app.tenant_id = :id` before the `SELECT` query runs. 
3. **Remove the WHERE clause:** Remove `WHERE tenant_id = :id` from the controller's `SELECT` query to prove that RLS is doing the filtering.

Verify:

```bash
php artisan challenge:verify
```
