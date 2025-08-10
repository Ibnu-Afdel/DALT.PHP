# DALT.PHP (learn-by-building PHP starter)

A tiny, beginner‑friendly PHP micro framework for learning by doing. SQLite by default, Postgres ready. Tailwind + DaisyUI + Vite out of the box. No heavy abstractions: plain PHP controllers, raw SQL, tiny helpers.

## Quick start

1) Install deps and env
```bash
composer install
npm install
cp .env.example .env
```

2) Build frontend
```bash
npm run dev   # during development
# or
npm run build # production
```

3) Database (SQLite by default)
```bash
php artisan migrate
```

4) Run the app
```bash
php artisan serve    # http://127.0.0.1:8000
```

## File structure (small and familiar)
```
public/                 # index.php (front controller)
routes/routes.php       # define routes
Http/controllers/       # your plain PHP controllers
Core/                   # tiny framework (Router, Database, Session, Middleware)
resources/
  views/                # PHP views
  js/app.js             # Vite entry (imports ../css/input.css)
  css/input.css         # Tailwind entry
config/
  app.php, database.php # env + db config (SQLite default)
database/migrations/    # migrations (Illuminate Database)
storage/logs/.gitkeep   # logs dir (kept empty by default)
artisan                 # minimal CLI: serve/migrate/etc
```

## Routes and controllers
- Add routes in `routes/routes.php`:
```php
$router->get('/', 'welcome.php');
```
- Create controllers in `Http/controllers/` (plain PHP):
```php
<?php
view('welcome.view.php');
```
- Views live in `resources/views/`.

## Features you’ll use right away
- {param} routes: `/users/{id}` becomes `$_GET['id']`
- CSRF: call `<?= csrf_field() ?>` inside forms and add `->only(['csrf'])` on POST/DELETE routes
- Validation: `Core\Validator` + throw `Core\ValidationException` (errors + old inputs are flashed)
- DB: `Core\App::resolve(Core\Database::class)->query($sql, $params)` (raw SQL, fetch with `find()` / `get()`)
- Debug: `APP_DEBUG=true` shows a simple stack trace; prod renders `resources/views/500.php`

## Database config
SQLite by default (zero setup). To switch to Postgres, edit `.env`:
```env
# SQLite (default)
DB_DRIVER=sqlite
DB_DATABASE=database/app.sqlite

# PostgreSQL
# DB_DRIVER=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_NAME=dalt_php_app
# DB_USERNAME=postgres
# DB_PASSWORD=
```

## CLI commands
```bash
php artisan serve                # start dev server
php artisan migrate              # run migrations
php artisan migrate:fresh        # drop sqlite DB and re-run migrations
php artisan make:migration posts # create a timestamped migration file
```

## Optional examples (not auto-installed)
- Auth demo lives under `examples/auth`.
- To install it into your app folders:
```bash
php artisan example:install auth
# then add the routes shown in routes/routes.php comments
```

## Frontend
- Vite + Tailwind + DaisyUI are prewired
- Entry is `resources/js/app.js` (which imports `../css/input.css`)
- Dev server autoloaded in views via `<?= vite('resources/js/app.js') ?>`

## Philosophy
- Learn PHP by reading and writing plain PHP files
- Keep the core small and explicit
- Avoid hiding how HTTP, sessions, and SQL work
- Once you’re comfortable, you can “graduate” to Laravel

## License
MIT
