# Challenges

This directory contains intentionally broken backend scenarios for debugging practice.

Each challenge presents a bug in the DALT.PHP framework that you must identify and fix.

## Structure

Each challenge is a self-contained directory with:
- `meta.json` - **Required.** Metadata (title, difficulty, bugs, lesson link)
- `README.md` - Bug description, learning objective, and hints
- `tests.php` - Test specification for verification
- Broken code files (framework/, routes/, Http/controllers/)

### Adding a New Challenge

1. Create a folder: `challenges/your-challenge-name/`
2. Add `meta.json`:
   ```json
   {
     "title": "Broken Something",
     "difficulty": "Easy",
     "bugs": 1,
     "lesson": "02-routing",
     "description": "Short description"
   }
   ```
3. Add `README.md`, `tests.php`, and broken code. The platform auto-discovers it.

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
