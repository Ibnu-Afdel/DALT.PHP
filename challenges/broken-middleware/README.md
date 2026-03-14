# Challenge: Broken Middleware

## Difficulty: Intermediate

## Description

The middleware system is not working correctly. Some middleware doesn't run at all, while other middleware blocks valid requests.

## Bug Symptoms

1. CSRF protection is not working - forms submit without tokens
2. Auth middleware allows unauthenticated users to access protected routes
3. Guest middleware doesn't redirect logged-in users away from login page
4. Middleware execution order seems random

## Expected Behavior

- CSRF middleware should validate tokens on POST requests
- Auth middleware should redirect unauthenticated users to login
- Guest middleware should redirect authenticated users to home
- Middleware should execute in the specified order

## Learning Objective

Understand how middleware is resolved, executed, and how it protects routes.

## Files to Investigate

- `framework/Core/Middleware/Middleware.php` - Middleware resolution
- `framework/Core/Middleware/Auth.php` - Authentication guard
- `framework/Core/Middleware/Guest.php` - Guest-only guard
- `framework/Core/Middleware/Csrf.php` - CSRF protection
- `framework/Core/Router.php` - Middleware invocation

## Debugging Hints

1. Check `Middleware::MAP` - are all middleware registered?
2. Verify `Middleware::resolve()` - is it calling `handle()` on each?
3. Look at middleware order in route definitions
4. Check if middleware is being called before the controller
5. Add `dd('Middleware running')` in each middleware's `handle()` method

## Questions to Ask

- How does `Middleware::resolve()` convert keys to class names?
- What happens if a middleware key is not in the MAP?
- In what order do multiple middleware execute?
- What happens when middleware fails (redirect vs abort)?
- Where in the request lifecycle does middleware run?

## Success Criteria

- All middleware executes when specified on routes
- CSRF tokens are validated on POST requests
- Auth middleware protects routes correctly
- Guest middleware works as expected
- Middleware executes in the correct order

## Related Lesson

**Lesson 03: Middleware System**

## Solution

(Hidden until you attempt the challenge)

The solution will be revealed after you've debugged the issue.
