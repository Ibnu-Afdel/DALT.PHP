# Lesson 15: PostgreSQL Reliability

> **Status: Completed** — The migration-ordering challenge has been fixed and verified.

## Keeping Your Data Safe

A database is only as good as its backup and its ability to evolve without breaking. In this lesson, we cover logical backups with `pg_dump`, how migrations actually work under the hood, and how to design a reliable background worker using Postgres.

## Learning Objectives

- Backup and restore a Postgres database using `pg_dump` and `psql`
- Understand how "migrations as code" are tracked in the database
- Fix broken migration states
- Build a concurrent job queue using `FOR UPDATE SKIP LOCKED`

## Predict before reading

Before reading further, write down what you expect for each:

| Question | What do you expect? |
|---|---|
| `002_create_posts.sql` is not wrapped in `BEGIN`/`COMMIT`, has no `IF NOT EXISTS`, and fails halfway through on a syntax error. You fix the syntax and run `php artisan migrate` again. Does it succeed cleanly? | ? |
| Three worker processes run `SELECT * FROM jobs WHERE status = 'pending' LIMIT 1` at the same instant, with no locking at all. How many distinct jobs get picked up? | ? |
| Same query, but with `FOR UPDATE SKIP LOCKED` added, and worker 1 already holds the lock on job #1. Does worker 2 wait for it, or move on? | ? |
| A worker crashes (process killed) between locking a job with `FOR UPDATE` and committing the `UPDATE ... SET status = 'processing'`. What happens to the lock? | ? |
| `pg_dump` runs against a live database while writes are still happening. Does the resulting backup contain a consistent snapshot, or a corrupted mix of before/after states? | ? |

The first is the one worth being wrong about — it is the exact failure mode "Dealing with Migration Failures" below exists to explain, and the fix is not "just re-run it."

---

## Backups with `pg_dump`

Docker volumes keep data safe across container restarts, but if you drop a table or the underlying server disk dies, the volume is gone. You need logical backups (SQL dumps) exported to another location.

### Manual Backup

Run `pg_dump` against the running Postgres container:

```bash
docker exec -it myapp-db-1 pg_dump -U postgres dalt > backup_2024.sql
```

This generates a plain-text SQL file containing all `CREATE TABLE`, `INSERT`, and `CREATE INDEX` statements needed to recreate the database from scratch.

### Manual Restore

To restore, pipe the SQL file into `psql`:

```bash
cat backup_2024.sql | docker exec -i myapp-db-1 psql -U postgres dalt
```
*(Notice the use of `-i` instead of `-it` because we are piping data in, not opening an interactive terminal.)*

### Automated Backups in Compose

In production, you don't run commands manually. You add a backup service to `docker-compose.yml`:

