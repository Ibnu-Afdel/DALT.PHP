# DALT Challenge Verification System

## Overview

The verification system automatically checks if learners have correctly fixed broken challenges. It provides instant feedback, hints, and tracks progress locally.

## How It Works

### 1. Test Specifications

Each challenge has a `tests.php` file defining expected behavior:

```php
// challenges/broken-routing/tests.php
return [
    'route_create_exists' => [
        'type' => 'route_exists',
        'route' => '/posts/create',
        'method' => 'get',
        'hint' => 'Make sure /posts/create route is registered'
    ],
    // ... more tests
];
```

### 2. Verification Engine

`Core/ChallengeVerifier.php` runs tests and returns results:

```php
$verifier = new ChallengeVerifier('challenges/broken-routing');
$result = $verifier->verify();

// Returns:
[
    'status' => 'pass' | 'fail',
    'message' => 'All tests passed!',
    'hint' => 'Next debugging step',
    'passed' => 3,
    'failed' => 1,
    'total' => 4,
    'results' => [...]
]
```

### 3. CLI Command

Learners run verification from terminal:

```bash
php artisan verify broken-routing
```

Output:
```
╔══════════════════════════════════════════════════════════════╗
║           DALT Challenge Verification System                ║
╚══════════════════════════════════════════════════════════════╝

Verifying: broken-routing
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✓ Route get /posts/create exists
✗ Route get /posts/{id}/edit not found
  💡 Hint: Uncomment the /posts/{id}/edit route in routes/routes.php
✓ Route order correct: specific before generic
✓ File does not contain problematic code

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Results: 3/4 tests passed
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

❌ 1 of 4 tests failed. Keep debugging!

💡 Next step: Uncomment the /posts/{id}/edit route in routes/routes.php

Keep debugging! Check the challenge README for more hints.
```

## Test Types

### route_exists

Checks if a route is registered in `routes/routes.php`:

```php
[
    'type' => 'route_exists',
    'route' => '/posts/create',
    'method' => 'get',
    'hint' => 'Register the route in routes/routes.php'
]
```

### route_order

Verifies specific routes come before generic routes:

```php
[
    'type' => 'route_order',
    'specific' => '/posts/create',
    'generic' => '/posts/{id}',
    'hint' => 'Move specific route before generic'
]
```

### file_contains

Checks if a file contains expected code:

```php
[
    'type' => 'file_contains',
    'file' => 'framework/Core/Authenticator.php',
    'search' => 'password_verify',
    'hint' => 'Use password_verify() function'
]
```

### file_not_contains

Checks if problematic code has been removed:

```php
[
    'type' => 'file_not_contains',
    'file' => 'framework/Core/Database.php',
    'search' => '->execute();',
    'hint' => 'Pass $params to execute()'
]
```

### session_key

Verifies correct session key usage:

```php
[
    'type' => 'session_key',
    'file' => 'framework/Core/Middleware/Auth.php',
    'key' => 'user',
    'hint' => "Use \$_SESSION['user'] not 'authenticated'"
]
```

### function_call

Checks if a specific function is called:

```php
[
    'type' => 'function_call',
    'file' => 'framework/Core/Authenticator.php',
    'function' => 'password_verify',
    'hint' => 'Use password_verify() to check passwords'
]
```

## Progress Tracking

Results are logged to `storage/logs/challenges.log`:

```
[2026-03-14 10:30:15] broken-routing - fail (2/4)
[2026-03-14 10:35:22] broken-routing - pass (4/4)
[2026-03-14 11:00:45] broken-middleware - fail (1/4)
```

## Learner Workflow

1. **Study the lesson**
   ```bash
   # Read lessons/lesson-02-routing/README.md
   ```

2. **Copy broken files**
   ```bash
   cp challenges/broken-routing/routes/routes.php routes/
   cp -r challenges/broken-routing/Http/controllers/posts Http/controllers/
   ```

3. **Test the bug**
   ```bash
   php artisan serve
   # Visit http://localhost:8000/posts/create
   ```

4. **Fix the code**
   ```bash
   # Edit routes/routes.php
   # Move /posts/create before /posts/{id}
   # Uncomment /posts/{id}/edit
   ```

5. **Verify the fix**
   ```bash
   php artisan verify broken-routing
   ```

6. **See results**
   - ✅ Pass: Challenge complete!
   - ❌ Fail: Get hints and keep debugging

## Adding New Challenges

### Step 1: Create Challenge Files

```
challenges/my-challenge/
├── README.md
├── tests.php          # Test specification
├── routes/
└── Http/controllers/
```

### Step 2: Define Tests

```php
// challenges/my-challenge/tests.php
return [
    'test_name' => [
        'type' => 'file_contains',
        'file' => 'path/to/file.php',
        'search' => 'expected code',
        'hint' => 'What to do if test fails'
    ]
];
```

### Step 3: Test Verification

```bash
php artisan verify my-challenge
```

## Safety Features

### No Code Execution

- Tests only read files and check patterns
- No `eval()` or arbitrary code execution
- Safe even with partially broken user code

### Local Only

- No network requests
- No external dependencies
- Works completely offline

### Non-Destructive

- Verification never modifies files
- Only reads and compares
- Framework remains stable

## Exit Codes

- `0` - All tests passed
- `1` - Some tests failed or error occurred

Useful for CI/CD or automated testing:

```bash
php artisan verify broken-routing && echo "Success!" || echo "Failed"
```

## Troubleshooting

### "No tests.php found"

Make sure the challenge has a `tests.php` file:
```bash
ls challenges/broken-routing/tests.php
```

### "Challenge not found"

Check the challenge name:
```bash
php artisan verify broken-routing  # Correct
php artisan verify routing          # Wrong
```

### Tests pass but code still broken

Tests check for common fixes. Your code might have other issues. Check:
- PHP syntax errors
- Logic errors not covered by tests
- Database connection issues

## Future Enhancements

- Web UI for verification (Milestone 3)
- More test types (HTTP requests, database queries)
- Difficulty levels
- Achievement system
- Leaderboards (optional)

## Summary

The verification system provides:
- ✅ Instant feedback on fixes
- ✅ Helpful hints for debugging
- ✅ Progress tracking
- ✅ Safe, local verification
- ✅ Beginner-friendly CLI
- ✅ Extensible test framework

Learners can now fix challenges and immediately know if they're correct!
