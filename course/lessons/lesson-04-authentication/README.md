# Lesson 04: Authentication System

## Overview

Authentication allows users to log in and access protected resources. Understanding how authentication works is crucial for debugging login issues and session problems.

## Learning Objectives

By the end of this lesson, you will understand:
- How user registration works
- How login authentication works
- How sessions store user data
- How logout destroys sessions
- How to debug authentication issues

## Authentication Components

### 1. Authenticator Class (`framework/Core/Authenticator.php`)

The Authenticator handles login and logout:

```php
class Authenticator
{
    public function attempt($email, $password)
    {
        // Query database for user
        $user = App::resolve(Database::class)
            ->query('SELECT * FROM users WHERE email = :email', [
                'email' => $email
            ])->find();

        if ($user) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                $this->login(['email' => $email]);
                return true;
            }
        }
        return false;
    }

    public function login($user)
    {
        $_SESSION['user'] = ['email' => $user['email']];
        session_regenerate_id(true);
    }

    public function logout()
    {
        Session::destroy();
    }
}
```

## Registration Flow

### Step 1: User Submits Registration Form

```html
<form method="POST" action="/register">
    <?= csrf_field() ?>
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Register</button>
</form>
```

### Step 2: Controller Validates Input

```php
// Http/controllers/registration/store.php

$email = $_POST['email'];
$password = $_POST['password'];

// Validate
$errors = [];
if (!Validator::email($email)) {
    $errors['email'] = 'Invalid email';
}
if (!Validator::string($password, 7, 255)) {
    $errors['password'] = 'Password must be at least 7 characters';
}

if (!empty($errors)) {
    return view('registration/create.view.php', ['errors' => $errors]);
}
```

### Step 3: Hash Password and Store User

```php
$db = App::resolve(Database::class);

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$db->query('INSERT INTO users (email, password) VALUES (:email, :password)', [
    'email' => $email,
    'password' => $hashedPassword
]);
```

### Step 4: Log User In

```php
$auth = new Authenticator();
$auth->login(['email' => $email]);

redirect('/');
```

## Login Flow

### Step 1: User Submits Login Form

```html
<form method="POST" action="/login">
    <?= csrf_field() ?>
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
```

### Step 2: Controller Attempts Authentication

```php
// Http/controllers/session/store.php

$email = $_POST['email'];
$password = $_POST['password'];

$auth = new Authenticator();

if ($auth->attempt($email, $password)) {
    redirect('/');
} else {
    $errors = ['email' => 'Invalid credentials'];
    return view('session/create.view.php', ['errors' => $errors]);
}
```

### Step 3: Authenticator Verifies Credentials

```php
public function attempt($email, $password)
{
    // 1. Find user by email
    $user = App::resolve(Database::class)
        ->query('SELECT * FROM users WHERE email = :email', [
            'email' => $email
        ])->find();

    // 2. Check if user exists
    if ($user) {
        // 3. Verify password
        if (password_verify($password, $user['password'])) {
            // 4. Log user in
            $this->login(['email' => $email]);
            return true;
        }
    }
    
    return false;
}
```

### Step 4: Session Stores User Data

```php
public function login($user)
{
    // Store user in session
    $_SESSION['user'] = ['email' => $user['email']];
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}
```

## Session Management

### How Sessions Work

1. **Session Start** - `session_start()` in `public/index.php`
2. **Session Storage** - Data stored in `$_SESSION` superglobal
3. **Session Cookie** - Browser receives session ID cookie
4. **Session Persistence** - Data persists across requests

### Session Data Structure

```php
$_SESSION = [
    'user' => [
        'email' => 'user@example.com'
    ],
    '_csrf' => 'abc123...',
    '_flash' => [
        'errors' => [...],
        'old' => [...]
    ]
];
```

### Checking Authentication Status

```php
// Check if user is logged in
if ($_SESSION['user'] ?? false) {
    // User is authenticated
} else {
    // User is not authenticated
}
```

