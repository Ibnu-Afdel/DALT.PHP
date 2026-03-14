# DALT.PHP Testing Guide

This guide walks you through testing all the features we've built in DALT.PHP - the interactive backend debugging playground.

## Prerequisites

Before testing, ensure you have:
- PHP 8.0+ installed
- Node.js and npm installed
- Composer dependencies installed (`composer install`)
- NPM dependencies installed (`npm install`)

## Quick Start

### 1. Start the Servers

You need two servers running simultaneously:

**Terminal 1 - PHP Server:**
```bash
php artisan serve
```
This starts the PHP backend on `http://localhost:8888`

**Terminal 2 - Vite Dev Server:**
```bash
npm run dev
```
This starts the Vite frontend on `http://localhost:5173`

### 2. Access the Application

Open your browser and navigate to:
```
http://localhost:8888
```

The Vite server will automatically inject the Vue components and Tailwind CSS.

---

## Testing the Framework Core

### Test 1: Basic Framework
1. Visit `http://localhost:8888`
2. You should see the welcome page
3. Check that Vue is working (look for any Vue components)
4. Check that Tailwind CSS v4 is applied (inspect element styles)

### Test 2: Vue Components
The framework includes auto-registered Vue components:
- `Counter.vue` - A simple counter component
- `ExampleComponent.vue` - Example component
- `UnlockButton.vue` - Challenge unlock button

These should be available globally in any view.

---

## Testing the Challenges

We have 5 broken challenges to test. Each follows the same cycle:

1. **Read** the challenge README
2. **Run** the verification (it should fail)
3. **Fix** the broken code
4. **Verify** again (it should pass)

### Challenge 1: Broken Routing

**Location:** `challenges/broken-routing/`

**Step 1 - Read the Challenge:**
```bash
cat challenges/broken-routing/README.md
```

**Step 2 - Run Verification (Should Fail):**
```bash
php artisan verify broken-routing
```

Expected output: ❌ Tests should fail

**Step 3 - Understand the Bugs:**

There are 2 bugs in `challenges/broken-routing/routes/web.php`:

1. **Route Order Problem:** `/posts/create` is registered AFTER `/posts/{id}`, so create gets caught by the wildcard
2. **Missing Route:** The `/posts/{id}/edit` route is commented out

**Step 4 - Fix the Bugs:**

Open `challenges/broken-routing/routes/web.php` and:
1. Move `/posts/create` BEFORE `/posts/{id}`
2. Uncomment the `/posts/{id}/edit` route

**Step 5 - Verify Again (Should Pass):**
```bash
php artisan verify broken-routing
```

Expected output: ✅ All tests pass

---

### Challenge 2: Broken Middleware

**Location:** `challenges/broken-middleware/`

**Step 1 - Read the Challenge:**
```bash
cat challenges/broken-middleware/README.md
```

**Step 2 - Run Verification (Should Fail):**
```bash
php artisan verify broken-middleware
```

**Step 3 - Understand the Bugs:**

There are 2 bugs:

1. **Auth Middleware Bug:** In `framework/Core/Middleware/Auth.php`
   - Checks for `$_SESSION['authenticated']` instead of `$_SESSION['user']`

2. **CSRF Middleware Bug:** In `framework/Core/Middleware/Csrf.php`
   - Logic is inverted (rejects valid tokens, accepts invalid ones)
   - Has timing attack vulnerability

**Step 4 - Fix the Bugs:**

Fix Auth middleware:
```php
// Change this:
if (!isset($_SESSION['authenticated'])) {
// To this:
if (!isset($_SESSION['user'])) {
```

Fix CSRF middleware:
```php
// Change this:
if ($token !== $sessionToken) {
    return; // Valid token, allow
}
// To this:
if (hash_equals($sessionToken, $token)) {
    return; // Valid token, allow
}
```

**Step 5 - Verify Again (Should Pass):**
```bash
php artisan verify broken-middleware
```

---

### Challenge 3: Broken Authentication

**Location:** `challenges/broken-auth/`

**Step 1 - Read the Challenge:**
```bash
cat challenges/broken-auth/README.md
```

**Step 2 - Run Verification (Should Fail):**
```bash
php artisan verify broken-auth
```

**Step 3 - Understand the Bug:**

In `framework/Core/Authenticator.php`, the `attempt()` method uses `==` comparison instead of `password_verify()`:

```php
if ($user && $user['password'] == $password) {
    // This is WRONG - compares plain text
}
```

**Step 4 - Fix the Bug:**

Change to:
```php
if ($user && password_verify($password, $user['password'])) {
    // Correct - uses password_verify()
}
```

**Step 5 - Verify Again (Should Pass):**
```bash
php artisan verify broken-auth
```

---

### Challenge 4: Broken Database

**Location:** `challenges/broken-database/`

**Step 1 - Read the Challenge:**
```bash
cat challenges/broken-database/README.md
```

**Step 2 - Run Verification (Should Fail):**
```bash
php artisan verify broken-database
```

**Step 3 - Understand the Bugs:**

In `framework/Core/Database.php`, there are 2 SQL injection vulnerabilities:

1. **In `query()` method:** Uses string concatenation instead of prepared statements
2. **In `find()` method:** Doesn't pass parameters to `execute()`

**Step 4 - Fix the Bugs:**

Fix the `query()` method:
```php
// Change this:
$statement = $this->connection->prepare($query . " WHERE {$conditions}");
// To this:
$statement = $this->connection->prepare($query);
```

