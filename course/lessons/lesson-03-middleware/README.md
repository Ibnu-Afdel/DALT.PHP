# Lesson 03: Middleware System

## Overview

Middleware provides a way to filter HTTP requests before they reach your controllers. Common uses include authentication, CSRF protection, and authorization checks.

## Learning Objectives

By the end of this lesson, you will understand:
- What middleware is and why it's useful
- How middleware executes in DALT.PHP
- The three built-in middleware types
- How to debug middleware issues
- When middleware blocks requests

## What is Middleware?

Middleware is code that runs between the router and the controller:

```
Request → Router → Middleware → Controller → Response
```

If middleware fails, the controller never executes.

## Middleware Registration

Middleware is registered on routes using `only()`:

```php
// Single middleware
$router->get('/dashboard', 'dashboard.php')->only('auth');

// Multiple middleware
$router->post('/posts', 'posts/store.php')->only(['auth', 'csrf']);
```

## How Middleware Works

### Step 1: Route Matches

The router finds a matching route with middleware:
```php
[
    'uri' => '/dashboard',
    'controller' => 'dashboard.php',
    'method' => 'GET',
    'middleware' => 'auth'
]
```

### Step 2: Middleware Resolution

```php
Middleware::resolve($route['middleware']);
```

The `Middleware::resolve()` method:
1. Converts middleware keys to class names using `MAP`
2. Instantiates each middleware class
3. Calls the `handle()` method

```php
public const MAP = [
    'guest' => Guest::class,
    'auth' => Auth::class,
    'csrf' => Csrf::class,
];

public static function resolve($keys)
{
    if (!$keys) {
        return;
    }

    $middlewares = is_array($keys) ? $keys : [$keys];

    foreach ($middlewares as $key) {
        $middleware = static::MAP[$key] ?? false;
        if (!$middleware) {
            throw new \Exception("No Matching Middleware found for key '{$key}'");
        }
        (new $middleware)->handle();
    }
}
```

### Step 3: Middleware Execution

Each middleware's `handle()` method runs:
- If it passes, execution continues
- If it fails, execution stops (redirect or abort)

## Built-in Middleware

### 1. Auth Middleware (`framework/Core/Middleware/Auth.php`)

**Purpose:** Ensure user is logged in

**Logic:**
```php
public function handle()
{
    if (!$_SESSION['user'] ?? false) {
        redirect('/login');
    }
}
```

**Usage:**
```php
$router->get('/dashboard', 'dashboard.php')->only('auth');
```

**Behavior:**
- If `$_SESSION['user']` exists → Allow access
- If not → Redirect to `/login`

### 2. Guest Middleware (`framework/Core/Middleware/Guest.php`)

**Purpose:** Ensure user is NOT logged in

**Logic:**
```php
public function handle()
{
    if ($_SESSION['user'] ?? false) {
        redirect('/');
    }
}
```

**Usage:**
```php
$router->get('/login', 'session/create.php')->only('guest');
```

**Behavior:**
- If `$_SESSION['user']` exists → Redirect to `/`
- If not → Allow access

### 3. CSRF Middleware (`framework/Core/Middleware/Csrf.php`)

**Purpose:** Prevent Cross-Site Request Forgery attacks

**Logic:**
```php
public function handle()
{
    $token = $_POST['_token'] ?? '';
    $sessionToken = $_SESSION['_csrf'] ?? '';

    if ($token !== $sessionToken || empty($token)) {
        abort(403);
    }
}
```

**Usage:**
```php
$router->post('/posts', 'posts/store.php')->only(['auth', 'csrf']);
```

**Behavior:**
- Compares form token with session token
- If they match → Allow request
- If not → Abort with 403

**Form Setup:**
```php
<form method="POST" action="/posts">
    <?= csrf_field() ?>
    <!-- form fields -->
</form>
```

## Middleware Execution Order

Middleware runs in the order specified:

```php
$router->post('/posts', 'posts/store.php')->only(['auth', 'csrf']);
```

**Execution:**
1. `auth` middleware runs first
2. If auth passes, `csrf` middleware runs
3. If csrf passes, controller executes

**Important:** Order matters! Always check `auth` before `csrf`.

## Common Middleware Issues

### Issue 1: Infinite Redirect Loop
**Cause:** Auth middleware redirects to a route that also requires auth  
**Example:**
```php
$router->get('/login', 'session/create.php')->only('auth'); // ❌ WRONG
```
**Fix:** Use `guest` middleware on login routes

### Issue 2: CSRF Token Mismatch
**Cause:** Form missing `<?= csrf_field() ?>`  
**Fix:** Add CSRF field to all POST forms

### Issue 3: Middleware Not Running
**Cause:** Middleware key not in `MAP`  
**Fix:** Check `Middleware::MAP` for typos

### Issue 4: User Logged Out Unexpectedly
**Cause:** Session expired or destroyed  
**Debug:** Check session configuration and lifetime

## Debugging Middleware

### Technique 1: Check Session
```php
// In middleware
dd($_SESSION);
```

### Technique 2: Trace Execution
```php
// In Middleware::resolve()
dd($keys, static::MAP);
```

### Technique 3: Test Middleware Directly
```php
// In controller (before middleware)
dd($_SESSION['user'] ?? 'not set');
```

## Creating Custom Middleware

To add custom middleware:

1. Create class in `framework/Core/Middleware/`
2. Implement `handle()` method
3. Add to `Middleware::MAP`
4. Use on routes

Example:
```php
// framework/Core/Middleware/Admin.php
class Admin
{
    public function handle()
    {
        if (!($_SESSION['user']['is_admin'] ?? false)) {
            abort(403);
        }
    }
}

// Add to MAP
public const MAP = [
    'guest' => Guest::class,
    'auth' => Auth::class,
    'csrf' => Csrf::class,
    'admin' => Admin::class, // New
];

// Use on route
$router->get('/admin', 'admin/dashboard.php')->only(['auth', 'admin']);
```

## Key Files

- **`framework/Core/Middleware/Middleware.php`** - Resolution logic
- **`framework/Core/Middleware/Auth.php`** - Authentication check
- **`framework/Core/Middleware/Guest.php`** - Guest-only check
- **`framework/Core/Middleware/Csrf.php`** - CSRF protection

## Practice Exercise

1. Create a route that requires authentication
2. Try accessing it without logging in
3. Observe the redirect to `/login`
4. Add `dd($_SESSION)` in Auth middleware to see why

## Next Steps

- **Lesson 04: Authentication** - How login/logout works
- **Challenge: Broken Middleware** - Debug middleware issues

## Summary

Middleware:
- Runs between router and controller
- Can block requests (redirect or abort)
- Executes in specified order
- Used for auth, CSRF, and authorization
- Configured via `only()` on routes

Understanding middleware is essential for securing your application and debugging access issues.
