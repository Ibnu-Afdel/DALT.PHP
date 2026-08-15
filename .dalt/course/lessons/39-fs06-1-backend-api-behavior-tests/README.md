# FS06.1 — Backend API behavior tests

Lesson ID: FS06.1  
Title: Backend API behavior tests  
Part: 06 — Testing, users and authentication  
Order: 1  
Status: Published  
Estimated effort: 110–140 minutes  
Difficulty: Integration  
Prerequisites: FS05.3 — CRUD, queries and transaction boundaries  
Project milestone: B06 — Multi-user protected system  
Primary source dossier: `FSO_PART_04.md`  
Last reviewed: 2026-08-14

## Why this matters

Your issue API worked while you watched it in a browser. That is useful evidence, but it is a
narrow sample: one data set, one sequence, and probably one happy path. A backend test is a
repeatable request through the same route, middleware, handler, validation, query, and response
boundary a real client uses. It answers a concrete claim: given this request and this known
database state, did the system produce this HTTP result and this stored effect?

That distinction matters *before* authentication multiplies the paths. Every rule you add in
FS06.2 and FS06.3 — login, sessions, CSRF, membership, ownership — doubles the number of
situations a handler can be in. Changing code in that state without tests is guesswork, and the
guess is usually "the part I was looking at is the part that broke."

A test is not proof that the application has no bugs. It is a durable witness for one behaviour.
And a green test that only calls a helper, or checks an implementation detail, can stay green
while the route is completely broken — which is worse than having no test, because it is
actively reassuring.

## Before you start

Complete FS05.3 and make its API persistent.

There is a worked example for this lesson. Run it before you write anything:

```sh
php vendor/bin/pest .dalt/course/fullstack/api-behavior-tests-lab/tests \
  --bootstrap=.dalt/course/fullstack/api-behavior-tests-lab/bootstrap.php
```

Ten tests should pass. `.dalt/course/fullstack/api-behavior-tests-lab/` contains a small issue
API and the ten behaviour tests that pin it. Read `src/IssueApi.php` first, then
`tests/IssueApiTest.php`; the lab README lists three sabotages to try. Everything below refers
to it, because a lesson about tests that ships no tests is asking you to take its word.

Then read `tests/Feature/RequestLifecycleTest.php` in this repository before copying an
assertion style. This project uses Pest with an application test client — not Laravel's
`getJson()` helper — and reaching for an API that does not exist here will cost you an hour.

```sh
php artisan test --filter=RequestLifecycle
php artisan test
```

For your own project, create a separate PostgreSQL database or test configuration. Tests must
never point at development data. Choose one reset strategy — migrate a scratch database, or wrap
each test in a transaction that is rolled back — and prove it gives the same result when tests
run alone and when they run together.

Going deeper in DALT Core — optional:

- [Request lifecycle](/learn/lessons/01-request-lifecycle) and [DALT database layer](/learn/lessons/11-dalt-db-layer) are reference material, not prerequisites.

## By the end

- write request-level tests for success, validation, absence, and stored effects;
- make test data isolated and deterministic;
- assert status, JSON contract, and database state as separate observations;
- run one focused test while diagnosing, then the complete suite;
- recognise what a behaviour test proves and what it does not.

## Predict before reading

1. Why can a test that calls `createIssue()` pass while `POST /api/issues` is broken?
2. Which assertion catches a handler returning 201 without writing a row?
3. Why can a test that passes alone fail in a full suite?
4. Is an invalid request test useful if it only expects a non-200 status?

## Mental model

~~~text
known database state → HTTP request → route → middleware → handler → SQL
        ↑                                                        ↓
 cleanup / rollback ← database assertion ← response assertion ← HTTP response
~~~

The test begins by owning its preconditions. It sends an external request rather than reaching
inside a controller. It then observes **two** outputs: the response a client can use, and the
durable state a later request would see. Cleanup makes the next test's preconditions truthful.

Those two observations are genuinely distinct. A response body cannot prove a transaction
committed, and a row cannot prove the public contract is right. Prediction 1's answer lives
here: calling `createIssue()` skips the route, the middleware, the JSON decoding, the status
code and the envelope — everything a client depends on — and tests only the part you were
already confident about.

## 1. Test the contract at the boundary

Start from a named behaviour, not a method name. "Creating a valid issue returns 201 and stores
it in its project" has an input, a visible output, and a lasting effect. Here is that test from
the lab:

