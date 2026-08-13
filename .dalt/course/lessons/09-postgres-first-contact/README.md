# Lesson 09: PostgreSQL First Contact

## Why Postgres Instead of SQLite

DALT.PHP ships with SQLite by default — zero setup, one file, works everywhere. For learning and local development that's perfect. But SQLite has hard limits:

- One writer at a time (concurrent requests queue up)
- No real types — everything is stored loosely
- Missing features: window functions, full-text search, JSONB, row-level security
- Not what production systems use

PostgreSQL is what you'll run in production. It's the industry-standard open-source relational database — battle-tested, feature-rich, and extremely well-documented.

This lesson teaches you to connect to Postgres, explore it with the `psql` CLI, write raw SQL, and wire DALT.PHP to it. No ORMs. Direct SQL.

## Learning Objectives

By the end of this lesson, you will:
- Connect to a Postgres container using `psql`
- Know the essential `psql` meta-commands
- Understand Postgres types vs SQLite types
- Write CREATE TABLE, INSERT, SELECT, UPDATE, DELETE in Postgres SQL
- Know why parameterized queries are non-negotiable
- Configure DALT.PHP to use Postgres instead of SQLite
- Run migrations against Postgres with `php artisan migrate`

## Predict before reading

Before reading further, write down what you expect for each:

| Question | What do you expect? |
|---|---|
| A migration written with `INTEGER PRIMARY KEY AUTOINCREMENT` runs against Postgres. Does it fail? | ? |
| You insert a row with `$db->query(..., ['email' => $userInput])` where `$userInput` is `' OR '1'='1`. How many rows match? | ? |
| `POSTGRES_DB` in `.env` is changed on a stack that already has a data volume, and the stack is restarted (no `-v`). Does the new database exist? | ? |
| `\d users` is run before `php artisan migrate` has ever been executed. What do you see? | ? |
| A controller calls `http_response_code(404)` and then `return Response::json($data)`. What status code reaches the client? | ? |

The third and fifth are the ones worth being wrong about — both fail silently, with no error message pointing at the real cause.

## Connecting to Postgres

With your Compose stack running (`docker compose up -d`), connect to Postgres:

```bash
docker compose exec db psql -U postgres -d dalt_php_app
```

Breaking this down:
- `docker compose exec db` — run a command inside the `db` container
- `psql` — the Postgres interactive CLI
- `-U postgres` — connect as user `postgres`
- `-d dalt_php_app` — connect to the `dalt_php_app` database

You'll see the `psql` prompt:
```
dalt_php_app=#
```

You're now inside Postgres. Everything you type here runs as SQL or a psql meta-command.

## Essential `psql` Commands

These meta-commands start with `\` and are not SQL — they're psql shortcuts.

```sql
\l              -- list all databases
\c dalt_php_app -- connect to the "dalt_php_app" database
\dt             -- list all tables in current database
\d users        -- describe the users table (columns, types, constraints)
\d+ users       -- describe with more detail (indexes, triggers)
\q              -- quit psql
\?              -- help: list all meta-commands
\timing         -- toggle query timing (shows how long each query takes)
```

Try them now:
```sql
\dt             -- shows the users table once you have run php artisan migrate
\d users        -- see the column types
```

## Postgres Types vs SQLite Types

SQLite stores everything loosely — the column type is just a hint. Postgres enforces types strictly.

| SQLite | Postgres equivalent | Notes |
|---|---|---|
| `INTEGER PRIMARY KEY AUTOINCREMENT` | `BIGSERIAL PRIMARY KEY` | BIGSERIAL auto-increments |
| `TEXT` | `TEXT` | Same |
| `VARCHAR(255)` | `VARCHAR(255)` | Same |
| `DATETIME` | `TIMESTAMPTZ` | With timezone — always prefer this |
| `BOOLEAN` | `BOOLEAN` | True/false, not 0/1 |
| `REAL` | `NUMERIC` or `FLOAT8` | Numeric is exact; float is approximate |
| (none) | `JSONB` | Binary JSON — Postgres's killer feature |
| (none) | `TEXT[]` | Arrays of text |

**`BIGSERIAL PRIMARY KEY`** — this is the Postgres way to write an auto-incrementing integer primary key. DALT's migration system auto-converts SQLite's `INTEGER PRIMARY KEY AUTOINCREMENT` to this when `DB_DRIVER=pgsql`.

**`TIMESTAMPTZ`** — always store timestamps with timezone. `TIMESTAMP` without a timezone stores the raw value and causes bugs when servers change timezone.

## Writing Postgres SQL

### CREATE TABLE

```sql
CREATE TABLE IF NOT EXISTS posts (
    id          BIGSERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title       TEXT NOT NULL,
    body        TEXT,
    published   BOOLEAN NOT NULL DEFAULT false,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ
);
```

Key differences from SQLite:
- `BIGSERIAL` not `INTEGER PRIMARY KEY AUTOINCREMENT`
- `REFERENCES users(id) ON DELETE CASCADE` — foreign key with cascade
- `TIMESTAMPTZ` not `DATETIME`
- `NOW()` not `CURRENT_TIMESTAMP` (both work, `NOW()` is idiomatic Postgres)

### INSERT

```sql
INSERT INTO users (name, email, password)
VALUES ('Alice', 'alice@example.com', 'hashed_password_here');

