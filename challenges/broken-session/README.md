# Challenge: Broken Session

## Difficulty: Beginner

## Description

Session data is not persisting across requests. Flash messages disappear immediately, and user authentication state is lost after page refresh.

## Bug Symptoms

1. Flash messages (errors, success) don't display
2. User is logged out after every page refresh
3. Session data appears to be lost between requests
4. Old form input is not available after validation errors

## Expected Behavior

- Session data should persist across requests
- Flash messages should display once and then disappear
- User authentication should remain active
- Old form input should be available after validation errors

## Learning Objective

Understand how sessions work, how flash data is managed, and how to debug session issues.

## Files to Investigate

- `framework/Core/Session.php` - Session management
- `public/index.php` - Session initialization
- `framework/Core/functions.php` - `old()` helper
- Controllers that use flash messages

## Debugging Hints

1. Check if `session_start()` is called in `public/index.php`
2. Verify `Session::unflash()` is called at the end of request
3. Look at how flash data is stored in `$_SESSION['_flash']`
4. Add `dd($_SESSION)` to see session contents
5. Check browser cookies to verify session ID is set

## Questions to Ask

- When is `session_start()` called?
- How does flash data differ from regular session data?
- What does `Session::unflash()` do?
- Why is `session_regenerate_id()` called after login?
- How does the `old()` helper retrieve form input?

## Success Criteria

- Session data persists across requests
- Flash messages display correctly
- User authentication remains active
- Old form input is available after validation errors
- Session cleanup works properly

## Related Lessons

- **Lesson 01: Request Lifecycle** (session initialization)
- **Lesson 04: Authentication** (session usage)

## Solution

(Hidden until you attempt the challenge)

The solution will be revealed after you've debugged the issue.