## Logout Flow

### Step 1: User Clicks Logout

```html
<form method="POST" action="/logout">
    <?= csrf_field() ?>
    <button type="submit">Logout</button>
</form>
```

### Step 2: Controller Calls Logout

```php
// Http/controllers/session/destroy.php

$auth = new Authenticator();
$auth->logout();

redirect('/');
```

### Step 3: Session Destroyed

```php
public function logout()
{
    Session::destroy();
}

// In Session::destroy()
public static function destroy()
{
    $cookieName = session_name();
    static::flush();  // Clear $_SESSION
    
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();  // Destroy session file
    }
    
    // Delete session cookie
    $params = session_get_cookie_params();
    setcookie($cookieName, '', time() - 3600, 
        $params['path'], $params['domain'], 
        $params['secure'], $params['httponly']);
}
```

## Password Security

### Hashing Passwords

**Never store plain text passwords!**

```php
// ❌ WRONG
$password = $_POST['password'];
$db->query('INSERT INTO users (password) VALUES (:password)', [
    'password' => $password  // Plain text!
]);

// ✅ CORRECT
$hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
$db->query('INSERT INTO users (password) VALUES (:password)', [
    'password' => $hashedPassword  // Hashed!
]);
```

### Verifying Passwords

```php
// ❌ WRONG
if ($password === $user['password']) {
    // This won't work with hashed passwords!
}

// ✅ CORRECT
if (password_verify($password, $user['password'])) {
    // Correctly verifies hashed password
}
```

## Protecting Routes with Auth Middleware

```php
// Require authentication
$router->get('/dashboard', 'dashboard.php')->only('auth');

// Require guest (not authenticated)
$router->get('/login', 'session/create.php')->only('guest');
```

## Common Authentication Issues

### Issue 1: Login Succeeds But User Not Authenticated
**Cause:** Session not started or session data not saved  
**Debug:** Check `session_start()` in `public/index.php`

### Issue 2: Password Verification Always Fails
**Cause:** Password not hashed during registration  
**Debug:** Check if `password_hash()` is used

### Issue 3: User Logged Out After Refresh
**Cause:** Session not persisting  
**Debug:** Check session configuration and cookie settings

### Issue 4: Can't Access Protected Routes
**Cause:** Auth middleware not working  
**Debug:** Check `$_SESSION['user']` exists

### Issue 5: Logout Doesn't Work
**Cause:** Session not properly destroyed  
**Debug:** Check `Session::destroy()` implementation

## Debugging Authentication

### Technique 1: Check Session Data
```php
// In controller
dd($_SESSION);
```

### Technique 2: Verify Password Hash
```php
// After registration
dd($hashedPassword);
```

### Technique 3: Test Password Verification
```php
// In login controller
dd([
    'input_password' => $password,
    'stored_hash' => $user['password'],
    'verify_result' => password_verify($password, $user['password'])
]);
```

### Technique 4: Trace Login Flow
```php
// In Authenticator::attempt()
dd($user, password_verify($password, $user['password']));
```

## Key Files

- **`framework/Core/Authenticator.php`** - Login/logout logic
- **`framework/Core/Session.php`** - Session management
- **`framework/Core/Middleware/Auth.php`** - Authentication guard
- **`framework/Core/Middleware/Guest.php`** - Guest guard

## Practice Exercise

1. Register a new user
2. Add `dd($_SESSION)` after login
3. Observe the session data
4. Logout and verify session is destroyed

## Next Steps

- **Lesson 05: Database** - How queries work
- **Challenge: Broken Auth** - Debug authentication issues

## Summary

Authentication in DALT.PHP:
1. **Registration** - Hash password, store user, log in
2. **Login** - Verify credentials, store in session
3. **Session** - Persist user data across requests
4. **Logout** - Destroy session and cookie
5. **Protection** - Use auth middleware on routes

Understanding authentication is essential for building secure applications and debugging login issues.
