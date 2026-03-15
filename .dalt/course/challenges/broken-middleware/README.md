# Challenge: Broken Middleware

## Difficulty: Intermediate

## Setup Instructions

1. **Backup your current middleware files:**
   ```bash
   cp framework/Core/Middleware/Auth.php framework/Core/Middleware/Auth.php.backup
   cp framework/Core/Middleware/Csrf.php framework/Core/Middleware/Csrf.php.backup
   ```

2. **Copy the broken middleware files:**
   ```bash
   cp challenges/broken-middleware/framework/Core/Middleware/Auth.php framework/Core/Middleware/
   cp challenges/broken-middleware/framework/Core/Middleware/Csrf.php framework/Core/Middleware/
   ```

3. **Copy the controller files:**
   ```bash
   cp -r challenges/broken-middleware/Http/controllers/dashboard Http/controllers/
   ```

4. **Add routes (append to routes/routes.php):**
   ```bash
   cat challenges/broken-middleware/routes/routes.php >> routes/routes.php
   ```

5. **Start the server:**
   ```bash
   php artisan serve
   ```

6. **Test the broken middleware:**
   - Visit http://localhost:8000/dashboard (should redirect to login, but doesn't!)
   - Try submitting the form (CSRF should pass, but fails!)

## Concept: How Middleware Works

Middleware runs between the router and controller to filter requests:

```
Request → Router → Middleware → Controller → Response
```

If middleware fails, the controller never executes.

**Common middleware types:**
- **Auth** - Requires user to be logged in
- **Guest** - Requires user to NOT be logged in
- **CSRF** - Validates form tokens to prevent attacks

## The Bugs

### Bug #1: Auth Middleware Checks Wrong Session Key

**Symptom:** Unauthenticated users can access protected routes.

**What's happening:**
```php
// BROKEN
if(!($_SESSION['authenticated'] ?? false)){
    // ...
}

// CORRECT
if(!($_SESSION['user'] ?? false)){
    // ...
}
```

The Auth middleware checks for `$_SESSION['authenticated']` but the login system stores user data in `$_SESSION['user']`.

**Why it's broken:** Session key mismatch means the check always fails, allowing anyone to access protected routes.

### Bug #2: CSRF Middleware Logic Inverted

**Symptom:** Valid CSRF tokens are rejected, forms can't be submitted.

**What's happening:**
```php
// BROKEN - rejects when tokens MATCH
if ($sessionToken == $formToken) {
    http_response_code(419);
    echo 'CSRF token mismatch';
    exit;
}

// CORRECT - rejects when tokens DON'T match
if (!$sessionToken || !$formToken || !hash_equals($sessionToken, $formToken)) {
    http_response_code(419);
    echo 'CSRF token mismatch';
    exit;
}
```

The logic is inverted - it aborts when tokens match instead of when they don't match.

**Additional issue:** Using `==` instead of `hash_equals()` creates a timing attack vulnerability.

## Learning Objectives

After fixing this challenge, you will understand:
- How middleware checks session data
- Why session key names must match
- How CSRF protection works
- Why timing-safe comparison matters
- How to debug middleware issues

## Debugging Hints

1. **Check session keys** - Add `dd($_SESSION)` in Auth middleware to see what's stored
2. **Trace middleware execution** - Add `dd('Auth middleware running')` to verify it executes
3. **Test CSRF logic** - Add `dd($sessionToken, $formToken)` to see token values
4. **Read the condition carefully** - Is the logic checking what you think it's checking?

## Files to Investigate

- `framework/Core/Middleware/Auth.php` - Authentication check (Bug #1 is here!)
- `framework/Core/Middleware/Csrf.php` - CSRF validation (Bug #2 is here!)
- `framework/Core/Middleware/Middleware.php` - Middleware resolution (read to understand flow)
- `framework/Core/Authenticator.php` - See what session key is used during login

## How to Fix

### Fix #1: Correct Session Key in Auth Middleware

Change the session key to match what's actually stored:
```php
// In framework/Core/Middleware/Auth.php
public function handle()
{
    if(!($_SESSION['user'] ?? false)){  // Changed from 'authenticated' to 'user'
        header('location: /' );
        exit();
    }
}
```

### Fix #2: Fix CSRF Logic and Use Timing-Safe Comparison

Invert the logic and use `hash_equals()`:
```php
// In framework/Core/Middleware/Csrf.php
public function handle(): void
{
    $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
    $method = strtoupper($method);
    
    if ($method === 'GET') {
        return;
    }

    $sessionToken = $_SESSION['_csrf'] ?? null;
    $formToken = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    // Fixed: Reject when tokens DON'T match, use hash_equals()
    if (!$sessionToken || !$formToken || !hash_equals($sessionToken, $formToken)) {
        http_response_code(419);
        echo 'CSRF token mismatch';
        exit;
    }
}
```

## Success Criteria

When fixed correctly:
- ✅ Unauthenticated users are redirected from `/dashboard`
- ✅ Forms with valid CSRF tokens submit successfully
- ✅ Forms without CSRF tokens are rejected
- ✅ Timing-safe comparison is used

## Testing Your Fix

### Test Auth Middleware:
```bash
# Without login, should redirect
curl -I http://localhost:8000/dashboard
# Should see: Location: /

# After login, should work
# (You'll need to implement login or mock $_SESSION['user'])
```

### Test CSRF Middleware:
```bash
# With valid token, should work
curl -X POST http://localhost:8000/dashboard/update \
  -d "_token=valid_token_here"

# Without token, should fail with 419
curl -X POST http://localhost:8000/dashboard/update
```

## Cleanup

After completing the challenge:
```bash
# Restore original middleware
cp framework/Core/Middleware/Auth.php.backup framework/Core/Middleware/Auth.php
cp framework/Core/Middleware/Csrf.php.backup framework/Core/Middleware/Csrf.php

# Remove challenge controllers (optional)
rm -rf Http/controllers/dashboard
```

## Related Lesson

**Lesson 03: Middleware System** - Study this before attempting the challenge.

## Next Challenge

After mastering middleware, try **Challenge: Broken Authentication**
