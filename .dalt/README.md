# .dalt/ - Learning Platform Internals

This directory contains the DALT learning platform UI and assets. It's completely separate from the framework core.

## What's Inside

- `Http/controllers/` - Controllers for `/learn` routes (lesson viewer, challenge UI, verification API)
- `resources/` - Vue components, CSS, and views for the learning interface
- `routes/` - Platform routes (loaded automatically in `public/index.php`)
- `stubs/` - Code templates for authentication scaffolding
- `scripts/` - Setup scripts (post-create hooks)

## Authentication Example

Install the small application-owned authentication example with:

```bash
php artisan example:install auth
php artisan migrate
```

The installer refuses existing destination files or conflicting literal routes and records hashes for the files it generated. Repeating install is safe. Use `php artisan example:update auth` only while the generated files are untouched, and `php artisan example:uninstall auth` to remove an unchanged example. Both commands stop if learner edits are present; `example:uninstall auth --force` explicitly discards those generated-file edits.

The example intentionally covers registration, login, logout, sessions, validation, password hashing, prepared queries, and CSRF. It is educational scaffolding, not a production authentication suite; rate limiting, password reset, verification, two-factor authentication, and account recovery are omitted.

## Removing the Learning Platform

Want to use DALT as a clean micro-framework? Run the supported cleanup command:

```bash
php artisan platform:remove
```

The framework core (`framework/Core/`) remains runnable when `.dalt/` is absent. `Core\Platform` discovers the optional directory once during bootstrap and owns its boot file, routes, controller roots, and view roots. A partially removed platform fails with a focused error instead of being loaded unpredictably.

## How the Fallback Works

The framework checks user code first, then falls back to `.dalt/`:

This means:
- Your code in `app/` always takes priority
- Platform code in `.dalt/` is a fallback
- Application routes are registered before platform routes
- Removing `.dalt/` with the supported command doesn't break your app

## For Contributors

If you're working on the learning platform itself, the frontend dependencies are managed at the project root (`package.json`), not here.
