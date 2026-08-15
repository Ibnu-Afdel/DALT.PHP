# API behaviour tests lab — FS06.1

A worked example of the tests FS06.1 asks you to write, against an API small enough to
read in one sitting.

## Run it

```sh
php vendor/bin/pest .dalt/course/fullstack/api-behavior-tests-lab/tests \
  --bootstrap=.dalt/course/fullstack/api-behavior-tests-lab/bootstrap.php
```

Ten tests should pass. If they do not, the lab is broken — report it rather than
debugging it, because nothing here depends on your project.

## What is in it

| File | What it is |
|---|---|
| `src/IssueApi.php` | The system under test: create, show, delete, over in-memory SQLite with real constraints and one two-write transaction |
| `tests/IssueApiTest.php` | Ten behaviour tests, each observing the response and the stored effect separately |
| `bootstrap.php` | Composer autoload plus the one class. The lab does not boot DALT |

Read `src/IssueApi.php` first. A test you cannot check against an implementation is a
test you are trusting rather than understanding.

## Why SQLite, and why in memory

Your project uses PostgreSQL, and it should. This lab uses in-memory SQLite for two
reasons: it runs with no configuration on any machine, and it gives each test a
genuinely fresh database — the strongest isolation available, with no cleanup to
forget and no order dependency to debug.

The tradeoff is real and worth knowing. SQLite is not PostgreSQL: type handling, error
messages, and concurrency all differ, and a test suite that runs against a different
engine than production can pass while production fails. For a lab about *what a test
observes*, that tradeoff is worth it. For your project, FS06.1 asks you to use a
separate PostgreSQL database instead, and that is the right call.

Note also `PRAGMA foreign_keys = ON` in `withSchema()`. SQLite does not enforce foreign
keys unless asked, so without that line the constraint would silently never fire and one
of the tests would be proving nothing.

## Prove the tests are real

A passing suite tells you nothing until you have watched it fail for the right reason.
Break the implementation on purpose and confirm which test catches it:

| Sabotage in `src/IssueApi.php` | Test that must fail |
|---|---|
| Replace `rollBack()` with `commit()` in the catch block | `a failed second write rolls back the first` |
| Add `status` to the INSERT from `$body['status']` | `an unaccepted field cannot reach the database` |
| Change `if ($errors !== [])` to `if (false)` | `a blank title is refused…` and `every invalid field…` |

Restore the file after each one and confirm the suite returns to ten passing. This is the
plausible-fake standard from the other side: a check that stays green when the behaviour
is broken is worse than no check, because it is actively misleading.