```php
test('a valid issue is created, returned, and stored', function () {
    $response = $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => 'Write API tests',
        'priority' => 'high',
    ]);

    // 1. The response a client can use.
    expect($response['status'])->toBe(201);
    expect($response['body']['data']['title'])->toBe('Write API tests');
    expect($response['body']['data']['status'])->toBe('todo');   // the schema default

    // 2. The durable effect a later request would see.
    expect(countRows($this->api->pdo(), 'issues'))->toBe(1);

    // 3. Both writes of the business fact, not just the interesting one.
    expect(countRows($this->api->pdo(), 'activity'))->toBe(1);
});
```

Observation 2 is prediction 2's answer. A handler that validates, builds a response and returns
201 without ever reaching the INSERT passes every assertion above it. The row count is the only
line that notices.

Note what is *not* asserted: no timestamp, no auto-increment id compared against a literal, no
whole-JSON string match. Those change for reasons that have nothing to do with the behaviour
under test, and a test that fails for irrelevant reasons gets weakened or deleted.

One assertion in the lab is worth stealing outright:

```php
expect(array_keys($response['body']['data']))
    ->toBe(['id', 'projectId', 'title', 'status', 'priority']);
```

That pins the response's *key set*. Add a column in Part 08, forget to update the mapper, and
this test fails rather than silently publishing it. It is the cheapest guard against accidental
exposure you will ever write.

One more thing the lab does not do, and neither should you yet: it does not chase a coverage
number. Coverage measures which lines ran, not whether anything was checked — a test that calls
every handler and asserts nothing reports excellent coverage. Choose tests by asking which
behaviours you would be alarmed to lose, and write those. The list for an issue API is short and
you can name it in a minute.

Write helpers only after the second copy. `countRows` earns its place because nine tests use it.
A helper may remove request setup; it must never hide the assertion that makes the behaviour
meaningful — a `assertIssueWasCreated()` that wraps all three observations is a helper you will
one day trust without knowing what it checks.

## 2. Negative behaviour is part of the contract

An API with only success tests is unspecified exactly where a client most needs precision.
Prediction 4 asks whether "not 200" is enough, and it is not:

```php
// Weak. A 500 from a misspelled column satisfies this.
expect($response['status'])->not->toBe(200);

// Strong. This is the documented behaviour, and only this behaviour.
expect($response['status'])->toBe(422);
expect($response['body']['error']['code'])->toBe('validation_failed');
expect($response['body']['error']['fields']['title'])->toBe('Required');
expect(countRows($this->api->pdo(), 'issues'))->toBe(0);
```

The last line is the one people leave out. A handler that returns a beautiful 422 *after*
inserting the row passes the first three assertions. Every validation test needs its "and
nothing was written" observation.

Two more negative tests are worth writing early because they protect decisions rather than code
paths:

```php
test('every invalid field is reported, not just the first', function () {
    $response = $this->api->handle('POST', '/api/issues', ['title' => '', 'priority' => 'urgent']);

    expect(array_keys($response['body']['error']['fields']))
        ->toBe(['title', 'project_id', 'priority']);
});

test('an unaccepted field cannot reach the database', function () {
    $response = $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => 'Smuggling attempt',
        'status' => 'done',            // not accepted on create
    ]);

    expect($response['status'])->toBe(201);
    expect($response['body']['data']['status'])->toBe('todo');
});
```

The second is the allowlist regression test from FS05.1, and it becomes considerably more
important in FS06.3 when the smuggled field is `creator_id` rather than `status`.

Then cover absence and deletion. A missing issue is 404, not an empty 200. A delete returns 204
with no body, removes the row, and — this is the part people skip — returns 404 the second time,
so a client can distinguish "I deleted it" from "there was nothing there".

## 3. Make data deterministic

Prediction 3: tests influence each other whenever they share rows, id sequences, time, or
configuration. A test that assumes issue id 1 exists is really asserting its own position in the
run order, and it will fail the day someone adds a test above it.

The lab takes the strongest available approach:

```php
beforeEach(function () {
    // A fresh in-memory database per test. No cleanup to forget, no order
    // dependency, no leftover row from yesterday's browser session.
    $this->api = IssueApi::withSchema();
    $this->projectId = $this->api->seedProject('Website');
});
```

Each test creates the data it needs and keeps the returned ids. Nothing is assumed to pre-exist.

Your project cannot use in-memory SQLite — it must test against PostgreSQL, because a suite that
passes on a different engine than production can pass while production fails. So you need the
other strategy: a dedicated test database, migrated once, with each test wrapped in a
transaction that is rolled back afterwards. Rollback is faster than truncation and leaves no
residue.

Whichever you pick, verify the isolation rather than assuming it. Run one test twice in a row.
Run the whole suite. Run it in a shuffled order if your runner supports it. Expected behaviour
must not depend on yesterday's manual testing.