-- Insert and return the new row
INSERT INTO users (name, email, password)
VALUES ('Bob', 'bob@example.com', 'hashed_password_here')
RETURNING id, name, email;
```

`RETURNING` — Postgres-specific, very useful. After inserting, get back the generated `id` without a second query.

### SELECT

```sql
-- All users
SELECT id, name, email FROM users ORDER BY created_at DESC;

-- One user by id
SELECT * FROM users WHERE id = 1;

-- Search by email (ILIKE = case-insensitive LIKE)
SELECT * FROM users WHERE email ILIKE '%alice%';

-- Count
SELECT COUNT(*) FROM users;
```

### UPDATE

```sql
UPDATE users
SET name = 'Alice Smith', updated_at = NOW()
WHERE id = 1;

-- Returns affected row count
UPDATE users SET published = true WHERE id = 1;
```

### DELETE

```sql
DELETE FROM users WHERE id = 1;

-- Safer: soft delete with a column instead
UPDATE users SET deleted_at = NOW() WHERE id = 1;
```

## Parameter Safety — The Non-Negotiable Rule

Never concatenate user input into a SQL string. Ever. This is SQL injection — the most common database vulnerability.

### What goes wrong

```php
// DANGEROUS — do not do this
$search = $_GET['search'];  // attacker sends: ' OR '1'='1
$users = $db->query("SELECT * FROM users WHERE email = '$search'")->get();
// Becomes: SELECT * FROM users WHERE email = '' OR '1'='1'
// Returns every user in the database
```

### The correct way — parameterized queries

```php
// SAFE — always do this
$search = $_GET['search'];
$users = $db->query(
    'SELECT * FROM users WHERE email ILIKE :search',
    ['search' => '%' . $search . '%']
)->get();
```

The `:search` placeholder is never interpreted as SQL. The database treats it as a value, not code. An attacker sending `' OR '1'='1` just searches for that literal string.

**DALT's `$db->query($sql, $params)` method always uses PDO prepared statements.** As long as you use `:placeholders` and pass values in the `$params` array, you're safe. The bug happens when you break out of this pattern with string concatenation.

## Configuring DALT.PHP for Postgres

In your project's `.env`, change the database settings:

```env
DB_DRIVER=pgsql
DB_HOST=db
DB_PORT=5432
DB_NAME=dalt_php_app
DB_USERNAME=postgres
DB_PASSWORD=secret
```

`DB_HOST=db` — inside Docker Compose, the hostname is the service name (`db`), not `localhost`.

If you're running Postgres locally (not Docker), use `DB_HOST=127.0.0.1`.

## Running Migrations Against Postgres

DALT's migration system supports both SQLite and Postgres. Your existing migration files in `database/migrations/` work against both.

```bash
# If running with Docker Compose
docker compose exec app php artisan migrate

