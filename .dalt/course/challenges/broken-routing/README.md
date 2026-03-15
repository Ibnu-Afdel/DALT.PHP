# Challenge: Broken Routing

## Difficulty: Beginner

## Setup Instructions

1. **Backup your current routes:**
   ```bash
   cp routes/routes.php routes/routes.php.backup
   ```

2. **Copy the broken routes file:**
   ```bash
   cp challenges/broken-routing/routes/routes.php routes/routes.php
   ```

3. **Copy the controller files:**
   ```bash
   cp -r challenges/broken-routing/Http/controllers/posts Http/controllers/
   ```

4. **Start the server:**
   ```bash
   php artisan serve
   ```

5. **Test the broken routes:**
   - Visit http://localhost:8000/posts (should work)
   - Visit http://localhost:8000/posts/create (broken!)
   - Visit http://localhost:8000/posts/1 (should work)
   - Visit http://localhost:8000/posts/1/edit (broken!)

## Concept: How Routing Works

The router matches incoming URLs to route patterns in the order they're defined. When a URL matches a pattern, the router:
1. Extracts parameters (e.g., `{id}` from `/posts/123`)
2. Injects them into `$_GET`
3. Executes the controller

**Key Point:** Route order matters! Specific routes must come before generic routes.

## The Bugs

### Bug #1: Route Order Problem

**Symptom:** Visiting `/posts/create` shows the post detail page instead of the create form.

**What's happening:**
```php
$router->get('/posts/{id}', 'posts/show.php');
$router->get('/posts/create', 'posts/create.php'); // Never matches!
```

The router matches `/posts/create` against `/posts/{id}` first, treating "create" as an ID.

**Why it's broken:** Generic routes with parameters should come AFTER specific routes.

### Bug #2: Missing Route Registration

**Symptom:** Visiting `/posts/1/edit` returns 404 Not Found.

**What's happening:**
```php
// $router->get('/posts/{id}/edit', 'posts/edit.php'); // Commented out!
```

The route exists in the controller but is not registered in `routes.php`.

**Why it's broken:** Routes must be explicitly registered to work.

## Learning Objectives

After fixing this challenge, you will understand:
- Why route order matters
- How the router matches patterns
- How to debug 404 errors
- How route parameters work

## Debugging Hints

1. **Check route order** - Look at `routes/routes.php` line by line
2. **Trace the matching** - Add `dd($this->routes)` in `Router::route()` to see all routes
3. **Test specific before generic** - Move `/posts/create` before `/posts/{id}`
4. **Find commented routes** - Search for `//` in `routes.php`

## Files to Investigate

- `routes/routes.php` - Route definitions (this is where the bugs are!)
- `framework/Core/Router.php` - Route matching logic (read `matchUri()` method)
- `Http/controllers/posts/` - Controllers expecting these routes

## How to Fix

### Fix #1: Correct Route Order

Move specific routes before generic ones:
```php
// ✅ CORRECT ORDER
$router->get('/posts/create', 'posts/create.php');  // Specific first
$router->get('/posts/{id}', 'posts/show.php');      // Generic after
```

### Fix #2: Uncomment Missing Route

Find and uncomment the edit route:
```php
$router->get('/posts/{id}/edit', 'posts/edit.php');
```

## Success Criteria

When fixed correctly:
- ✅ `/posts` shows all posts
- ✅ `/posts/create` shows the create form
- ✅ `/posts/1` shows post #1
- ✅ `/posts/1/edit` shows the edit form
- ✅ No 404 errors on valid routes

## Testing Your Fix

```bash
# Test all routes
curl http://localhost:8000/posts
curl http://localhost:8000/posts/create
curl http://localhost:8000/posts/1
curl http://localhost:8000/posts/1/edit
```

All should return 200 OK with the correct page.

## Cleanup

After completing the challenge:
```bash
# Restore original routes
cp routes/routes.php.backup routes/routes.php

# Remove challenge controllers (optional)
rm -rf Http/controllers/posts
```

## Related Lesson

**Lesson 02: Routing System** - Study this before attempting the challenge.

## Next Challenge

After mastering routing, try **Challenge: Broken Middleware**
