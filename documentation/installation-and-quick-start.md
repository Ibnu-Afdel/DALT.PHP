# Installation and Quick Start

This page takes a new DALT project from an empty directory to a working route and database query. The commands assume PHP 8.2 or newer and Composer are installed.

## Create a project

For a new application, use Composer:

```bash
composer create-project ibnuafdel/daltphp my-project --stability=beta --remove-vcs
cd my-project
```

The project requires PHP `^8.2`. The create-project scripts copy `.env.example` to `.env` when needed and remove the framework repository metadata from the generated project. If you are working from a source checkout instead, run:

```bash
composer install
cp .env.example .env
```

Do not commit `.env`; it is environment-specific and may contain credentials.

## Start the application

```bash
php artisan serve
```

The development server listens on `http://127.0.0.1:8000` by default. Open that URL. A custom host and port are accepted:

```bash
php artisan serve 0.0.0.0 8080
```

`serve` uses PHP's built-in development server and exposes `public/` as the document root. It is for local development, not production hosting. If the requested port is busy, DALT tries the next available port, up to 50 ports ahead.

The default project includes built frontend assets, so Node.js is not required for the first request. Run `npm ci` and `npm run dev` only when changing the learning-platform frontend; run `npm run build` before distributing those changes.

## Add a route

Application routes live in `routes/routes.php`. The router accepts a controller path or a closure. Add this route below the existing root route:

```php
$router->get('/hello', fn (): string => 'Hello from DALT!');
```

With the server running, request it:

```bash
curl http://127.0.0.1:8000/hello
```

The response is:

```text
Hello from DALT!
```

Return a value from a handler. Do not call `echo`, `header()`, or `exit` for a normal route response. A returned string becomes HTML, an array becomes JSON, `null` is an empty response, and a `Core\Response` gives explicit control over status and headers.

Route registration is ordered. The first route matching both the HTTP method and URI wins. A placeholder such as `/posts/{id}` captures a string value and can be read with `Request::route('id')` inside a closure or controller.

## Run the first migration

The default `.env` uses SQLite at `database/app.sqlite`:

```dotenv
DB_DRIVER=sqlite
DB_DATABASE=database/app.sqlite
```

Run migrations explicitly:

```bash
php artisan migrate
```

Migrations are not run automatically on the first request. The command creates the migration bookkeeping table and applies pending `.sql` files from `database/migrations/`. The included migration creates `users`.

## Run the first query

Create `app/Http/controllers/users.php`:

```php
<?php

use Core\App;
use Core\Database;

$db = App::resolve(Database::class);

return $db->query(
    'SELECT id, name, email FROM users ORDER BY id'
)->get();
```

Register it in `routes/routes.php`:

```php
$router->get('/users', 'users.php');
```

Then request it:

```bash
curl http://127.0.0.1:8000/users
```

With no users inserted, the result is an empty JSON array: `[]`. The database connection is created when `Database` is resolved, not when the application boots. Queries use prepared statements; values belong in parameters rather than string concatenation:

```php
$user = $db->query(
    'SELECT id, name, email FROM users WHERE id = :id',
    ['id' => $id],
)->find();
```

`get()` returns a list, `find()` returns one row or `false`, and `findOrFail()` aborts with HTTP 404 when there is no row. DALT supports SQLite and PostgreSQL; MySQL is intentionally rejected.

## What to inspect when something fails

- `php artisan help` lists the available commands.
- A missing route is an HTTP 404. Check the method, URI, and registration order.
- `RuntimeException: Controller not found` means the controller path is not under `app/Http/controllers/` or an installed platform controller root.
- A database connection error usually means `.env` does not match the selected driver or the database service is unavailable.
- With `APP_DEBUG=true`, a 500 response includes the exception class, message, file, line, and trace. With `APP_DEBUG=false`, the response is the generic `Internal Server Error`; inspect `storage/logs/app.log`.

For the public deployment boundary, serve only `public/`, install with `composer install --no-dev --optimize-autoloader`, set `APP_ENV=production` and `APP_DEBUG=false`, configure HTTPS and secure session cookies, and read [SECURITY.md](../SECURITY.md).

## Next

- Read the [framework reference](framework-reference.md) when you need an API contract.
- Read the [architecture guide](architecture.md) to trace a request through the application.
- Work through the optional lessons under `.dalt/course/lessons/` if you want guided practice.