For behaviour spanning several writes, assert the database after the request. The FS05.3
transaction deserves two tests, and the second one is the important one:

```php
test('a failed second write rolls back the first', function () {
    // The activity table caps its message at 40 characters, so a long title
    // succeeds at the issue insert and fails at the activity insert.
    $longTitle = str_repeat('long ', 12);

    expect(fn () => $this->api->handle('POST', '/api/issues', [
        'project_id' => $this->projectId,
        'title' => $longTitle,
    ]))->toThrow(PDOException::class);

    expect(countRows($this->api->pdo(), 'issues'))->toBe(0);
    expect(countRows($this->api->pdo(), 'activity'))->toBe(0);
});
```

Those last two lines are what distinguish a rollback from a caught exception. Catching leaves
the first write committed; rollback does not. A test that only asserted the exception would pass
against both, and that is precisely the plausible fake this lesson exists to rule out.

Notice how the failure is provoked: through a real constraint, not by editing the code under
test. A test that requires you to sabotage production code in order to run is a test nobody will
run twice.

## 4. Testing a JSON API in this repository

The lab calls `handle()` directly, which keeps it small. Your project must go through DALT's
front controller, and here you will meet a genuine obstacle worth understanding rather than
working around.

`tests/Support/ApplicationTestClient` runs a real request in a separate PHP process:

```php
$response = (new ApplicationTestClient())->request('GET', '/api/issues/1');

expect($response->exitCode)->toBe(0)
    ->and($response->statusCode)->toBe(200)
    ->and($response->stderr)->toBe('');
```

The separate process is deliberate. Headers, `exit`, and fatal errors cannot leak into the
PHPUnit process, so a handler that dies takes its own process with it and reports honestly
instead of corrupting the run. That is also why `stderr` is worth asserting: a response can be
correct while the handler emitted a warning nobody noticed.

Now the obstacle. `request()` accepts `query` and `input`, which become `$_GET` and `$_POST`.
It has **no parameter for a raw request body** — read `tests/Support/run-application.php` and
you will find `$_POST = $payload['input']` and nothing that populates `php://input`. So a POST
handler that reads `php://input`, as FS05.1's JSON API must, cannot be driven end-to-end by this
client.

Do not respond by making the handler read `$_POST` instead. That would change your API from
JSON to form-encoded to suit a test, which is the tail wagging the dog. Instead use the seam
FS05.1 built in:

```php
function decodeJsonBody(Request $request, ?string $raw = null): array
{
    $raw ??= file_get_contents('php://input');
    // ...
}
```

Test the decoding and validation directly, with the raw body supplied:

```php
test('a blank title is rejected before any SQL runs', function () {
    $input = decodeJsonBody(new Request(), '{"title":"   ","project_id":"7"}');
    $result = validateIssueInput($input);

    expect($result->errors)->toHaveKey('title');
});
```

…and test the GET, PATCH and DELETE routes end-to-end through `ApplicationTestClient`, where no
raw body is needed. That split is not a compromise you should feel bad about. It is the normal
shape of testing at a boundary a test harness cannot fully reach: push the logic just inside the
boundary, where it is ordinary code, and keep the untestable part down to the one line that
reads the stream.

Record the gap in your notes. If you later add raw-body support to the test client, these tests
should move up to the route level — and knowing which ones and why is worth writing down now,
while the reason is fresh.

## 5. Watch your tests fail

A passing suite tells you nothing until you have seen it fail for the right reason. This is not
a ritual; it is the only way to distinguish a test that checks something from a test that checks
nothing.

The lab's README lists three sabotages. Do them:

| Sabotage in `src/IssueApi.php` | Test that must fail |
|---|---|
| `rollBack()` → `commit()` in the catch block | `a failed second write rolls back the first` |
| Add `status` to the INSERT from `$body['status']` | `an unaccepted field cannot reach the database` |
| `if ($errors !== [])` → `if (false)` | `a blank title is refused…` and `every invalid field…` |

Two things to check each time. Did the *expected* test fail — or did something else fail
instead, meaning your mental map of what covers what is wrong? And did the failure message
identify the lost behaviour, or did it just report an unhelpful mismatch?

Then do the same in your own project. Break one route and one validation rule, confirm the right
test fails with a message that names the problem, and restore them.

## Try it

Write one test for a valid create and one for an invalid create against your own API. Make the
invalid test fail first, by temporarily accepting blank titles — then restore the validator and
watch it pass. Then temporarily remove the insert and confirm the valid test fails on its
*database* assertion, not merely on its response assertion. If it fails on the response, you
have not yet written the observation that matters.

## Common mistakes

