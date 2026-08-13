# Lesson 19: Testing a Framework Contract

## What you will be able to do

By the end of this lesson, you can:

- explain what a "contract" is, distinct from an implementation, and why a test should prove the former;
- choose between a unit test and a feature test based on what actually needs to boot;
- explain why DALT's feature tests run the real front controller in a subprocess instead of calling it in-process;
- name the specific isolation `tests/TestCase.php` resets before and after every test, and why;
- write a Pest test that would fail against a plausible-but-wrong fix, not just against an obviously broken one;
- read `ChallengeVerifier`'s own two families of checks as a worked example of the exact same problem at a different scale.

## Recommended prerequisite

Complete [Lesson 05: Database](../05-database/README.md) and [Lesson 18: Errors, Exceptions, and Debugging](../18-debugging-and-logging/README.md) first. Several examples below reuse `ExceptionHandler` and the database seeding pattern from those two lessons; this lesson does not re-explain them.

## The problem a framework must solve

You have already been told to run tests three times before reaching this lesson — L01, L02, and L05 all say `composer test -- --filter='...'` — without ever being shown how to write one. That gap is deliberate up to this point and closes here.

"I tried it and it worked" and "I wrote a test that proves it" are different claims, and the difference is not effort — it is what each one can rule out:

```text
manual check:  proves the one input you tried, once, in the state your app happened to be in
automated test: proves the contract, every time, including inputs you didn't think to try by hand
```

A contract is the behavior a caller is entitled to rely on — not how it is currently implemented, but what it promises: "redeeming an already-used coupon is rejected," not "line 14 checks a boolean." A test that asserts the implementation detail breaks the moment you refactor something that was never part of the promise; a test that asserts the contract survives a rewrite and catches a regression either way.

## Predict before reading the source

For each Pest test below, predict whether it needs the whole application booted, and why:

| Test | Boots the app? | Reason |
|---|---|---|
| `new HttpException(404, 'Missing article')`, assert `statusCode` | ? | ? |
| `GET /` through the real front controller, assert the body contains `<title>` | ? | ? |
| `(new ExceptionHandler(debug: true))->render($exception)`, assert the body | ? | ? |
| Two sequential requests, assert a flashed value survives the first and expires on the second | ? | ? |

Then check every answer against `tests/Unit/HttpExceptionTest.php`, `tests/Feature/RequestLifecycleTest.php`, `tests/Unit/ExceptionHandlerTest.php`, and `tests/Unit/SessionTest.php`.

## 1. Unit tests exercise a class; feature tests exercise the application

`tests/Unit/HttpExceptionTest.php` never touches routing, middleware, or the database:

```php
test('http exceptions expose an immutable error status and useful message', function () {
    $exception = new HttpException(404, 'Missing article');

    expect($exception->statusCode)->toBe(404)
        ->and($exception->getCode())->toBe(404)
        ->and($exception->getMessage())->toBe('Missing article');
});
```

This is a **unit** test: one class, constructed directly, asserted against directly. It runs in microseconds because nothing else exists yet.

`tests/Feature/RequestLifecycleTest.php` is a different shape entirely:

```php
test('the front controller renders an http exception as a response', function () {
    $response = (new ApplicationTestClient())->request('GET', '/missing-route');

    expect($response->exitCode)->toBe(0)
        ->and($response->statusCode)->toBe(404)
        ->and($response->body)->toBe('<h1>404</h1><p>Not Found</p>');
});
```

This is a **feature** test: it drives the system the way an HTTP client actually would — through the router, through `ExceptionHandler`, through `Response::send()` — and asserts on what came out the other end. It is slower, and it is the only kind of test that can catch a defect in how the pieces are wired together rather than in one piece alone.

Neither is "better." A unit test that boots the whole application to check one `if` statement is slow for no reason; a feature test that never exercises the real dispatch path cannot prove the wiring works. Choose based on what you are actually trying to prove a promise about.

## 2. DALT's feature tests run in a real subprocess, on purpose

`tests/Support/ApplicationTestClient.php` does not call `public/index.php`'s logic in-process. It shells out:

```php
$process = proc_open(
    [PHP_BINARY, __DIR__ . '/run-application.php', $payload],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    BASE_PATH,
);
```

