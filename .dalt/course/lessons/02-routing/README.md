# Lesson 02: Routing System

## Overview

The routing system maps incoming URLs to controller files. Understanding how routes work is crucial for debugging "404 Not Found" errors and parameter extraction issues.

## Learning Objectives

By the end of this lesson, you will understand:
- How routes are defined and registered
- How the router matches URLs to patterns
- How route parameters are extracted
- How HTTP methods are handled
- Common routing bugs and how to fix them

## Route Definition

Routes are defined in `routes/routes.php`:

```php
// Simple route
$router->get('/', 'welcome.php');

// Route with parameter
$router->get('/posts/{id}', 'posts/show.php');

// Route with multiple parameters
$router->get('/users/{userId}/posts/{postId}', 'users/posts/show.php');

// Different HTTP methods
$router->post('/posts', 'posts/store.php');
$router->patch('/posts/{id}', 'posts/update.php');
$router->delete('/posts/{id}', 'posts/destroy.php');
```

## How Route Matching Works

### Step 1: Loop Through Routes

The router loops through all registered routes:
```php
foreach ($this->routes as $route) {
    // Check if this route matches
}
```

### Step 2: Check HTTP Method

```php
if (strtoupper($method) !== $route['method']) {
    continue; // Skip this route
}
```

### Step 3: Match URI Pattern

The router uses regex to match patterns:

**Pattern:** `/posts/{id}`  
**Actual:** `/posts/123`

The `matchUri()` method:
1. Converts `{id}` to regex capture group `([^/]+)`
2. Creates regex: `#^/posts/([^/]+)$#`
3. Matches against actual URI
4. Extracts captured values

```php
protected function matchUri(string $pattern, string $actual)
{
    // Exact match fast-path
    if ($pattern === $actual) {
        return [];
    }

    // Convert {id} to ([^/]+)
    $paramNames = [];
    $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', 
        function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '([^/]+)';
        }, $pattern);

    $regex = '#^' . $regex . '$#';

    if (preg_match($regex, $actual, $matches)) {
        array_shift($matches); // remove full match
        return array_combine($paramNames, $matches) ?: [];
    }

    return false;
}
```

### Step 4: Inject Parameters

Matched parameters are injected into `$_GET`:
```php
foreach ($params as $key => $value) {
    $_GET[$key] = $value;
}
```

Now controllers can access them:
```php
$id = $_GET['id']; // "123"
```

## Route Registration Methods

### GET Routes
```php
$router->get('/posts', 'posts/index.php');
```
Handles: `GET /posts`

### POST Routes
```php
$router->post('/posts', 'posts/store.php');
```
Handles: `POST /posts` (form submissions)

### PATCH Routes
```php
$router->patch('/posts/{id}', 'posts/update.php');
```
Handles: `PATCH /posts/123` (updates)

### DELETE Routes
```php
$router->delete('/posts/{id}', 'posts/destroy.php');
```
Handles: `DELETE /posts/123` (deletions)

## Route Parameters

### Single Parameter
```php
$router->get('/posts/{id}', 'posts/show.php');
```
- URL: `/posts/123`
- Controller access: `$_GET['id']` → `"123"`

### Multiple Parameters
```php
$router->get('/users/{userId}/posts/{postId}', 'users/posts/show.php');
```
- URL: `/users/5/posts/42`
- Controller access: `$_GET['userId']` → `"5"`, `$_GET['postId']` → `"42"`

### Parameter Naming Rules
- Must start with letter or underscore
- Can contain letters, numbers, underscores
- Examples: `{id}`, `{user_id}`, `{postId}`

## Route Order Matters

Routes are matched in the order they're defined:

```php
// ❌ WRONG - specific route after generic
$router->get('/posts/{id}', 'posts/show.php');
$router->get('/posts/create', 'posts/create.php'); // Never matches!

// ✅ CORRECT - specific route first
$router->get('/posts/create', 'posts/create.php');
$router->get('/posts/{id}', 'posts/show.php');
```

## Middleware on Routes

Routes can have middleware:
```php
$router->post('/posts', 'posts/store.php')->only(['auth', 'csrf']);
```

This runs `auth` and `csrf` middleware before the controller.

## Common Routing Issues

### Issue 1: 404 on Valid Route
**Cause:** Route pattern doesn't match URL  
**Debug:** Check regex pattern in `matchUri()`

### Issue 2: Wrong Controller Executes
**Cause:** Route order problem  
**Fix:** Move specific routes before generic ones

### Issue 3: Parameters Are Empty
**Cause:** Parameter injection failed  
**Debug:** Check `$_GET` injection in `Router::route()`

### Issue 4: POST Route Returns 404
**Cause:** Form method is GET, not POST  
**Fix:** Add `method="POST"` to form

## Debugging Routes

### Technique 1: Dump All Routes
```php
// In routes/routes.php
dd($router);
```

### Technique 2: Trace Route Matching
```php
// In Router::route()
dd($uri, $method, $this->routes);
```

### Technique 3: Check Parameters
```php
// In controller
dd($_GET);
```

## Key Files

- **`routes/routes.php`** - Route definitions
- **`framework/Core/Router.php`** - Routing logic
- **`public/index.php`** - Router invocation

## Practice Exercise

1. Add a new route: `$router->get('/about', 'about.php');`
2. Create controller: `Http/controllers/about.php`
3. Create view: `resources/views/about.view.php`
4. Test in browser: `http://localhost:8000/about`

## Next Steps

- **Lesson 03: Middleware** - How to protect routes
- **Challenge: Broken Routing** - Debug routing issues

## Summary

The routing system:
1. Registers routes with patterns and controllers
2. Matches incoming URLs using regex
3. Extracts parameters from URLs
4. Injects parameters into `$_GET`
5. Requires the controller file

Understanding routing is essential for building and debugging web applications.
