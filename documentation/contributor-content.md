# Contributor Guide: Lessons and Challenges

This guide explains how to add educational content that is discoverable, reproducible, and connected to a real framework contract. A lesson teaches a competency; a challenge asks the learner to diagnose and repair a deliberately broken state.

## The content contract

Before writing prose, identify the current source and run the behavior. Every claim about DALT must come from executed code, a test, or a repeatable command. If the behavior is missing or different from the intended lesson, record the gap instead of describing the intention as if it were implemented.

Run the complete suite before closing a content change:

```bash
composer test
```

If the change touches a challenge, run its full lifecycle and leave no active fixture:

```bash
php artisan challenge:start <challenge-id> --force
php artisan challenge:verify
php artisan challenge:stop
```

The stop command must run before committing. Challenge files are copied into application paths during a run; they are not learner-owned source changes.

## Add a lesson

Create a directory with a safe ID such as `18-background-jobs` under `.dalt/course/lessons/`. It must contain `README.md` and `meta.json`.

The metadata object has exactly these fields:

```json
{
  "title": "Background Jobs",
  "description": "Understand how queued work moves outside the request",
  "order": 18,
  "icon": "lifecycle",
  "color": "blue",
  "prerequisites": ["01-request-lifecycle"]
}
```

Rules enforced by `Core\CourseLoader`:

- `title`, `description`, and `icon`/`color` are non-empty strings;
- `order` is a unique positive integer;
- `icon` is one of `lifecycle`, `routing`, `middleware`, `auth`, `database`, `session`, `docker`, `shield`, or `eye`;
- `color` is one of `blue`, `green`, `purple`, `red`, `yellow`, `orange`, or `gray`;
- `prerequisites` is a list of existing lesson IDs with no duplicates;
- every prerequisite has a lower `order` than the lesson;
- the metadata contains no unknown fields.

The loader orders lessons by `order`, not directory enumeration. Keep prerequisites dependency-based: a lesson should name what the learner must understand, not simply the previous directory.

## Structure a lesson for transfer

A useful lesson makes the learner observe a mechanism and then use it in a new context. Include:

1. observable objectives, written as things the learner can do;
2. prerequisites and the request or data path involved;
3. the current framework contract, with real file paths;
4. a prediction before the output or solution is shown;
5. a trace/debug exercise using repository files;
6. a small build exercise that is not copy-and-paste only;
7. a checkpoint asking the learner to explain the mechanism without the page;
8. linked challenges that require the lesson's competency;
9. a next step that follows the dependency graph.

Keep framework facts separate from comparison material. If you compare DALT with Laravel, name the Laravel version and link the documentation page you actually consulted. Do not copy a course example into public documentation without running it again.

## Add a challenge

Create a directory such as `.dalt/course/challenges/slow-query/` with:

- `meta.json`;
- `README.md`;
- `tests.php`;
- the broken files copied into the paths the challenge mutates.

Challenge metadata has exactly these fields:

```json
{
  "title": "Slow Query",
  "order": 21,
  "description": "Find the missing index behind a slow lookup.",
  "difficulty": "Medium",
  "bugs": 1,
  "lesson": "17-observability",
  "color": "orange"
}
```

`order` and `bugs` are positive integers. `difficulty` is `Easy`, `Medium`, or `Hard`; `lesson` must identify an existing lesson; `color` must be supported. The challenge inherits its lesson icon. A broken destination must stay inside the allowlist: `framework/Core/**/*.php`, `routes/routes.php`, `Http/controllers/**/*.php`, `database/migrations/*.sql`, `Dockerfile`, `docker-compose.yml`, or `nginx/*.conf`. Symlinks, hard links, traversal paths, and unrelated destinations are rejected.

The README should state the observed symptom, the competency, the files changed by `challenge:start`, the verification command, and staged hints. Do not put the complete patch before the learner has had a chance to observe the failure.

## Write verification checks

`tests.php` returns a non-empty array keyed by unique `snake_case` names:

```php
return [
    'uses_bound_parameter' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/posts/index.php',
        'search' => ':user_id',
        'hint' => 'Put the request value in a prepared-statement parameter.',
    ],
];
```

The supported check types are:

| Type | Use it for | Important limit |
|---|---|---|
| `file_contains` | one meaningful source property | ignores comments unless `include_comments` is true |
| `file_not_contains` | rejecting a known broken form | choose a specific defect, not broad prose |
| `function_call` | a real PHP function call | token-based; strings and comments do not count |
| `route_exists` | one route registration | checks the application route file |
| `route_order` | precedence between specific and generic routes | checks registration order |
| `session_key` | a required session operation | use only for a meaningful session contract |
| `class_contract` | required class interfaces or methods | framework declaration files only; the class is loaded in an isolated process |
| `handler_result` | actual controller behavior | controllers only; requires seeded SQL and an explicit response expectation |

`class_contract` and `handler_result` are the executable checks. Use `class_contract` when source shape is not enough to prove that a learner class still loads and exposes the interface consumed by the application. Use `handler_result` when a controller must run against seeded data and produce a status, row count, or body value. A source match that finds the right SQL in an unused variable is not behavioral proof.

For a `handler_result` check, make the seed self-contained and SQLite-compatible unless the challenge explicitly requires another environment:

```php
'returns_the_author' => [
    'type' => 'handler_result',
    'file' => 'Http/controllers/posts/index.php',
    'seed' => [
        'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)',
        "INSERT INTO users VALUES (1, 'Alice')",
    ],
    'expect' => [
        'status' => 200,
        'count' => 1,
        'contains' => 'Alice',
    ],
    'hint' => 'Run the handler against the seeded row and inspect the response.',
],
```

The verifier rejects unknown types, malformed fields, unsafe target paths, empty seed lists, invalid SQL values, duplicate check names, and checks that point outside the challenge's mutable files. It reads learner source as text for static checks and isolates executable probes so a broken learner file cannot poison the verifier process.

## Review checklist

Before calling a unit complete, verify all of these:

- the broken fixture fails before the learner changes it;
- the intended fix passes;
- a plausible incomplete fix still fails;
- the README, metadata, linked lesson, bug count, and tests agree;
- hints move from observation to location to concept without giving away the patch;
- the challenge starts, resets, and stops without overwriting unrelated learner work;
- executable checks test behavior where behavior is the competency;
- the lesson names prerequisites and gives the learner a transfer exercise;
- every behavior claim was run against the current repository;
- `composer test` is green and no challenge is active.

The durable audit record is updated in `docs/AUDIT_COVERAGE.md`, `docs/FINDINGS_LEDGER.md`, and `docs/WORKLOG.md`. Coverage state is set only from evidence: `Implemented` means code exists, `Verified` means it was run in this session, and `Aligned` means the content and related challenges agree with the verified contract.