`run-application.php` boots a real, separate PHP process, defines `BASE_PATH` fresh, requires the real front controller, and reports back the status, body, headers, and any fatal error as one JSON payload over a marker in stdout. The comment above `request()` gives the reason directly: "constants, sessions, headers, and fatal errors cannot leak into the PHPUnit process." `BASE_PATH` is a `define()`d constant — it can only be set once per process. A fatal error in the application under test would otherwise kill the entire test run, not just the one test. This is the same isolation principle Lesson 05's `handler_result` check and Lesson 18's probe controller both depend on, applied to the test suite itself rather than to a challenge verifier.

## 3. State isolation is not optional, and DALT resets it explicitly

Every test — unit or feature — extends `Tests\TestCase`, which resets a specific, enumerated list of superglobals and framework state before and after each test:

```php
protected function setUp(): void
{
    $this->serverBeforeTest = $_SERVER;
    // ...similarly for $_GET, $_POST, $_COOKIE, $_FILES, $_REQUEST, $_ENV, $_SESSION,
    // the current container, and the last HTTP response code...

    $_GET = [];
    $_POST = [];
    // ...
    http_response_code(200);
    header_remove();
}

protected function tearDown(): void
{
    $_SERVER = $this->serverBeforeTest;
    // ...restore everything else, in the same order...
}
```

This is what "state isolation" means concretely: it is not a slogan, it is this explicit save-then-restore pair, naming every mutable global the framework can touch. Skip it and a value one test writes into `$_SESSION` or `$_ENV` is still there when the next test runs — a test that passes only when it runs after a specific other test is not proving anything about the contract; it is proving something about test order, which is exactly the kind of false positive this whole lesson exists to prevent.

## 4. A test that cannot fail is not a test

This is the part that connects back to why this course was hardened. `ChallengeVerifier` — the thing that grades every challenge in this course, including the one linked to this lesson — has exactly two families of checks, and the difference between them is this lesson's whole point at platform scale:

```text
file_contains       — is a specific string present in the source?
handler_result       — what did the code actually do when it ran?
```

`documentation/contributor-content.md` states the risk of the first kind plainly: "A source match that finds the right SQL in an unused variable is not behavioral proof." A `file_contains` check for `rollBack()` passes if that call exists anywhere in the file — including inside a comment, a dead `if (false)` branch, or a `catch` block that reaches an unconditional `commit()` right after it. It cannot tell "this runs" from "this is spelled correctly." A `handler_result` check runs the actual controller against a seeded database and inspects the real response and the real row left behind; it fails the exact fake fix a keyword search would let through.

Your own tests have the same two failure modes. A test that only calls a method and checks it did not throw is closer to `file_contains` than you might think — it proves the method exists and the happy path runs, and nothing about whether the *contract* holds under the case someone will actually hit in production.

## Experiment: catch a plausible-but-wrong fix

Take `tests/Unit/HttpExceptionTest.php`'s rejection test:

```php
test('http exceptions reject statuses outside the error range', function (int $status) {
    new HttpException($status);
})->with([399, 600])->throws(InvalidArgumentException::class);
```

1. Run it: `composer test -- --filter='reject statuses outside'`. It passes.
2. Now imagine a "fix" to `HttpException`'s constructor that only rejects status codes above 599, silently accepting anything below 400 — including negative numbers. Would this test catch it? Check: does `399` alone prove the *lower* bound, or only that one specific value near it?
3. Add a case the current list does not cover — `0` — and rerun. If it still passes, the contract ("400 to 599, inclusive, nothing else") was only ever partially proven; you have now proven more of it.

This is the entire skill: a test that passes today is not evidence by itself. A test that would *fail* against a specific, realistic wrong implementation is.

## Debugging checklist for tests themselves

- **A test passes locally but fails in CI, or vice versa.** Suspect leaked state — a superglobal, a static property, or a file left behind by a previous test — before suspecting the environment.
- **A test only fails when run after another specific test.** `TestCase::setUp()`/`tearDown()` did not reset something your test or the framework touches; find the missing global.
- **A "regression" test never actually fails when you revert the fix.** It is testing something adjacent to the bug, not the bug. Comment out the fix and confirm the test goes red before trusting it.
- **A feature test is slow and you are tempted to make it a unit test.** Ask what it is actually proving. If the answer requires the router, middleware, or `Response::send()`, it has to stay a feature test; slow-but-correct beats fast-but-blind.
- **You cannot explain what real-world mistake a test would catch.** Write that mistake down first, then write the assertion that fails against it. Assertions written after the fix tend to describe the fix, not the contract.

