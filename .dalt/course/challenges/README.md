# Challenges

This directory contains intentionally broken backend scenarios for debugging practice.

Each challenge presents a bug in the DALT.PHP framework that you must identify and fix.

## Structure

Each challenge is a self-contained directory with:
- `meta.json` - **Required.** Validated metadata (title, description, order, difficulty, bugs, lesson, color)
- `README.md` - Bug description, learning objective, and hints
- `tests.php` - Test specification for verification
- Broken code files (framework/, routes/, Http/controllers/)

### Adding a New Challenge

1. Create a folder: `challenges/your-challenge-name/`
2. Add `meta.json`:
   ```json
   {
     "title": "Broken Something",
     "description": "Short description",
     "order": 21,
     "difficulty": "Easy",
     "bugs": 1,
     "lesson": "02-routing",
     "color": "green"
   }
   ```
3. Add `README.md`, `tests.php`, and broken code. The platform auto-discovers it.

`order` must be a unique positive integer, `difficulty` is `Easy`, `Medium`, or `Hard`, `bugs` is positive, and `lesson` must name a discovered lesson. The challenge inherits that lesson's icon. Invalid metadata stops catalog loading with an actionable error instead of hiding the challenge.

Broken files must map to the challenge mutation allowlist: `framework/Core/**/*.php`, `routes/routes.php`, `Http/controllers/**/*.php`, `database/migrations/*.sql`, `Dockerfile`, `docker-compose.yml`, or `nginx/*.conf`. Symbolic links, hard-linked files, traversal segments, and any other destination are rejected before learner files change.

`challenge:start` snapshots only that challenge's destination files in a per-run recovery manifest before copying broken code. `challenge:reset` reapplies the broken files without replacing the snapshot. `challenge:stop` restores files that existed, removes files/directories created by the run, and leaves unrelated learner files alone. Only one challenge transaction may be active at a time.

## Challenge Types

The current catalog contains 20 challenges across framework, Docker, and PostgreSQL concepts. Catalog display and numbering follow explicit `order`; lesson pages find related challenges through the validated `lesson` relationship. The metadata files are authoritative, so this guide does not duplicate an inventory that can become stale.

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
