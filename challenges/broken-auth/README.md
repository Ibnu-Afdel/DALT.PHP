# Challenge: Broken Authentication

## Difficulty: Intermediate

## Description

The authentication system is broken. Users can register but cannot log in, even with correct credentials. Additionally, the session doesn't persist after login.

## Bug Symptoms

1. Registration works - users are created in the database
2. Login always fails with "Invalid credentials" error
3. Even when login appears to succeed, user is not authenticated
4. Accessing protected routes redirects to login repeatedly

## Expected Behavior

- Users should be able to log in with correct email/password
- Session should persist across requests
- Protected routes should be accessible after login
- Logout should clear the session

## Learning Objective

Understand how authentication works, including password hashing, session management, and middleware protection.

## Files to Investigate

- `framework/Core/Authenticator.php` - Login/logout logic
- `framework/Core/Session.php` - Session management
- `framework/Core/Middleware/Auth.php` - Authentication guard
- `Http/controllers/session/store.php` - Login controller
- `Http/controllers/registration/store.php` - Registration controller

## Debugging Hints

1. Check if passwords are being hashed during registration
2. Verify password verification logic in `Authenticator::attempt()`
3. Inspect `$_SESSION` after login - is user data stored?
4. Check if `session_start()` is called in `public/index.php`
5. Add `dd($_SESSION)` in various places to trace session data

## Questions to Ask

- Are passwords hashed with `password_hash()` during registration?
- Is `password_verify()` used correctly during login?
- Is user data being stored in `$_SESSION['user']`?
- Is the session ID being regenerated for security?
- Does the Auth middleware check for `$_SESSION['user']`?

## Success Criteria

- Users can register with hashed passwords
- Users can log in with correct credentials
- Session persists across requests
- Protected routes are accessible after login
- Logout clears the session properly

## Related Lesson

**Lesson 04: Authentication System**

## Solution

(Hidden until you attempt the challenge)

The solution will be revealed after you've debugged the issue.