# If running locally (with a local Postgres)
php artisan migrate
```

DALT reads the driver from the live connection and rewrites SQLite-only syntax before sending a migration to Postgres. Specifically it:

- turns `INTEGER PRIMARY KEY AUTOINCREMENT` into `BIGSERIAL PRIMARY KEY`;
- turns `DATETIME` into `TIMESTAMP`;
- strips any remaining bare `AUTOINCREMENT`;
- removes `PRAGMA` statements, which Postgres has no equivalent for.

It then re-checks the result, and if SQLite-only syntax somehow survives it refuses to run the migration rather than sending SQL Postgres will reject:

```
Migration contains SQLite-only syntax that cannot be converted for PostgreSQL: <file>
```

This is a deliberate convenience with a limit. It rescues the common SQLite idioms; it is not a general SQL translator, and anything beyond those four rules you write for the target yourself.

To start fresh (drops all tables and re-runs):
```bash
docker compose exec app php artisan migrate:fresh
```

## Making New Migrations

When you need a new table, create a migration file:

```bash
php artisan make:migration create_posts_table
```

This generates a timestamped `.sql` file in `database/migrations/`. When `DB_DRIVER=pgsql`, the template uses Postgres syntax automatically:

```sql
-- Migration: create_posts_table
-- Created: 2026-05-10