- Calling a controller function directly and calling the result an API test.
- Reusing development data, or assuming a fixed id.
- Asserting a whole JSON string, including changing ids and timestamps.
- Testing 422 but not proving that invalid input made no write.
- Asserting only that a request failed, so a 500 counts as correct behaviour.
- Asserting that a transaction threw, without asserting that nothing was committed.
- Letting helpers silently create unrelated data that hides a missing relation.
- Provoking failures by editing production code rather than through a real constraint.
- Stopping after a focused test and never running the suite that exposes leakage.

## When this goes wrong

Read the first failing assertion before changing any code. A 404 may mean the route is wrong
rather than the query; a 500 often means the test configuration did not select PostgreSQL. Log
the status and response body in the failing test only while investigating, and remove the noise
afterwards.

If tests pass alone but fail together, inspect every setup and teardown path and query the test
database between cases — something is leaking, and it is usually a fixture created outside a
transaction. If a test fails only on a second run, something is not being reset.

Never make a test pass by loosening an assertion whose behaviour is still required. If 422 has
become 500, the correct move is to find out why, not to accept both.

## Exercise

**Goal:** Turn the persistent issue API into a tested public contract.

**Starting state:** B05 has project and issue routes backed by PostgreSQL.

**Requirements:** Add request-level tests for create, invalid create, update, delete, project
relation, and pagination. Each test owns its fixture data. At least one test asserts both the
response and the database state; the invalid-create test proves no row was written; one test
pins the response key set; one test proves the two-write transaction rolls back.

**Verification:** Deliberately break one route and one validation rule. Show that the relevant
test fails with an assertion that identifies the lost behaviour, restore it, then run
`php artisan test` successfully.

**Mode: tool-run — Pest output and PostgreSQL test-state inspection.** This exercise is not
automatically graded; the project test suite is the evidence.

**Hints:** Start with a single POST behaviour. Extract fixture helpers after the second copy.
Prefer querying by a returned id over matching a title another fixture may also use.

## In the project

These tests freeze B05's public behaviour before the application acquires users. In the next
lesson, update the affected ones deliberately: anonymous mutations should stop succeeding once
identity is required, and a test that has to change is the contract recording a purposeful
product decision rather than annoying fallout.

That is worth saying plainly, because the temptation in FS06.2 will be to weaken tests until
they pass. The right move is to read each failure and ask whether the new behaviour is the one
you intended. If it is, change the test and know why. If it is not, you have found a bug before
it shipped, which is exactly what you wrote these for.

## Closed-book checkpoint

1. What are the three observations in a request-level create test?
2. Why is a unique test database or rollback strategy necessary?
3. Which failure is stronger evidence: "not 200", or exact 422 plus no inserted row?
4. Why is asserting that a transaction threw insufficient?
5. When is a controller-unit test insufficient?
6. What does a passing test not prove?

## Resources

### Read

- [Pest: writing tests](https://pestphp.com/docs/writing-tests)
- [Pest: datasets](https://pestphp.com/docs/datasets)
- [PostgreSQL: transactions](https://www.postgresql.org/docs/current/tutorial-transactions.html)

### Go deeper

- [Pest: expectations](https://pestphp.com/docs/expectations)
- [Full Stack Open Part 4](https://fullstackopen.com/en/part4)

## You are done when

- [ ] I ran the FS06.1 lab and watched all three of its sabotages fail the right test.
- [ ] My test data is isolated from development data and from other tests.
- [ ] Create, invalid input, update, delete, relation, and pagination have request-level evidence.
- [ ] Validation tests assert 422, stable error evidence, and no database write.
- [ ] One test pins the response key set so a new column cannot leak.
- [ ] The transaction test asserts that neither row was committed, not merely that it threw.
- [ ] Tests assert public status and body and durable database state where appropriate.
- [ ] A deliberately broken route makes the relevant test fail with a message that names it.
- [ ] `php artisan test --filter` and `php artisan test` pass.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_04.md`
- Official sources: Pest writing-tests, datasets and expectations documentation; PostgreSQL transaction documentation
- Versions: PHP 8.4; Pest 3.8 supplied by this repository; PostgreSQL as configured by the learner
- Consulted: 2026-08-14
- DALT files inspected: `tests/Feature/RequestLifecycleTest.php`; `.dalt/course/fullstack/api-behavior-tests-lab/`; `framework/Core/Database.php`
- Curriculum authority: `CURRICULUM.md` §17 FS06.1
- Laravel bridge: Laravel supplies `getJson()` and `assertDatabaseHas()` for these same two observations; writing them explicitly here keeps the response check and the state check visibly separate.
