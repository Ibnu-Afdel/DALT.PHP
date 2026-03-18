# .dalt/ - Learning Platform Internals

This directory contains the DALT learning platform UI and assets. It's completely separate from the framework core.

## What's Inside

- `Http/controllers/` - Controllers for `/learn` routes (lesson viewer, challenge UI, verification API)
- `resources/` - Vue components, CSS, and views for the learning interface
- `routes/` - Platform routes (loaded automatically in `public/index.php`)
- `stubs/` - Code templates for authentication scaffolding
- `scripts/` - Setup scripts (post-create hooks)

## Removing the Learning Platform

Want to use DALT as a clean micro-framework? Just delete this directory:

```bash
rm -rf .dalt/ course/
```

The framework core (`framework/Core/`) has no dependencies on `.dalt/` or `course/`. The fallback logic in `Router.php` and `functions.php` gracefully handles missing `.dalt/` files.

## How the Fallback Works

The framework checks user code first, then falls back to `.dalt/`:

```php
// In Router.php
$controllerPath = base_path('app/Http/controllers/' . $route['controller']);
if (!file_exists($controllerPath)) {
    $controllerPath = base_path('.dalt/Http/controllers/' . $route['controller']);
}

// In functions.php
$viewPath = base_path('resources/views/' . $path);
if (!file_exists($viewPath)) {
    $viewPath = base_path('.dalt/resources/views/' . $path);
}
```

This means:
- Your code in `app/` always takes priority
- Platform code in `.dalt/` is a fallback
- Deleting `.dalt/` doesn't break your app

## For Contributors

If you're working on the learning platform itself, the frontend dependencies are managed at the project root (`package.json`), not here.
