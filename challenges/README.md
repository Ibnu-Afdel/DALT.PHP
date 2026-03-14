# Challenges

This directory contains intentionally broken backend scenarios for debugging practice.

Each challenge presents a bug in the DALT.PHP framework that you must identify and fix.

## Structure

Each challenge is a self-contained directory with:
- `README.md` - Bug description, learning objective, and hints
- Broken code files (to be implemented later)
- Test cases to verify your fix
- Solution explanation (hidden until you solve it)

## Challenge Types

Challenges are organized by difficulty and concept:

### Routing Challenges
- **broken-routing** - Routes not matching correctly

### Authentication Challenges
- **broken-auth** - Login system not working

### Middleware Challenges
- **broken-middleware** - Middleware not executing properly

### Database Challenges
- **broken-database** - Query execution issues

### Session Challenges
- **broken-session** - Session data not persisting

## How to Approach Challenges

1. Read the challenge description
2. Understand the expected behavior
3. Trace the request lifecycle
4. Identify where the bug occurs
5. Fix the bug
6. Test your solution
7. Compare with the provided solution

## Debugging Tools

- `dd($variable)` - Dump and die for inspection
- Error logs in `storage/logs/app.log`
- Browser developer tools
- PHP error messages (when `APP_DEBUG=true`)

Good luck debugging!
