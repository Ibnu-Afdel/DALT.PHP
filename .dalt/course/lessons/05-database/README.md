# Lesson 05: Database — One Connection, Prepared Statements, Three Fetches

## What you will be able to do

By the end of this lesson, you can:

- name the drivers DALT supports and predict what happens when you ask for another;
- explain why the connection is not opened during bootstrap;
- choose correctly between `find()`, `findOrFail()`, and `get()`;
- explain why a bound parameter can never become SQL, rather than saying "PDO escapes it";
- read the PDO attributes DALT sets and say what each one prevents;
- diagnose a query failure from the exception type alone.

## Recommended prerequisite

Complete [Lesson 01: Request Lifecycle](../01-request-lifecycle/README.md) first — the container and the lazy `Database` registration are introduced there.

## Two drivers, not three

DALT supports **SQLite** and **PostgreSQL**. There is no MySQL support:

```php
new Database(['driver' => 'mysql', ...]);
// InvalidArgumentException: Unsupported database driver: mysql
```

`Database::driver()` accepts only `sqlite` and `pgsql` and rejects everything else before a connection is attempted. SQLite is the zero-setup default for learning; PostgreSQL is what the Docker and SQL lessons from Lesson 06 onward build on.

## Predict before reading the source

| Call | Result |
|---|---|
| `$db->find()` before any `query()` | `LogicException: Run query() before fetching results.` |
| `find()` when no row matches | `false` |
| `findOrFail()` when no row matches | aborts with 404 |
| `get()` when no row matches | `[]` |
| `query('... id = :id', ['id' => '1 OR 1=1'])` | zero rows, not every row |
| reading an integer column | PHP `int`, not `"1"` |

## 1. Registration is not connection

`framework/Core/bootstrap.php` registers the database as a **singleton factory**:

```php
$container->singleton(
    Database::class,
    fn (Container $container): Database => DatabaseManager::create(
        $container->resolve(Config::class)->array('database.database'),
    ),
);
```

Nothing connects here. The closure runs the first time something resolves `Core\Database`:

```php
$db = App::resolve(Database::class);
```

Because it is a singleton, every later resolution returns that same instance and reuses one connection for the request. A page that never touches the database never opens one — worth remembering when a request renders fine but "the database is down".

## 2. The connection is configured deliberately

```php
$this->connection = new PDO($dsn, $username, $password, [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_STRINGIFY_FETCHES  => false,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
```

Each attribute prevents a specific class of bug:

| Attribute | Without it |
|---|---|
| `FETCH_ASSOC` | every row arrives duplicated under both numeric and named keys |
| `ERRMODE_EXCEPTION` | failures return `false` silently and surface much later as "call on null" |
| `STRINGIFY_FETCHES => false` | an `INTEGER` column arrives as `"1"`, and `===` comparisons quietly fail |
| `EMULATE_PREPARES => false` | PDO interpolates values client-side instead of sending a real prepared statement |

That last row is the important one, and section 4 returns to it.

A failed connection is wrapped, so callers see a consistent type:

```php
throw new RuntimeException("Database connection failed for {$driver}.", previous: $exception);
```

The original `PDOException` is attached as `previous` — read it for the real cause, because the wrapper deliberately does not put credentials or DSN details into the message.

## 3. Three ways to take results

`query()` prepares, executes, and returns `$this`, so fetching chains from it:

```php
// One row, or false
$user = $db->query('SELECT * FROM users WHERE email = :email', ['email' => $email])->find();

// One row, or 404
$post = $db->query('SELECT * FROM posts WHERE id = :id', ['id' => $id])->findOrFail();

// Every row, or []
$posts = $db->query('SELECT * FROM posts ORDER BY created_at DESC')->get();
```

Choosing between them is a decision about *whose fault a missing row is*:

- **`find()`** — absence is normal. "Is this email registered?" Handle the `false`.
- **`findOrFail()`** — absence means the URL is wrong. Showing post 999 that does not exist is a 404, and `findOrFail()` calls `abort()`, which defaults to 404.
- **`get()`** — an empty list is a normal answer, never an error.

Fetching before querying is a programming error, not a data condition, so it throws:

```php
$db->find();
// LogicException: Run query() before fetching results.
```

## 4. A bound parameter cannot become SQL

The usual explanation — "PDO escapes the value" — is wrong here, and the difference matters.

Because `EMULATE_PREPARES` is `false`, DALT sends the SQL and the values to the database **separately**. The server parses the statement first, with `:id` as a placeholder in the parsed plan, and only then receives the value. There is no string to escape and no moment when the value could be parsed as SQL.

Watch what that means concretely. This table has two rows:

```php
$evil = '1 OR 1=1';
$db->query('SELECT * FROM t WHERE id = :id', ['id' => $evil])->get();
// 0 rows
```

Zero, not two. The database looked for a row whose `id` equals the literal string `"1 OR 1=1"` and found none. The `OR 1=1` was never operative because it was never SQL.

