# .dalt/ - Learning Platform Internals

This directory contains the DALT learning platform UI and assets. It's completely separate from the framework core.

## What's Inside

- `Http/controllers/` - Controllers for `/learn` routes (lesson viewer, challenge UI, verification API)
- `resources/` - Vue components, CSS, and views for the learning interface
- `routes/` - Platform routes (loaded automatically in `public/index.php`)
- `stubs/` - Code templates for authentication scaffolding
- `scripts/` - Setup scripts (post-create hooks)

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