CREATE TABLE IF NOT EXISTS posts (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Edit this file to match your actual schema, then run `php artisan migrate`.

## Hands-On: Your First Raw SQL Session

Connect to your Compose Postgres container and run these queries manually. This is not a challenge — it's a practice session to build muscle memory.

```bash
docker compose exec db psql -U postgres -d dalt_php_app
```

Inside psql:
```sql
-- See what tables exist
\dt

-- See the users table structure
\d users

-- Insert a test user
INSERT INTO users (name, email, password)
VALUES ('Test User', 'test@example.com', 'placeholder')
RETURNING id, name, email;

-- List all users
SELECT id, name, email, created_at FROM users ORDER BY created_at DESC;

-- Count users
SELECT COUNT(*) AS total_users FROM users;

-- Update the user
UPDATE users SET name = 'Updated Name' WHERE email = 'test@example.com';

-- Verify the update
SELECT name, email FROM users WHERE email = 'test@example.com';

-- Delete the test user
DELETE FROM users WHERE email = 'test@example.com';

-- Confirm deletion
SELECT COUNT(*) FROM users WHERE email = 'test@example.com';

-- Quit
\q
```

Every one of these queries works in Postgres. By the time you've run them all once, the syntax stops feeling foreign.

## Viewing DALT's Users Table Structure

DALT's migration (`database/migrations/001_create_users_table.sql`) was written in SQLite syntax, but when `DB_DRIVER=pgsql` the system auto-converts it. After running migrations on Postgres, check what was created:

```bash
docker compose exec db psql -U postgres -d dalt_php_app -c "\d users"
```

You should see:
```
                         Table "public.users"
   Column   |            Type             | Nullable |      Default
------------+-----------------------------+----------+--------------------
 id         | bigint                      | not null | nextval(...)
 name       | character varying(255)      | not null |
 email      | character varying(255)      | not null |
 password   | character varying(255)      | not null |
 created_at | timestamp without time zone |          | CURRENT_TIMESTAMP
 updated_at | timestamp without time zone |          | CURRENT_TIMESTAMP
```

Notice `bigint` (DALT converted `INTEGER PRIMARY KEY AUTOINCREMENT` → `BIGSERIAL PRIMARY KEY`) and `timestamp` (from `DATETIME`).

## DALT's Database Layer — Quick Reference

```php
$db = \Core\App::resolve(\Core\Database::class);

// SELECT multiple rows
$users = $db->query('SELECT id, name FROM users ORDER BY name')->get();

// SELECT one row (returns false if not found)
$user = $db->query('SELECT * FROM users WHERE id = :id', ['id' => $id])->find();

// SELECT one row or abort 404
$user = $db->query('SELECT * FROM users WHERE id = :id', ['id' => $id])->findOrFail();

// INSERT
$db->query(
    'INSERT INTO users (name, email, password) VALUES (:name, :email, :pass)',
    ['name' => $name, 'email' => $email, 'pass' => $hashed]
);

// UPDATE
$db->query('UPDATE users SET name = :name WHERE id = :id', ['name' => $name, 'id' => $id]);

// DELETE
$db->query('DELETE FROM users WHERE id = :id', ['id' => $id]);

// Raw PDO for transactions
$pdo = $db->getConnection();
$pdo->beginTransaction();
// ... queries ...
$pdo->commit();
```

## Your Task

Load the broken controllers:

```bash
php artisan challenge:start db-first-queries
```

Two controller files will be added to your project. They have three bugs between them: SQL injection, a wrong column name, and a status code that never reaches the client.

Fix the bugs, then verify:

```bash
php artisan challenge:verify
```

## Common Mistakes

### "could not find driver" error
The `pdo_pgsql` PHP extension is not installed. In Docker, this is fixed by the `docker-php-ext-install pdo pdo_pgsql` line in your Dockerfile (Phase 1 challenge).

### "FATAL: database 'dalt_php_app' does not exist"
Postgres only creates the database named by `POSTGRES_DB` on the *first* boot of an empty data volume. If that variable changed after the volume already existed, the old database is still there and the new name was never created. Run `docker compose down -v` to discard the volume, then `docker compose up` again.

### "column user_id does not exist"
You're querying with the wrong column name. Use `\d tablename` in psql to see the actual column names.

### A 404 response arrives with status 200
The controller called `http_response_code(404)` and then returned data. `Response::send()` sets the status from the Response object, overwriting it. Return `Response::json($data, 404)` instead.

## Checkpoint

Answer from memory:

1. Name three SQLite idioms DALT rewrites for Postgres, and state what happens when a migration contains SQLite-only syntax it cannot rewrite.
2. Explain why `DB_HOST=db` works inside Compose but not on your host machine.
3. `?search=` is passed straight into a `LIKE` clause. Describe what an attacker can do, and where the `%` wildcards belong once you bind it.
4. A lookup returns nothing even though the row exists. Name the psql meta-command that would show you why.
5. A controller sets `http_response_code(404)` and returns an array. State the status the client actually receives and why.
6. Explain why `POSTGRES_DB` appears to be ignored when you change it on an existing stack.

## Summary

- `psql` connects you to Postgres interactively; `\dt`, `\d tablename`, `\q` are the essential meta-commands
- Postgres is strict about types: use `BIGSERIAL`, `TIMESTAMPTZ`, `TEXT`, `BOOLEAN`
- `RETURNING` gets back the inserted row without a second query
- **Always use parameterized queries** — never concatenate user input into SQL
- DALT switches to Postgres by changing `DB_DRIVER=pgsql` and running `php artisan migrate`
- `$db->query($sql, $params)->get()` / `->find()` / `->findOrFail()` are your three fetch methods

## Laravel bridge

Compared against Laravel 13.x ([laravel.com/docs/13.x/database](https://laravel.com/docs/13.x/database) and [laravel.com/docs/13.x/migrations](https://laravel.com/docs/13.x/migrations), consulted 2026-08-13).

| Laravel 13.x | DALT |
|---|---|
| `DB_CONNECTION=pgsql` in `.env`, one of five supported drivers (MySQL, Postgres, SQLite, SQL Server, MariaDB) | `DB_DRIVER=pgsql`, one of two (SQLite, Postgres) — same variable name for the same concept |
| `DB::select('select * from users where email = ?', [$email])` — positional `?` bindings by default | `$db->query('SELECT * FROM users WHERE email = :email', ['email' => $email])` — named `:placeholder` bindings only |
| the query builder and Eloquent generate parameterized SQL for you; raw bindings are the escape hatch | raw SQL and bindings are the *only* layer — see Lesson 05 |
| `Schema::create('posts', function (Blueprint $table) { $table->id(); ... })` — one PHP DSL, translated per-driver by the grammar class | one `.sql` file per migration, written once in SQLite syntax; `Migration::convertSqliteSqlToPgsql()` rewrites four specific idioms for Postgres (see "Running Migrations Against Postgres" above) — not a general translator |
| `$id = DB::table('users')->insertGetId([...])` | `INSERT ... RETURNING id` in the SQL itself — Postgres-native, no wrapper method needed |
| migrations run automatically in CI/deploy pipelines via `php artisan migrate --force` | `php artisan migrate` is always a manual step; nothing in DALT's boot path applies migrations for you (see "Migrations do not run by themselves" in Lesson 08) |

The biggest gap is conceptual, not syntactic: Laravel's query builder and Eloquent exist specifically so you rarely write raw SQL by hand. DALT keeps you at the raw-SQL layer through Lesson 17 on purpose — the parameter-binding discipline this lesson teaches is the same discipline Eloquent automates away, and it is worth doing by hand at least once.

## Next Steps

- **Challenge: db-first-queries** — fix three SQL bugs in the users controllers
- **Lesson 10: PostgreSQL Core** — JOINs, aggregations, indexes, transactions