## Trace exercise

Read these files in order and, for each, write down what it isolates or proves and what it deliberately leaves to a different file:

1. `tests/Pest.php` — how every test file gets `Tests\TestCase`'s isolation
2. `tests/TestCase.php` — the exact reset list
3. `tests/Support/ApplicationTestClient.php` and `tests/Support/run-application.php` — subprocess isolation
4. `tests/Unit/HttpExceptionTest.php` — a unit test
5. `tests/Feature/RequestLifecycleTest.php` — the matching feature tests, including the production-500 case from Lesson 18
6. `.dalt/Core/ChallengeVerifier.php` — `file_contains` versus `handler_result`, at the platform scale

Then run:

```bash
composer test -- --filter='HttpException|RequestLifecycle|ExceptionHandler'
```

## Laravel bridge

Compared against Laravel 13.x [Testing: Getting Started](https://laravel.com/docs/13.x/testing) (consulted 2026-08-13).

| Laravel 13.x | DALT |
|---|---|
| `tests/Unit` does not boot the application; `tests/Feature` does, via `Tests\TestCase` extending `Illuminate\Foundation\Testing\TestCase` | the same `Unit`/`Feature` split, but neither directory boots the framework in-process — feature tests boot a real subprocess instead |
| in-process HTTP testing (`$this->get('/users')->assertOk()`) against a booted kernel | out-of-process HTTP testing (`ApplicationTestClient::request()`) against a real front-controller run |
| `RefreshDatabase` trait migrates a real test database once per run and wraps each test in a transaction | `handler_result`-style checks build a throwaway `:memory:` SQLite database per check; application feature tests that touch the database seed their own state per test |
| parallel testing via `paratest`, with per-process suffixed test databases | tests run sequentially; isolation comes from `TestCase::setUp()`/`tearDown()` and, for feature tests, a fresh subprocess per request |
| both Pest and PHPUnit supported out of the box, `php artisan test` as a unified runner | Pest only, run through `composer test` |

Laravel's in-process HTTP testing is faster because the framework boots once and Laravel controls every global it touches. DALT's subprocess approach is slower but needs no such inventory — it is correct by construction, because nothing survives past the child process exiting. Both are legitimate answers to state isolation; DALT chose simplicity of guarantee over speed, which is a genuine trade-off worth naming, not a limitation to work around.

## DALT boundary

DALT does not provide a database-refresh trait, parallel test runners, HTTP test assertion sugar (`assertOk()`, `assertJson()`), or a mocking framework beyond what Pest itself ships. Every feature test either drives the real subprocess (`ApplicationTestClient`) or, like `handler_result`, dispatches a controller directly against a throwaway database. There is no abstraction between what you write and what actually ran.

## Complete the challenge

```bash
php artisan challenge:start untested-contract
php artisan challenge:verify
```

The fixture's coupon-redemption endpoint has exactly the shape of bug a single manual try cannot find — it looks correct the first time you use it. Fix the contract violation, verify, then:

```bash
php artisan challenge:stop
```

## Transfer exercise

Pick any small behavior from an earlier lesson's challenge — `broken-database`'s injection-safe search, or `broken-session`'s flash precedence, are both good candidates — and write a fresh Pest test for it from a blank file, without looking at that challenge's `tests.php`:

1. Name the contract in one sentence before writing any code.
2. Write the assertion.
3. Temporarily reintroduce the original bug (check the challenge's fixture files back out with `challenge:start <name> --force`) and confirm your test goes red.
4. Fix it again and confirm your test goes green.

If your test passed in both the broken and fixed state, it was not testing the contract — go back to step 1.

## Explain it back

Without opening the source, answer these from memory:

1. What is the difference between a contract and an implementation, and which one should a good test survive a refactor of?
2. Name one thing `TestCase::setUp()`/`tearDown()` resets and explain what would go wrong across two tests if it did not.
3. Why does `ApplicationTestClient` run the front controller in a real subprocess instead of calling it directly?
4. What can a `handler_result` check catch that a `file_contains` check structurally cannot?
5. Describe a test that passes both before and against a bug it was meant to catch. What does that prove, and what does it not prove?

If you can write a test that fails against a specific, realistic wrong implementation — not just against the seeded broken fixture — before opening any file to check, this lesson's checkpoint is complete.