Fix the `find()` method:
```php
// Change this:
$statement->execute();
// To this:
$statement->execute($params);
```

**Step 5 - Verify Again (Should Pass):**
```bash
php artisan verify broken-database
```

---

### Challenge 5: Broken Session

**Location:** `challenges/broken-session/`

**Step 1 - Read the Challenge:**
```bash
cat challenges/broken-session/README.md
```

**Step 2 - Run Verification (Should Fail):**
```bash
php artisan verify broken-session
```

**Step 3 - Understand the Bugs:**

In `framework/Core/Session.php`, there are 2 bugs:

1. **In `flash()` method:** Checks `_flash` before `$key`, so new flash data is immediately removed
2. **In `unflash()` method:** The cleanup is commented out, so flash data persists forever

**Step 4 - Fix the Bugs:**

Fix the `flash()` method (swap the order):
```php
// Change this:
return $_SESSION['_flash'][$key] ?? $_SESSION[$key] ?? $default;
// To this:
return $_SESSION[$key] ?? $_SESSION['_flash'][$key] ?? $default;
```

Fix the `unflash()` method (uncomment the cleanup):
```php
// Change this:
// unset($_SESSION['_flash']);
// To this:
unset($_SESSION['_flash']);
```

**Step 5 - Verify Again (Should Pass):**
```bash
php artisan verify broken-session
```

---

## Checking Progress Logs

All verification results are logged to `storage/logs/challenges.log`.

**View the log:**
```bash
cat storage/logs/challenges.log
```

**View recent entries:**
```bash
tail -20 storage/logs/challenges.log
```

The log format:
```
[2026-03-14 10:30:45] broken-routing: PASS
[2026-03-14 10:35:22] broken-middleware: FAIL
```

---

## Testing Vue Frontend

### Test Vue Component Registration

Create a test view file to verify Vue components work:

**Create:** `resources/views/test-vue.view.php`
```php
<?php view('partials/head', ['title' => 'Vue Test']); ?>

<div id="app">
    <h1 class="text-3xl font-bold mb-4">Vue Component Test</h1>
    
    <!-- Test Counter Component -->
    <counter></counter>
    
    <!-- Test Example Component -->
    <example-component></example-component>
    
    <!-- Test Unlock Button -->
    <unlock-button challenge="broken-routing"></unlock-button>
</div>

<?php view('partials/foot'); ?>
```

Visit the page and verify all components render correctly.

### Test Tailwind CSS v4

Verify Tailwind v4 is working:
1. Inspect any element with Tailwind classes
2. Check that styles are applied
3. Try adding new utility classes (they should work without rebuild)

---

## Troubleshooting

### Port 5173 Already in Use

If you get this error:
```bash
Error: Port 5173 is already in use
```

**Solution 1 - Kill the process:**
```bash
lsof -ti:5173 | xargs kill -9
```

**Solution 2 - Use a different port:**
Edit `vite.config.mjs` and change the port:
```js
server: {
    port: 5174 // Use different port
}
```

### PHP Server Not Starting

If `php artisan serve` fails:

**Check if port 8888 is in use:**
```bash
lsof -ti:8888
```

**Use a different port:**
```bash
php artisan serve --port=8889
```

### Vite Not Injecting Assets

If Vue components don't work:

1. Check that Vite dev server is running
2. Check browser console for errors
3. Verify `resources/views/partials/head.view.php` includes Vite script:
```php
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/resources/js/app.js"></script>
```

### Database Not Found

If you get database errors:

**Create the database:**
```bash
touch database/database.sqlite
```

**Run migrations:**
```bash
php artisan migrate
```

### Verification Tests Failing Unexpectedly

If tests fail after you've fixed the code:

1. Check the exact error message
2. Verify you fixed the correct file (challenge folder, not main framework)
3. Check for syntax errors: `php -l path/to/file.php`
4. Review the test specification in `challenges/*/tests.php`

---

## Complete Testing Checklist

Use this checklist to verify everything works:

- [ ] PHP server starts successfully
- [ ] Vite dev server starts successfully
- [ ] Welcome page loads at http://localhost:8888
- [ ] Vue components render correctly
- [ ] Tailwind CSS v4 styles are applied
- [ ] Challenge 1 (broken-routing) verification works
- [ ] Challenge 2 (broken-middleware) verification works
- [ ] Challenge 3 (broken-auth) verification works
- [ ] Challenge 4 (broken-database) verification works
- [ ] Challenge 5 (broken-session) verification works
- [ ] Progress logs are written to storage/logs/challenges.log
- [ ] All 5 challenges can be fixed and verified

---

## Next Steps

After testing everything:

1. **Break the challenges again** - Reset them to broken state for learners
2. **Add frontend UI** - Create Vue pages for /learn route
3. **Add Docker support** - Containerize the application
4. **Create video tutorials** - Record walkthroughs of each challenge
5. **Add more challenges** - Expand the learning content

---

## Getting Help

If you encounter issues:

1. Check this guide's troubleshooting section
2. Review the documentation in `docs/`
3. Check the lesson READMEs in `course/lessons/`
4. Review challenge READMEs in `course/challenges/`
5. Check the verification system docs: `docs/VERIFICATION_SYSTEM.md`

---

## Summary

DALT.PHP is now a complete interactive debugging playground with:
- 5 comprehensive lessons explaining backend concepts
- 5 broken challenges with realistic bugs
- Automatic verification system with 19 tests
- Vue 3 + Tailwind v4 frontend
- Progress tracking and logging
- Beginner-friendly hints and feedback

Happy debugging! 🐛🔧
