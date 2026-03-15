# Challenge: Broken Session

## Difficulty: Beginner

## Setup Instructions

1. **Backup your current Session class:**
   ```bash
   cp framework/Core/Session.php framework/Core/Session.php.backup
   ```

2. **Copy the broken Session class:**
   ```bash
   cp challenges/broken-session/framework/Core/Session.php framework/Core/
   ```

3. **Copy the controller files:**
   ```bash
   cp -r challenges/broken-session/Http/controllers/contact Http/controllers/
   ```

4. **Add routes (append to routes/routes.php):**
   ```bash
   cat challenges/broken-session/routes/routes.php >> routes/routes.php
   ```

5. **Start the server:**
   ```bash
   php artisan serve
   ```

6. **Test the broken session:**
   - Visit http://localhost:8000/contact
   - Submit form with empty fields (validation errors won't show correctly!)
   - Submit valid form (success message persists after refresh!)

## Concept: How Sessions Work

Sessions store data across HTTP requests:

1. **Session Start** - `session_start()` in `public/index.php`
2. **Store Data** - `$_SESSION['key'] = 'value'`
3. **Retrieve Data** - `$value = $_SESSION['key']`
4. **Flash Data** - Data that exists for only one request
5. **Cleanup** - `Session::unflash()` removes flash data

**Flash data flow:**
```
Request 1: Store flash → $_SESSION['_flash']['errors'] = [...]
Request 2: Retrieve flash → Session::get('errors')
Request 2 End: Clean up → Session::unflash()
Request 3: Flash data gone
```

## The Bugs

### Bug #1: Session::get() Checks Wrong Order

**Symptom:** Flash data is not retrieved correctly.

**What's happening:**
```php
// BROKEN - checks regular session first
return $_SESSION[$key] ?? $_SESSION['_flash'][$key] ?? $default;

// CORRECT - checks flash first
return $_SESSION['_flash'][$key] ?? $_SESSION[$key] ?? $default;
```

Flash data should have priority over regular session data.

### Bug #2: unflash() is Disabled

**Symptom:** Flash messages persist across multiple requests.

**What's happening:**
```php
// BROKEN - cleanup commented out
public static function unflash()
{
    // unset($_SESSION['_flash']);
}

// CORRECT - cleanup enabled
public static function unflash()
{
    unset($_SESSION['_flash']);
}
```

Without cleanup, flash data never gets removed and appears on every request.

## Learning Objectives

After fixing this challenge, you will understand:
- How sessions persist data across requests
- The difference between regular and flash session data
- Why flash data must be cleaned up
- How form validation uses flash data
- How to debug session issues

## Debugging Hints

1. **Check session contents** - Add `dd($_SESSION)` to see what's stored
2. **Trace flash data** - Add `dd($_SESSION['_flash'])` after flashing
3. **Test cleanup** - Refresh the page multiple times to see if flash persists
4. **Check retrieval order** - Look at `Session::get()` logic carefully

## Files to Investigate

- `framework/Core/Session.php` - Session management (Bugs are here!)
- `public/index.php` - See where `Session::unflash()` is called
- `Http/controllers/contact/submit.php` - See how flash data is stored
- `Http/controllers/contact/form.php` - See how flash data is retrieved

## How to Fix

### Fix #1: Correct Session::get() Order

Check flash data before regular session data:
```php
// In framework/Core/Session.php
public static function get($key, $default = null)
{
    // Fixed: Check flash first, then regular session
    return $_SESSION['_flash'][$key] ?? $_SESSION[$key] ?? $default;
}
```

### Fix #2: Enable unflash()

Uncomment the cleanup code:
```php
// In framework/Core/Session.php
public static function unflash()
{
    unset($_SESSION['_flash']); // Fixed: uncommented
}
```

## Success Criteria

When fixed correctly:
- ✅ Validation errors display after form submission
- ✅ Old form input is preserved after validation errors
- ✅ Success messages display once and disappear after refresh
- ✅ Flash data is cleaned up properly

## Testing Your Fix

1. **Test validation errors:**
   - Visit http://localhost:8000/contact
   - Submit empty form
   - Should see error messages
   - Refresh page - errors should disappear

2. **Test old input:**
   - Fill form partially
   - Submit with some fields empty
   - Should see your input preserved in valid fields

3. **Test success message:**
   - Submit valid form
   - Should see success message
   - Refresh page - message should disappear

## Cleanup

After completing the challenge:
```bash
# Restore original Session class
cp framework/Core/Session.php.backup framework/Core/Session.php

# Remove challenge controllers (optional)
rm -rf Http/controllers/contact
```

## Related Lessons

- **Lesson 01: Request Lifecycle** - See where session is initialized
- **Lesson 04: Authentication** - See how sessions store user data

## Congratulations!

You've completed all five broken challenges! You now understand:
- Routing and parameter extraction
- Middleware execution and validation
- Authentication and password security
- Database queries and SQL injection prevention
- Session management and flash data

Ready to build your own backend applications!