```yaml
  backup:
    image: postgres:16-alpine
    volumes:
      - ./backups:/backups
    environment:
      - PGHOST=db
      - PGUSER=postgres
      - PGPASSWORD_FILE=/run/secrets/db_password
    secrets:
      - db_password
    # Run pg_dump every night at 3 AM
    command: >
      sh -c "while true; do
        sleep $$(expr 86400 - $$(date +%s) % 86400 + 10800);
        pg_dump -Fc -f /backups/dump_$$(date +%Y%m%d).custom dalt;
      done"
```
*(Note: In a real system you'd use a dedicated cron container like `ofelia` or push to S3, but this shows the concept.)*

---

## Migrations as Code

If you manually run `CREATE TABLE` in production, you will eventually forget to run it on staging, or another developer won't have it locally.

**Migrations as code** solves this. You write SQL files with numbered prefixes, and a script runs them in order.

### How DALT tracks migrations

DALT.PHP has a built-in migration system (`php artisan migrate`). Here is exactly what it does:

1. It ensures a table named `migrations` exists:
   ```sql
   CREATE TABLE IF NOT EXISTS migrations (
       id SERIAL PRIMARY KEY,
       migration VARCHAR(255) NOT NULL,
       batch INTEGER NOT NULL,
       executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```
2. It looks in `database/migrations/` for all `.sql` and `.php` files.
3. It queries the `migrations` table: `SELECT migration FROM migrations`.
4. It subtracts the executed migrations from the files on disk.
5. It runs the missing files in alphabetical order.
6. After each file succeeds, it inserts a record into the `migrations` table.

### Dealing with Migration Failures

If `002_create_posts.sql` has a syntax error halfway through, it fails.
- If it wasn't wrapped in a transaction, the first half of the file executed, but the file name wasn't recorded in the `migrations` table.
- When you run `php artisan migrate` again, it will try to run `002` from the beginning, and fail because "table already exists".

**The Fix:**
Always write idempotent migrations (`CREATE TABLE IF NOT EXISTS`). If you can't, wrap the migration in a transaction:
```sql
BEGIN;
ALTER TABLE users ADD COLUMN age INT;
ALTER TABLE users DROP COLUMN dob;
COMMIT;
```

---

## Job Queues in Postgres (`SKIP LOCKED`)

When you need to send 1,000 emails, you don't do it in the HTTP request. You insert 1,000 rows into a `jobs` table, return 200 OK, and let a background worker process them.

### The Problem: Race Conditions

If you have 3 worker containers running, and they all do this:

```sql
-- Worker 1, 2, and 3 run this at the exact same millisecond:
SELECT * FROM jobs WHERE status = 'pending' LIMIT 1;
```

They will all select Job ID #1. All three workers will send the same email.

### The Solution: `FOR UPDATE SKIP LOCKED`

Postgres provides a mechanism specifically for building queues.

```sql
SELECT * FROM jobs 
WHERE status = 'pending' AND available_at <= NOW()
ORDER BY created_at ASC
FOR UPDATE SKIP LOCKED
LIMIT 1;
```

- `FOR UPDATE`: Locks the returned row so no other transaction can modify it.
- `SKIP LOCKED`: If another worker already locked Job #1, don't wait for the lock to release — just skip it and grab Job #2 immediately.

This allows you to run 50 concurrent workers safely against a single Postgres table.

---

## End-of-Phase Project: PostgreSQL Job Queue

You're going to build the queue described above.

### Step 1: The Schema

Create `database/migrations/004_create_jobs_table.sql`:

```sql
CREATE TABLE IF NOT EXISTS jobs (
    id BIGSERIAL PRIMARY KEY,
    type TEXT NOT NULL,
    payload JSONB NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    attempts INTEGER NOT NULL DEFAULT 0,
    available_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    locked_at TIMESTAMPTZ,
    failed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Index for the worker query
CREATE INDEX IF NOT EXISTS idx_jobs_worker ON jobs (status, available_at, created_at);
```

### Step 2: The API (Producer)

Create endpoints in `routes/routes.php` and their controllers:

- `POST /jobs` — Insert a new job. Payload should be JSON.
  ```php
  $db->query(
      'INSERT INTO jobs (type, payload, available_at) VALUES (:type, :payload, NOW() + interval \'1 second\' * :delay)',
      ['type' => 'email', 'payload' => json_encode(['to' => 'test@test.com']), 'delay' => 0]
  );
  ```

- `GET /jobs/stats` — Return counts grouped by status.
  ```sql
  SELECT status, COUNT(*) FROM jobs GROUP BY status;
  ```

### Step 3: The Worker (Consumer)

Create a CLI script `worker.php` in the project root. This script loops forever, grabbing jobs and processing them.

```php
<?php
// worker.php
require __DIR__ . '/framework/bootstrap.php';
$db = \Core\App::resolve(\Core\Database::class);

echo "Worker started...\n";

while (true) {
    $pdo = $db->getConnection();
    $pdo->beginTransaction();

    try {
        // 1. Grab and lock exactly one job
        $job = $db->query(
            "SELECT * FROM jobs 
             WHERE status = 'pending' AND available_at <= NOW()
             ORDER BY created_at ASC
             FOR UPDATE SKIP LOCKED
             LIMIT 1"
        )->find();

        if (!$job) {
            // No jobs available. Rollback the (empty) transaction, sleep, and try again.
            $pdo->rollBack();
            sleep(2);
            continue;
        }

        // 2. Mark it as processing
        $db->query(
            "UPDATE jobs SET status = 'processing', locked_at = NOW(), attempts = attempts + 1 WHERE id = :id",
            ['id' => $job['id']]
        );
        $pdo->commit(); // Release the lock so other workers don't block

        echo "Processing job {$job['id']} of type {$job['type']}...\n";

        // 3. DO THE ACTUAL WORK HERE (e.g., send the email)
        $payload = json_decode($job['payload'], true);
        sleep(1); // Simulate work

        // 4. Mark as completed
        $db->query(
            "UPDATE jobs SET status = 'completed' WHERE id = :id",
            ['id' => $job['id']]
        );
        echo "Job {$job['id']} completed.\n";

    } catch (\Exception $e) {
        // 5. If it fails, mark as failed
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (isset($job)) {
            $db->query(
                "UPDATE jobs SET status = 'failed', failed_at = NOW() WHERE id = :id",
                ['id' => $job['id']]
            );
        }
        echo "Job failed: " . $e->getMessage() . "\n";
    }
}
```

Run it in your terminal: `php worker.php`
In another terminal, curl `POST /jobs` and watch the worker pick it up.

### Step 4: Dockerize the Worker

Add the worker to `docker-compose.yml`:

```yaml
  worker:
    build: .
    command: php worker.php
    restart: on-failure
    depends_on:
      db:
        condition: service_healthy
```

Now you have a production-grade, concurrently-safe job queue backed entirely by Postgres.

---

## Checkpoint

Close the source files and answer from memory:

1. Explain why `pg_dump` produces a consistent snapshot even while writes are happening, in one sentence.
2. A migration fails halfway through, uncommitted, and its filename never reaches the `migrations` table. Explain what happens on the next `php artisan migrate`, and the two ways to prevent the failure mode.
3. Explain what `FOR UPDATE` locks, and what `SKIP LOCKED` changes about waiting for that lock.
4. A worker crashes after `FOR UPDATE` but before its `UPDATE ... SET status = 'processing'` commits. State what happens to the row when the connection drops.
5. Three workers query `SELECT ... LIMIT 1` with no locking at all. State how many of them can end up processing the same row, and name the one clause that fixes it.
6. Explain why the worker script commits immediately after marking a job `'processing'`, rather than holding the transaction open for the entire job.

## Your Task

Load the broken challenge:

```bash
php artisan challenge:start db-migrations-disorder
```

You have two migration files, but they are numbered incorrectly. The `posts` table (which has a foreign key to `users`) is trying to run *before* the `users` table is created.

Additionally, the `posts` table is using SQLite `AUTOINCREMENT` syntax instead of Postgres `BIGSERIAL`.

1. Move the users-table SQL into `001_create_users_table.sql` so `users` runs first.
2. Move the posts-table SQL into `002_create_posts_table.sql` so it runs after `users`.
3. Fix the Postgres syntax in the posts migration by replacing `INTEGER PRIMARY KEY AUTOINCREMENT` with `BIGSERIAL PRIMARY KEY`.

Verify:

```bash
php artisan challenge:verify
```

## Laravel bridge

Compared against Laravel 13.x ([laravel.com/docs/13.x/migrations](https://laravel.com/docs/13.x/migrations) and [laravel.com/docs/13.x/queues](https://laravel.com/docs/13.x/queues), consulted 2026-08-13).

| Laravel 13.x | DALT |
|---|---|
| no first-party `pg_dump` wrapper — teams reach for the hosting platform's snapshot feature or a package (e.g. Spatie's backup tool) | `pg_dump` / `psql` run directly, by hand or from a small Compose `backup` service — no package layer |
| `migrations` table tracked the same way: filename, batch number, timestamp — `php artisan migrate:status` shows pending vs. run | DALT's own `migrations` table (see "How DALT tracks migrations" above) is deliberately the same shape |
| `--step` runs each migration file as its own batch so a partial failure can be rolled back one file at a time; `--pretend` previews the SQL without running it | migrations run in filename order with no per-file batching; "Dealing with Migration Failures" above is the manual version of the same problem `--step` exists to contain |
| `php artisan make:queue-table` scaffolds a `jobs` table for the `database` queue driver; the query builder's own `lockForUpdate()` method (`->lockForUpdate()`, generating `SELECT ... FOR UPDATE`) is the documented tool for this exact "claim a row safely" pattern | this lesson's `jobs` table is hand-designed, and the worker uses `FOR UPDATE SKIP LOCKED` specifically — the `SKIP LOCKED` half is what lets *multiple* workers stay busy instead of queueing behind each other's locks |
| `ShouldBeUnique` jobs, `--timeout`/`retry_after` tuning, and automatic retries are queue-driver features you configure, not write | `attempts`, `failed_at`, and the retry decision are columns and `catch` logic you write yourself in `worker.php` |

The `FOR UPDATE` vs. `FOR UPDATE SKIP LOCKED` gap is worth sitting with: `lockForUpdate()` alone, under concurrent workers, still serializes them one at a time on lock acquisition — a second worker blocks until the first releases the row rather than moving on. `SKIP LOCKED` — what this lesson's worker actually uses — is the difference between "workers wait their turn" and "workers stay busy," and the query builder has no dedicated method for it; you'd still drop to `whereRaw`/`DB::raw` to add it, the same raw-SQL escape hatch this whole course stays inside.
