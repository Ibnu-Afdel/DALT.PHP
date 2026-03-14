# Lesson 01: HTTP Request Lifecycle

## Overview

Understanding the HTTP request lifecycle is fundamental to debugging backend applications. This lesson traces how a single HTTP request flows through the DALT.PHP framework from start to finish.

## Learning Objectives

By the end of this lesson, you will understand:
- How HTTP requests enter the application
- The role of the front controller pattern
- How the framework bootstraps itself
- The order of operations in request handling
- Where to look when debugging request issues

## The Request Journey

### 1. Entry Point: `public/index.php`

Every HTTP request starts here. This is called the "front controller" pattern.

**What happens:**
```php
// Define base path
const BASE_PATH = __DIR__ . '/../';

// Load Composer autoloader
require BASE_PATH . 'vendor/autoload.php';

// Start session
session_start();

// Load helper functions
require BASE_PATH . 'framework/Core/functions.php';

// Bootstrap the framework
require base_path('framework/Core/bootstrap.php');
```

**Key Point:** No matter what URL you visit (`/`, `/posts`, `/login`), this file always runs first.

### 2. Framework Bootstrap: `framework/Core/bootstrap.php`

The bootstrap process initializes the framework:

```php
// Load environment variables from .env
$dotenv = Dotenv\Dotenv::createImmutable(base_path(''));
$dotenv->safeLoad();

// Load configuration
$appConfig = require base_path('config/app.php');
$dbConfig = require base_path('config/database.php');

// Create dependency injection container
$container = new Container();

// Bind database to container
$container->bind('Core\Database', function () use ($dbConfig) {
    return DatabaseManager::create($dbConfig['database']);
});

// Make container available globally
App::setContainer($container);
```

**Key Point:** After bootstrap, the database connection is ready and available via `App::resolve(Database::class)`.

### 3. Router Creation and Route Loading

```php
// Create router instance
$router = new \Core\Router();

// Load route definitions
$routes = require base_path('routes/routes.php');
```

The `routes/routes.php` file registers all application routes:
```php
$router->get('/', 'welcome.php');
$router->get('/posts/{id}', 'posts/show.php');
$router->post('/login', 'session/store.php')->only(['guest', 'csrf']);
```

### 4. Request Capture

```php
$request = Request::capture();
$uri = $request->path();        // e.g., "/posts/123"
$method = $request->method();   // e.g., "GET"
```

The Request object extracts:
- URI path from `$_SERVER['REQUEST_URI']`
- HTTP method from `$_SERVER['REQUEST_METHOD']`
- Query parameters from `$_GET`
- Form data from `$_POST`

### 5. Route Matching

```php
$router->route($uri, $method, $request);
```

The router:
1. Loops through all registered routes
2. Checks if the HTTP method matches
3. Uses regex to match the URI pattern
4. Extracts route parameters (e.g., `{id}` → `123`)
5. Injects parameters into `$_GET`

### 6. Middleware Execution

If the route has middleware:
```php
Middleware::resolve($route['middleware']);
```

Middleware runs before the controller:
- `auth` - Checks if user is logged in
- `guest` - Checks if user is NOT logged in
- `csrf` - Validates CSRF token

If middleware fails, execution stops (redirect or abort).

### 7. Controller Execution

```php
return require base_path('Http/controllers/' . $route['controller']);
```

The controller file is required and executed:
```php
<?php
// Http/controllers/posts/show.php

$db = App::resolve(Database::class);
$post = $db->query('SELECT * FROM posts WHERE id = :id', [
    'id' => $_GET['id']
])->findOrFail();

view('posts/show.view.php', ['post' => $post]);
```

### 8. View Rendering

```php
view('posts/show.view.php', ['post' => $post]);
```

The view helper:
1. Extracts variables (`$post` becomes available)
2. Requires the view template
3. Outputs HTML to the browser

### 9. Response Sent

The HTML is sent to the browser, completing the request.

### 10. Session Cleanup

```php
Session::unflash();
```

Flash data (like validation errors) is removed after one request.

## Visual Flow Diagram

```
HTTP Request (GET /posts/123)
    ↓
public/index.php (Front Controller)
    ↓
Load Composer Autoloader
    ↓
Start Session
    ↓
Load Helper Functions
    ↓
framework/Core/bootstrap.php
    ├─ Load .env
    ├─ Load config files
    ├─ Create Container
    ├─ Bind Database
    └─ Set App container
    ↓
Create Router
    ↓
Load routes/routes.php
    ↓
Capture Request (URI + Method)
    ↓
Router::route($uri, $method)
    ├─ Match route pattern
    ├─ Extract parameters
    └─ Inject into $_GET
    ↓
Middleware::resolve()
    ├─ Run auth check
    ├─ Run guest check
    └─ Run CSRF check
    ↓
Require Controller File
    ├─ Query database
    ├─ Process data
    └─ Call view()
    ↓
Render View Template
    ↓
Send HTML Response
    ↓
Session::unflash()
    ↓
Request Complete
```

## Key Files to Study

1. **`public/index.php`** - Entry point and request handling
2. **`framework/Core/bootstrap.php`** - Framework initialization
3. **`framework/Core/Router.php`** - Route matching logic
4. **`framework/Core/Request.php`** - Request abstraction
5. **`framework/Core/Middleware/Middleware.php`** - Middleware resolution

## Common Issues

### Issue: "404 Not Found"
**Where to look:** Router matching logic in `Router::route()`

### Issue: "Route works but parameters are empty"
**Where to look:** Parameter injection in `Router::route()` after `matchUri()`

### Issue: "Session data not persisting"
**Where to look:** Session start in `public/index.php`

### Issue: "Database connection fails"
**Where to look:** Bootstrap process and `config/database.php`

## Debugging Tips

1. **Use `dd()`** - Add `dd($variable)` anywhere to dump and stop execution
2. **Check error logs** - Look in `storage/logs/app.log`
3. **Enable debug mode** - Set `APP_DEBUG=true` in `.env`
4. **Trace the flow** - Start at `public/index.php` and follow execution

## Practice Exercise

Try tracing a request manually:
1. Start your server: `php artisan serve`
2. Visit `http://localhost:8000/`
3. Open `public/index.php` and add `dd('Entry point reached');`
4. Refresh the browser
5. Move the `dd()` further down to trace execution

## Next Steps

After understanding the request lifecycle, you're ready for:
- **Lesson 02: Routing** - How URLs map to controllers
- **Challenge: Broken Request Handling** - Debug a broken request flow

## Summary

The HTTP request lifecycle in DALT.PHP follows a predictable path:
1. All requests enter through `public/index.php`
2. Framework bootstraps (load config, create container)
3. Router matches URI to controller
4. Middleware runs (if defined)
5. Controller executes
6. View renders
7. Response sent

Understanding this flow is essential for debugging any backend issue.
