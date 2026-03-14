# Challenge: Broken Routing

## Difficulty: Beginner

## Description

The routing system is not working correctly. Some routes return 404 errors even though they're defined, and route parameters are not being extracted properly.

## Bug Symptoms

1. Visiting `/posts/123` returns 404 Not Found
2. The route `/posts/create` works, but `/posts/{id}` doesn't
3. When a route does match, `$_GET['id']` is empty in the controller

## Expected Behavior

- `/posts/123` should load the post with ID 123
- Route parameters should be available in `$_GET`
- Routes should match in the correct order

## Learning Objective

Understand how the router matches URLs to patterns and extracts parameters.

## Files to Investigate

- `framework/Core/Router.php` - Route matching logic
- `routes/routes.php` - Route definitions
- `Http/controllers/posts/show.php` - Controller expecting parameters

## Debugging Hints

1. Check the `matchUri()` method - is the regex correct?
2. Look at route order - are specific routes before generic ones?
3. Verify parameter injection - are params being added to `$_GET`?
4. Add `dd($params)` in `Router::route()` to see extracted parameters

## Questions to Ask

- How does the router convert `{id}` to a regex pattern?
- What happens if two routes match the same URL?
- Where are route parameters injected into `$_GET`?
- Why does route order matter?

## Success Criteria

- All routes match correctly
- Route parameters are accessible in controllers
- No 404 errors on valid routes

## Related Lesson

**Lesson 02: Routing System**

## Solution

(Hidden until you attempt the challenge)

The solution will be revealed after you've debugged the issue.
