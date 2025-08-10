# DALT.PHP — learn-by-building PHP starter

A tiny, beginner‑friendly PHP micro‑framework for learning by doing.
- Plain PHP controllers and views
- Raw SQL (SQLite by default; Postgres/MySQL ready)
- Minimal helpers (Router, DB, Session, CSRF)
- Optional Tailwind + DaisyUI + Vite + Alpine.js

## Create a new project
```bash
composer create-project ibnuafdel/daltphp my-app "^0.1@alpha"
cd my-app
```
What happens automatically:
- `.env` is created with SQLite defaults (no DB setup required)
- `storage/logs` is prepared
- If Node is available, post-create may install/build assets (optional)

## Run the app
```bash
php artisan serve
```
- Starts the PHP built-in server on a free port (8000, 8001, ...)
- Uses `public/router.php` so static files (e.g. `/favicon.svg`, `/css/*`, `/js/*`) are served correctly
- Keep the terminal open to see request logs

Optional (for HMR/live reload):
```bash
npm ci
npm run dev
```
If Vite dev server or manifest is not available, views fall back to static assets under `/js/app.js`, `/js/app.css`, or `/css/style.css`.

## File structure (small and familiar)
```
public/                 # index.php (front controller), router.php, favicon.svg
routes/routes.php       # define routes
Http/controllers/       # your plain PHP controllers
config/                 # app.php, database.php (SQLite default)
resources/
  views/
    layouts/            # head.php, nav.php, footer.php
    status/             # 403.php, 404.php, 500.php
  js/app.js             # Vite entry (imports ../css/input.css)
  css/input.css         # Tailwind entry
framework/
  Core/                 # router, db, session, middleware, helpers
  examples/             # auth demo (optional)
database/migrations/    # migrations (Illuminate Database)
storage/logs/.gitkeep   # logs dir
artisan                 # minimal CLI: serve/migrate/etc
```

## Routes and controllers
- Add routes in `routes/routes.php`:
```php
$router->get('/', 'welcome.php');
```
- Create a controller in `Http/controllers/welcome.php`:
```php
<?php
view('welcome.view.php');
```
- Views live in `resources/views/`.

### Route parameters and middleware
- Route params: `/posts/{id}` → available as `$_GET['id']`
- Methods: `$router->get()`, `post()`, `patch()`, `delete()`
- Middleware on a route:
```php
$router->post('/session', 'session/store.php')->only(['guest','csrf']);
```
Built-in keys: `csrf`, `auth`, `guest`.

## Database
SQLite by default (zero setup). To switch to PostgreSQL or MySQL, edit `.env`.

SQLite (default):
```env
DB_DRIVER=sqlite
DB_DATABASE=database/app.sqlite
```

PostgreSQL:
```env
DB_DRIVER=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=dalt_php_app
DB_USERNAME=postgres
DB_PASSWORD=
```

MySQL:
```env
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=dalt_php_app
DB_USERNAME=root
DB_PASSWORD=
```

Migrations (powered by illuminate/database):
```bash
php artisan migrate          # run migrations
php artisan migrate:fresh    # drop sqlite DB and re-run
php artisan make:migration posts
```

## Auth example (optional)
Install the demo (register/login/logout):
```bash
php artisan example:install auth
```
What it does:
- Copies demo controllers and views into your app
- Appends auth routes to `routes/routes.php`
- Shows Login/Register or Logout links in the header

## Debug and errors
- Set `APP_DEBUG=true` in `.env` for a simple stack trace on errors
- In production, errors are logged to `storage/logs/app.log` and a clean `resources/views/status/500.php` is shown
- Status pages: `resources/views/status/{403,404,500}.php`

## Frontend
- Vite + Tailwind + DaisyUI + Alpine.js are prewired
- Entry: `resources/js/app.js` (imports `../css/input.css`)
- In views, assets are included via `<?= vite('resources/js/app.js') ?>`
- No Node? It’s fine—static fallbacks are used if present under `/js` or `/css`

## Philosophy
- Learn PHP by reading and writing plain PHP files
- Keep the core small and explicit (no magic)
- Understand HTTP, sessions, and SQL fundamentals
- When you’re ready, graduate to full frameworks like Laravel

## License
MIT
