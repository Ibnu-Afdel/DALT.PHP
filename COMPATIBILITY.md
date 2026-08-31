# DALT.PHP v1 compatibility policy

DALT.PHP follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html). This file
records exactly what `1.x` promises, so that "we did not break anything" is a checkable
statement rather than an intention.

- **Patch** (`1.0.x`) — bug fixes, security fixes, documentation, course content.
- **Minor** (`1.x.0`) — new capabilities that do not break anything listed below.
- **Major** (`2.0.0`) — required for any change that breaks a covered contract.

Everything in **[What v1 covers](#what-v1-covers)** is a promise. Everything in
**[What v1 does not cover](#what-v1-does-not-cover)** may change in a minor release, and
you should not build on it expecting otherwise.

---

## Supported platforms

| | Supported in 1.x |
|---|---|
| PHP | 8.2, 8.3, 8.4, 8.5 |
| Required extensions | `pdo`, plus the PDO driver for your database |
| Databases | SQLite (via `pdo_sqlite`, the default), PostgreSQL 16+ (via `pdo_pgsql`; the course labs run `postgres:16-alpine`), and MySQL 8.0+ (via `pdo_mysql`) |
| Composer | 2.x |
| Node.js | not required to run a project. Node 20.19+ or 22.12+ only to rebuild frontend assets |
| Operating systems | Linux is tested. macOS is expected to work. Windows is untested; use WSL2 |

Adding support for a newer PHP version is a minor release. Dropping a PHP version that
1.x supports is a major release.

`DB_DRIVER` accepts exactly `sqlite`, `pgsql`, and `mysql`. Any other value throws
`InvalidArgumentException: Unsupported database driver: <value>` before connecting.
That rejection is itself part of the contract — code may rely on the failure being loud.

---

## What v1 covers

### Public framework classes

These live under the `Core\` namespace in `framework/Core/` and are autoloaded by
Composer. Their public method names, parameter order, required parameters, and return
types are stable in 1.x.

| Class | Public surface |
|---|---|
| `Core\App` | `setContainer`, `container`, `containerOrNull`, `forgetContainer`, `bind`, `singleton`, `instance`, `resolve` |
| `Core\Container` | `bind`, `singleton`, `instance`, `has`, `resolved`, `resolve`, `call` |
| `Core\Router` | `add`, `get`, `post`, `patch`, `put`, `delete`, `options`, `only`, `route`, `previousUrl` |
| `Core\Route` | `method`, `uri`, `handler`, `setMiddleware`, `middleware` |
| `Core\Request` | `capture`, `method`, `path`, `query`, `input`, `all`, `setRouteParameters`, `route`, `server` |
| `Core\Response` | `html`, `text`, `json`, `redirect`, `fromHandlerResult`, `fromHandler`, `content`, `status`, `headers`, `withContent`, `withHeader`, `send` |
| `Core\Session` | `start`, `active`, `regenerate`, `put`, `get`, `exists`, `has`, `flash`, `now`, `getFlash`, `keep`, `reflash`, `ageFlashData`, `unflash`, `forget`, `flush`, `destroy` |
| `Core\Database` | `getConnection`, `query`, `find`, `findOrFail`, `get` |
| `Core\DatabaseManager` | `getDatabase`, `create` |
| `Core\Migration` | `createMigrationsTable`, `hasRun`, `markAsRun`, `getNextBatch`, `runMigrations` |
| `Core\Authenticator` | `attempt`, `login`, `user`, `id`, `check`, `guest`, `rememberIntended`, `intended`, `logout` |
| `Core\Config` | `load`, `get`, `string`, `integer`, `boolean`, `array` |
| `Core\View` | `render`, `resolve` |
| `Core\Validator` | `string`, `email` |
| `Core\ExceptionHandler` | `report`, `render` |
| `Core\Platform` | `discover`, `isInstalled`, `viewRoots`, `controllerRoots`, `boot`, `registerRoutes` |
| `Core\HttpException`, `Core\ValidationException` | thrown types, and `ValidationException::throw` |
| `Core\Middleware\MiddlewareInterface` | `handle(Request $request, Closure $next)` |
| `Core\Middleware\Middleware` | `run`, and the built-in `auth`, `guest`, `csrf` aliases |

Adding a method, or adding an optional parameter at the end of an existing signature, is
a minor release. Removing or renaming anything above, reordering parameters, making an
optional parameter required, or narrowing a return type is a major release.

### Global helper functions

Defined in `framework/Core/functions.php` and available everywhere:

`dd`, `abort`, `authorize`, `base_path`, `env`, `config`, `view`, `redirect`, `old`,
`vite`, `vite_is_dev_server_running`, `csrf_token`, `csrf_field`, `app_log`.

### Routing behavior

- The verbs `get`, `post`, `patch`, `put`, `delete`, and `options` are registered through
  `$router` in `routes/routes.php`.
- A handler is either a `Closure` or a controller path string resolved relative to the
  registered controller roots.
- `{name}` matches exactly one path segment and becomes a route parameter readable with
  `$request->route('name')`.
- A pattern ending in `/{*}` is a **prefix fallback**: it matches the bare prefix and
  everything beneath it, across segment boundaries. This is what an SPA route table
  (`/app/{*}`) and an API preflight catch-all (`/api/{*}`) depend on.
- Routes are matched in registration order. A literal route registered before a
  parameterised one wins. This ordering is intentional and will not change in 1.x.
- An unmatched path produces a 404 response through `ExceptionHandler`.

### Response behavior

- `Response::json()` sets `Content-Type: application/json`; `html()` and `text()` set
  their corresponding types; `redirect()` defaults to status 302.
- A controller may return a `Response`, or echo output and return nothing; both are
  normalised by `Response::fromHandlerResult()`.
- `APP_DEBUG=true` renders a detailed error page. `APP_DEBUG=false` renders the generic
  status page and does not leak stack traces, file paths, or query text. That boundary is
  a security contract, not a presentation detail.

### Configuration

Files in `config/` return arrays and are read through `config('file.key')`:

- `config/app.php` — application name, environment, debug flag, URL.
- `config/database.php` — `driver`, `host`, `port`, `dbname`, `username`, `password`,
  `charset`, `database`.
- `config/session.php` — session name, lifetime, path, domain, secure-cookie and
  SameSite settings.

Environment variables documented in `.env.example` are covered: `APP_NAME`, `APP_ENV`,
`APP_DEBUG`, `APP_URL`, `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_NAME`,
`SESSION_PATH`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`,
`DB_DRIVER`, `DB_DATABASE`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`,
`DB_PASSWORD`, `DB_CHARSET`.

Removing a key, or changing a default in a way that changes behavior for an existing
`.env`, is a major release.

### Artisan commands

`php artisan <command>`. The command names, their arguments, and their success exit code
of `0` are stable:

`help`, `serve [host] [port]`, `dev`, `migrate`, `migrate:fresh [--force]`,
`make:migration <name>`, `test`, `verify <challenge>`, `dalt:test [challenge]`,
`challenge:list`, `challenge:start <name> [--force]`, `challenge:verify`,
`challenge:reset`, `challenge:stop`, `platform:status`, `platform:clean [--force]`,
`platform:remove [--force]`, `example:install auth`, `example:update auth`,
`example:uninstall auth`.

`serve` takes **positional** host and port (`php artisan serve 0.0.0.0 8080`), not
flags. Commands that require the learning platform exit `1` with an explanatory message
when `.dalt/` is absent, rather than failing obscurely.

Human-readable output text is **not** covered — do not parse it. Exit codes are.

### Migrations

- Migrations are plain `.sql` files in `database/migrations/`, applied in filename order.
- Applied migrations are recorded in a `migrations` table and are not re-run.
- `make:migration` creates a timestamped file; `migrate` applies what is pending;
  `migrate:fresh` drops and reapplies everything.

### The auth example lifecycle

`php artisan example:install auth` generates an authentication example into application
space. It is repeatable, refuses to overwrite application files or conflicting literal
routes, and preserves learner modifications by default. `example:update auth` refreshes
only untouched generated files; `example:uninstall auth` removes them. Generated code is
yours after installation — DALT will not silently rewrite a file you have edited.

### `platform:remove` — the framework/course boundary

This is the load-bearing promise of the project. After
`php artisan platform:remove --force`:

- `.dalt/` is gone.
- Every application file remains: `app/`, `config/`, `database/`, `public/`, `routes/`,
  `resources/`, `storage/`, `artisan`, and an installed auth example.
- The framework test suite passes.
- `/` returns HTTP 200.

Nothing in `framework/`, `config/`, `public/`, `artisan`, or the root `tests/` may
acquire a dependency on a course artifact in 1.x. If that ever becomes untrue, it is a
bug to be fixed in a patch release, not a contract to be renegotiated.

---

## What v1 does not cover

These may change in any minor release. Do not build on them.

- **Everything inside `.dalt/`** — the learning platform, its Vue components, its own
  Vite build, its routes, its views, its progress store, and its internal PHP classes.
  Course content is expected to change continuously.
- **Course content itself** — lesson text, lesson IDs, ordering, counts, challenge names,
  challenge fixtures, and build specifications. Lessons will be added, split, merged, and
  rewritten within 1.x.
- **Any class, method, property, or function marked `@internal`, or declared `private`
  or `protected`.**
- **Human-readable CLI and error-page text**, including wording, colour, and layout.
- **Generated frontend asset filenames and content hashes** under `public/build/`.
- **The root frontend toolchain** — the exact versions of Vite, Vitest, ESLint,
  TypeScript, React, and Tailwind in `package.json`, and their configuration files.
  Security updates to these will land in patch releases.
- **Database schema of the bundled example tables**, beyond the `migrations` table.
- **`documentation/` file paths and headings.**

---

## Deprecation

If something covered above must change, 1.x will keep the old behavior working and
document the replacement in `CHANGELOG.md` under `Deprecated`. Removal waits for 2.0.

## Security exception

A change required to fix a security vulnerability may break a covered contract in a
patch release. When that happens it is called out explicitly in `CHANGELOG.md` under
`Security`, with the reason and the upgrade path. See [SECURITY.md](SECURITY.md).
