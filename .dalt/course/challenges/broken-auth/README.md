# Challenge: Broken Authentication

## Difficulty: Intermediate

## Setup Instructions

1. **Backup your current Authenticator:**
   ```bash
   cp framework/Core/Authenticator.php framework/Core/Authenticator.php.backup
   ```

2. **Copy the broken Authenticator:**
   ```bash
   cp challenges/broken-auth/framework/Core/Authenticator.php framework/Core/
   ```

3. **Copy the controller files:**
   ```bash
   cp -r challenges/broken-auth/Http/controllers/auth Http/controllers/
   ```

4. **Add routes (append to routes/routes.php):**
   ```bash
   cat challenges/broken-auth/routes/routes.php >> routes/routes.php
   ```

5. **Ensure users table exists:**
   ```bash
   php artisan migrate
   ```

6. **Start the server:**
   ```bash
   php artisan serve
   ```

7. **Test the broken authentication:**
   - Visit http://localhost:8000/auth/register
   - Register a new user (this works!)
   - Try to login with the same credentials (this fails!)

## Concept: How Authentication Works

Authentication verifies user identity through:

1. **Registration:**
   - User submits email/password
   - Password is hashed with `password_hash()`
   - User stored in database with hashed password

2. **Login:**
   - User submits email/password
   - System finds user by email
   - Password verified with `password_verify()`
   - If valid, user data stored in session

3. **Session:**
   - `$_SESSION['user']` stores authenticated user
   - Persists across requests
   - Checked by Auth middleware

## The Bug

### Password Verification Uses Plain Text Comparison

**Symptom:** Login always fails with "Invalid credentials" even with correct password.

**What's happening:**
```php
// BROKEN - plain text comparison
if ($password == $user['password']) {
    // This never matches because $user['password'] is hashed!
}

// CORRECT - password verification
if (password_verify($password, $user['password'])) {
    // This correctly verifies hashed passwords
}
```

**Why it's broken:**
- During registration, passwords are hashed: `password_hash('password123', PASSWORD_BCRYPT)` → `$2y$10$abc...xyz`
- During login, the code compares plain text password against the hash: `'password123' == '$2y$10$abc...xyz'`
- This comparison always fails because the hash doesn't match the plain text

**Security note:** Even if this worked, using `==` for password comparison is insecure. Always use `password_verify()`.

## Learning Objectives

After fixing this challenge, you will understand:
- Why passwords must be hashed
- How `password_hash()` and `password_verify()` work
- Why plain text comparison fails with hashed passwords
- How authentication flow works from registration to login
- Why timing-safe comparison matters

## Debugging Hints

1. **Check what's stored** - Add `dd($user)` in `Authenticator::attempt()` to see the hashed password
2. **Compare values** - Add `dd($password, $user['password'])` to see plain vs hashed
3. **Test password_verify** - Add `dd(password_verify($password, $user['password']))` to test verification
4. **Trace the flow** - Add `dd('Login attempt')` at various points to trace execution

## Files to Investigate

- `framework/Core/Authenticator.php` - Login logic (Bug is here!)
- `Http/controllers/auth/register-post.php` - See how passwords are hashed during registration
- `Http/controllers/auth/login-post.php` - See how login is attempted
- `framework/Core/Middleware/Auth.php` - See how authentication is checked

## How to Fix

Replace the plain text comparison with `password_verify()`:

```php
// In framework/Core/Authenticator.php
public function attempt($email, $password)
{
    $user = App::resolve(Database::class)->query('select * from users where email = :email', [
        'email' => $email
    ])->find();

    if ($user) {
        // Fixed: Use password_verify() instead of ==
        if (password_verify($password, $user['password'])) {
            $this->login([
                'email' => $email,
            ]);

            return true;
        }
    }
    return false;
}
```

## Success Criteria

When fixed correctly:
- ✅ Users can register with hashed passwords
- ✅ Users can login with correct credentials
- ✅ Login fails with incorrect credentials
- ✅ Session persists after login
- ✅ Protected routes are accessible after login

## Testing Your Fix

1. **Register a new user:**
   ```
   Visit: http://localhost:8000/auth/register
   Email: test@example.com
   Password: password123
   ```

2. **Verify password is hashed in database:**
   ```bash
   sqlite3 public/database/app.sqlite
   SELECT email, password FROM users;
   # Should see hashed password like: $2y$10$...
   ```

3. **Login with same credentials:**
   ```
   Visit: http://localhost:8000/auth/login
   Email: test@example.com
   Password: password123
   # Should redirect to dashboard
   ```

4. **Verify session:**
   ```php
   // Add to any controller
   dd($_SESSION);
   // Should see: ['user' => ['email' => 'test@example.com']]
   ```

## Understanding Password Hashing

### Why Hash Passwords?

**Never store plain text passwords!** If your database is compromised, attackers get all passwords.

### How password_hash() Works

```php
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);
// Result: $2y$10$abcdefghijklmnopqrstuvwxyz123456789...

// Same password, different hash each time (due to random salt)
$hash2 = password_hash($password, PASSWORD_BCRYPT);
// Result: $2y$10$zyxwvutsrqponmlkjihgfedcba987654321...
```

### How password_verify() Works

```php
$password = 'password123';
$hash = '$2y$10$abcdefghijklmnopqrstuvwxyz123456789...';

password_verify($password, $hash);  // true
password_verify('wrong', $hash);    // false
```

`password_verify()` extracts the salt from the hash and recomputes, then compares securely.

## Cleanup

After completing the challenge:
```bash
# Restore original Authenticator
cp framework/Core/Authenticator.php.backup framework/Core/Authenticator.php

# Remove challenge controllers (optional)
rm -rf Http/controllers/auth
```

## Related Lesson

**Lesson 04: Authentication System** - Study this before attempting the challenge.

## Next Challenge

After mastering authentication, try **Challenge: Broken Database**