Now the version to never write:

```php
$id = $_GET['id'];
$db->query("SELECT * FROM posts WHERE id = $id");   // NEVER
```

Here the value is concatenated into the statement *before* the database sees it, so `1 OR 1=1` genuinely becomes part of the query and returns every row.

The rule: user input belongs in the parameter array, never in the SQL string. Table and column names cannot be parameterised — if those must vary, validate them against an allowlist you wrote.

## 5. Configuration

`config/database.php` reads the environment with defaults:

```php
return [
    'database' => [
        'driver'   => env('DB_DRIVER', 'sqlite'),
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => (int) env('DB_PORT', 5432),
        'dbname'   => env('DB_NAME', 'dalt_php_app'),
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', ''),
        'charset'  => env('DB_CHARSET', 'utf8'),
        'database' => env('DB_DATABASE', base_path('database/app.sqlite')),
    ],
];
```

`database` is the SQLite file; the rest apply to PostgreSQL. Two behaviours are easy to miss:

- **Relative SQLite paths are resolved against the project root** by `DatabaseManager::normalizedConfig()`, so the file does not move when the working directory changes. `:memory:` and absolute paths are left alone.
- **PostgreSQL settings are validated before use.** `host`, `dbname`, and `charset` must match a conservative character pattern and `port` must be an integer from 1 to 65535, so a malformed value fails with a clear `InvalidArgumentException` instead of producing a confusing DSN.

`:memory:` is worth knowing for tests — it creates a private database that disappears when the connection closes.

## 6. Migrations are plain SQL

DALT migrations are `.sql` files in `database/migrations/`, applied in filename order:

```sql
-- database/migrations/001_create_users_table.sql
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

```bash
php artisan migrate
```

There is no PHP migration class and no `up()`/`down()` method — you write the DDL you would type into the database. The numeric prefix is what orders them, which Lesson 15 examines when the ordering is wrong.

## Debugging checklist

The exception type tells you the category before you read the message:

- **`InvalidArgumentException: Unsupported database driver`** — `DB_DRIVER` is not `sqlite` or `pgsql`.
- **`InvalidArgumentException` naming a config key** — a PostgreSQL setting failed validation; check `.env`.
- **`RuntimeException: Database connection failed`** — the server is unreachable or credentials are wrong; read the `previous` exception.
- **`LogicException: Run query() before fetching`** — a fetch call without a preceding `query()`.
- **`PDOException` mentioning a table** — migrations have not run.
- **"Cannot access array offset on bool"** — `find()` returned `false` and the result was used unchecked; use `findOrFail()` or test it.
- **A comparison that should match but does not** — you may be comparing across types; DALT returns integers as `int`.

## Trace exercise

Read in order and write down what each contributes:

1. `config/database.php`
2. `framework/Core/bootstrap.php` — the singleton registration
3. `framework/Core/DatabaseManager.php` — path normalization
4. `framework/Core/Database.php` — DSN building, attributes, fetch methods
5. `database/migrations/001_create_users_table.sql`

Then run:

```bash
composer test -- --filter='Database|Migration'
```

## Checkpoint

Close the source files and answer from memory:

1. Name the two supported drivers and the exception thrown for any other.
2. Explain why resolving `Database` twice in one request opens one connection, and when the first one opens.
3. Give a situation for each of `find()`, `findOrFail()`, and `get()`, justified by whose fault a missing row is.
4. Explain why `['id' => '1 OR 1=1']` returns zero rows, without using the word "escape".
5. State what `STRINGIFY_FETCHES => false` changes and one bug it prevents.
6. Explain why a table name cannot be passed as a bound parameter.

## Challenge: Broken Database

```bash
php artisan challenge:start broken-database
php artisan challenge:verify
php artisan challenge:stop
```

## Laravel bridge

Compared against Laravel 13.x ([laravel.com/docs/13.x/database](https://laravel.com/docs/13.x/database), consulted 2026-08-12).

Laravel's raw layer is the same idea:

```php
$users = DB::select('select * from users where active = ?', [1]);
```

| Laravel 13.x | DALT |
|---|---|
| `DB::select()` returns `stdClass` objects | `get()` returns associative arrays |
| positional `?` or named `:name` bindings | named `:name` bindings |
| MySQL, PostgreSQL, SQLite, SQL Server, MariaDB | SQLite and PostgreSQL |
| multiple named connections, read/write splitting, pooling | one connection per request |
| Eloquent ORM and the query builder above raw SQL | raw SQL only |
| `firstOrFail()` throwing `ModelNotFoundException` → 404 | `findOrFail()` calling `abort()` |

DALT stops at the raw layer on purpose. A query builder is a convenience over exactly this mechanism, and it is easier to reason about one once you have written the SQL it generates.

## Next steps

- **Lesson 06: Docker Basics** — running PostgreSQL alongside the app
- **Challenge: Broken Database** — find a query that returns the wrong rows
