# DALT.PHP Competency Roadmap

DALT is a framework-learning system, so this roadmap is organized around abilities you can demonstrate rather than a calendar. Follow the recommended path until the core request and data boundaries are clear, then choose the Docker, PostgreSQL, or learning-platform branch that matches the system you want to understand.

The roadmap is based on the current repository contracts. Each node tells you what to read, what to trace, what to predict, what to build, and what evidence closes the node. A green challenge is useful evidence, but it is not the whole checkpoint: you must be able to explain the mechanism and test a new case.

## How to use a node

For every node:

1. Read the listed DALT files and lesson.
2. Trace the concrete path with the listed input.
3. Predict the result before running it.
4. Complete the linked challenge or focused exercise.
5. Build the transfer project without copying the lesson's exact example.
6. Write or interpret a focused test.
7. Pass the checkpoint unaided and record the next unlocked node.

The public DALT docs are the starting point:

- [Installation and Quick Start](installation-and-quick-start.md)
- [Framework Reference](framework-reference.md)
- [Architecture](architecture.md)
- [Errors and Debugging](errors-and-debugging.md)
- [Contributor Content Guide](contributor-content.md)

Laravel bridge links below target Laravel 13.x documentation and the `13.x` framework source branch. They are comparison points, not additional DALT dependencies. The links were checked on 2026-08-12.

## The graph at a glance

Three nodes the original blueprint proposed are explicitly out of scope, not silently missing — R00, R12, and R13. Two more were written but never became independent nodes — R05 and R08 are folded into R01 and R04. Each is called out at its former position below so the absence reads as a decision, not a hole.

```text
R01 Request lifecycle (includes bootstrapping/containers, folded from R05)
  └─> R02 Request/response messages ─> R03 Routing
                                              └─> R04 Middleware (includes validation, folded from R08)
                                                    ├─> R06 Errors/debugging
                                                    ├─> R07 Sessions/state ─> R09 Auth
                                                    └─> R10 Database ─> R11 Migrations

R06 + R10 ─> R14 Contract testing

Docker branch:  DKR01 ─> DKR02 ─> DKR03 ─> DKR04 ─> DKR05
PostgreSQL:     PG01 ─> PG02 ─> PG03 ─> PG04 ─> PG05 ─> PG06 ─> PG07
                         └───────────────────────────────┘       └─> PG07

Platform branch: R01 + R07 + R14 ─> P-R01 ─> P-R02 ─> P-R03 ─> P-R04
                                          └─> P-R05 ─> P-R06

Out of scope (owner's call, 2026-08-13): R00 (repository boundaries and PHP execution),
R12 (controllers, views, escaping), R13 (console entry points and project tooling).
```

The current course metadata makes Docker Compose a prerequisite for the PostgreSQL lessons because it supplies the declared PostgreSQL environment. Conceptually, PostgreSQL begins at the database boundary after R10; a learner may use a local PostgreSQL installation instead, provided the lesson's database assumptions are satisfied.

## Recommended foundation path

### R00 — Repository boundaries and PHP execution — out of scope

**Cut (owner's call, 2026-08-13).** PHP-language fundamentals — Composer autoloading, namespaces, `require`, superglobals, process boundaries — are not what this course teaches. The owner is relearning backend *concepts* after a period of heavy AI use, not PHP syntax, and will pick the language mechanics up in passing while working through R01 onward. The `composer.json`, `public/index.php`, and `public/router.php` files this node would have covered are already read as part of R01's trace, so nothing about the request lifecycle is actually uncovered — only a standalone "PHP execution model" competency is deliberately not built as its own checkpoint.

**Next:** R01. Optional branch: DKR01.

### R01 — One HTTP request from server to output

**Competency:** Narrate the complete path from a request URI to the final status, headers, and body, including the exception boundary.

**Why it matters:** A route, middleware, controller, and response are stages in one request, not independent magic.

**Prerequisites:** None.

**Read:** `public/router.php`, `public/index.php`, `framework/Core/bootstrap.php`, `framework/Core/Request.php`, `framework/Core/Router.php`, `framework/Core/Response.php`, `framework/Core/Container.php`; then [Lesson 01: Request Lifecycle](../.dalt/course/lessons/01-request-lifecycle/README.md), which now also covers R05's bootstrapping and container competency in its §3.

**Trace:** Request `/` with `GET`, then request a missing path. Predict which stage produces the 404 and whether middleware runs. Repeat with a controller that throws while `APP_DEBUG` is true and false.

**Practice:** `broken-session` after observing its request-to-response failure.

**Build:** Tiny route debugger: add a dynamic route, a closure route, a deliberate 404, and a contract test for each result.

**Test and checkpoint:** Interpret the feature tests for the front controller and explain why `Response::send()` happens once at the outer boundary. Draw the sequence without opening `public/index.php`.

**Laravel bridge:** [Request Lifecycle](https://laravel.com/docs/13.x/lifecycle), [Requests](https://laravel.com/docs/13.x/requests), and the [HTTP foundation source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Foundation/Http).

**DALT boundary:** DALT uses one visible front controller and a small exception handler; it does not provide Laravel's kernel middleware groups, events, or response negotiators.

**Next:** R02.

### R02 — Request and HTTP response messages

**Competency:** Distinguish method, path, route parameters, query data, form input, status, headers, and body, then choose the correct response representation.

**Why it matters:** Bugs often come from confusing where data came from or changing the HTTP status outside the response object that will actually be sent.

**Prerequisites:** R01.

**Read:** `framework/Core/Request.php`, `framework/Core/Response.php`, `framework/Core/functions.php`, and the Request/Response sections of [Framework Reference](framework-reference.md).

**Trace:** Use `PATCH /posts/9?page=2` with form data and `_method`. Predict `method()`, `path()`, `route('id')`, `query('page')`, `input()`, and `all()`. Return a string, array, `Response`, `null`, and an integer from separate handlers.

**Practice:** Focused exercise: convert a controller that prints JSON and calls `http_response_code()` into one that returns a `Response`.

**Build:** Add a small endpoint with HTML success, JSON validation failure, and a redirect; test status and content type for each branch.

**Test and checkpoint:** Write a test proving that a route value remains a string and that printed output overrides a non-`Response` return. Explain why `exit` breaks the outward path.

**Laravel bridge:** [Requests](https://laravel.com/docs/13.x/requests), [Responses](https://laravel.com/docs/13.x/responses), and the [HTTP response source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Http).

**DALT boundary:** DALT has no streamed responses, response negotiation layer, resource objects, or immutable request value objects beyond the small snapshot used for one request.

**Next:** R03.

### R03 — Routes as data and dispatch

**Competency:** Register and diagnose routes, predict ordered matching, extract parameters, and choose between a closure and a controller file.

**Why it matters:** Routing is the framework's first application-level decision. A correct handler is irrelevant if a different route wins first.

**Prerequisites:** R02.

**Read:** `framework/Core/Route.php`, `framework/Core/Router.php`, `routes/routes.php`; then [Lesson 02: Routing](../.dalt/course/lessons/02-routing/README.md).

**Trace:** Put `/posts/{id}` before `/posts/create` and predict the winner. Try a method mismatch and a placeholder containing a slash. Confirm that static route text is escaped and route values are captured as strings.

**Practice:** `broken-routing`.

**Build:** Tiny route debugger project from R01, expanded with route-order and 404 tests.

**Test and checkpoint:** Interpret `RouterTest` and write one route-order test. From a 404 alone, identify the method/path/ordering checks before changing the controller.

**Laravel bridge:** [Routing](https://laravel.com/docs/13.x/routing) and the [routing source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Routing).

**DALT boundary:** Routes are registered in one PHP file and matched by a small ordered list. There are no route names, model binding, route groups, resource controllers, or typed URI constraints.

**Next:** R04.

### R04 — Middleware as a pipeline

**Competency:** Explain before/after order, short-circuiting, aliases, and how middleware receives and returns a `Response`.

**Why it matters:** Authentication, CSRF, logging, and other cross-cutting concerns must surround a handler without duplicating themselves in every controller.

**Prerequisites:** R03.

**Read:** `framework/Core/Middleware/Middleware.php`, `MiddlewareInterface.php`, `Auth.php`, `Guest.php`, `Csrf.php`, and [Lesson 03: Middleware](../.dalt/course/lessons/03-middleware/README.md).

**Trace:** Attach `['auth', 'csrf']` to a POST route. Predict the order on the way in and out, then remove the CSRF token and observe 419. Confirm that a missing route never reaches middleware.

**Practice:** `broken-middleware`.

**Build:** Middleware trace viewer that records before/after order and has one layer that deliberately short-circuits.

**Test and checkpoint:** Write a test for order and one for short-circuiting. Explain why the first declared middleware sees the request first but the response last.

**Laravel bridge:** [Middleware](https://laravel.com/docs/13.x/middleware) and the [middleware source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Http/Middleware).

**DALT boundary:** DALT has route-local aliases only; it does not have global middleware groups, terminable middleware, middleware parameters, or the full Laravel HTTP stack.

**Next:** R06 and R07. (R05's container competency is already covered — see the note below.)

### R05 — Bootstrapping and dependency containers — folded into R01

**Folded, not cut.** Unlike R00/R12/R13, this competency is genuinely taught — it now lives inside [Lesson 01: Request Lifecycle](../.dalt/course/lessons/01-request-lifecycle/README.md) §3, which covers `bind()`/`singleton()`/`instance()`, why registration is not construction, and why `Database` is registered lazily. A separate R05 checkpoint would have asked the learner to re-read files (`Container.php`, `bootstrap.php`) already read for R01, for a competency (bind/resolve/singleton) that is inseparable from R01's own "the database stays lazy" trace. Wherever another node below lists a prerequisite of "R05," read it as "R01" — R01 now includes this competency.

**Next:** R06, R07, R10, and the platform branch.

### R06 — Errors, exceptions, logging, and debugging

**Competency:** Use an exception's class and status to locate the failing boundary, reproduce it safely, and choose what belongs in a log versus a response.

**Why it matters:** A framework is partly an error translation system. Debug output, production output, HTTP status, and logs have different audiences.

**Prerequisites:** R01.

**Read:** `framework/Core/ExceptionHandler.php`, `framework/Core/HttpException.php`, `framework/Core/ValidationException.php`, `framework/Core/functions.php`, [Errors and Debugging](errors-and-debugging.md), and [Lesson 18: Errors, Exceptions, and Debugging](../.dalt/course/lessons/18-debugging-and-logging/README.md).

**Trace:** Compare `abort(404)`, a missing controller, an unsupported handler return, and an unexpected 500 with `APP_DEBUG=true` and false. Inspect which cases reach `storage/logs/app.log`.

**Practice:** `broken-error-handling`, plus a focused debugging exercise using `dd()`, `app_log()`, and a minimal CLI reproduction, then remove diagnostic output.

**Build:** Add a safe error page and a test that proves production output does not disclose a trace.

**Test and checkpoint:** Map five observed failures to their source boundary without opening the implementation first. Explain why 4xx errors are not reported like 5xx errors.

**Laravel bridge:** [Error Handling](https://laravel.com/docs/13.x/errors), [Logging](https://laravel.com/docs/13.x/logging), and the [exception source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Foundation/Exceptions).

**DALT boundary:** DALT logs to a local file and renders a small HTML response. It does not ship external reporters, structured logging channels, renderable exception mappings, or custom error-page discovery.

**Next:** R07 and, with R10, R14.

### R07 — State across requests

**Competency:** Explain the difference between persistent session data, flash data, old input, cookies, redirects, and request-local values.

**Why it matters:** Login state, validation errors, and multi-step forms cross request boundaries; confusing their lifetimes creates security and UX defects.

**Prerequisites:** R01 and R06.

**Read:** `framework/Core/Session.php`, session startup in `public/index.php`, `framework/Core/Authenticator.php`, `framework/Core/functions.php`, and [Lesson 01: Request Lifecycle](../.dalt/course/lessons/01-request-lifecycle/README.md).

**Trace:** Flash a value and observe it in the current request, next request, and third request. Follow a validation redirect through `errors`, `old`, and `previousUrl()`.

**Practice:** `broken-session`.

**Build:** Form workflow with validation, redirect, flash errors, old input, CSRF, and a success state.

**Test and checkpoint:** Write a three-request session test and explain why session regeneration is a security boundary. Identify which values must never be flashed or logged.

**Laravel bridge:** [Session](https://laravel.com/docs/13.x/session), [HTTP Session source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Session), and [Validation](https://laravel.com/docs/13.x/validation).

**DALT boundary:** DALT uses native PHP sessions and file storage. It does not provide Laravel's configurable cache-backed session drivers, cookie encryption middleware, or session middleware groups.

**Next:** R09. (R08's validation competency is already covered — see the note below.)

### R08 — Validation and trusted input — folded into R04

**Folded, not cut.** This competency is taught inside [Lesson 03: Middleware](../.dalt/course/lessons/03-middleware/README.md) §8, alongside — and deliberately contrasted with — authorization, since middleware is where DALT's other authorization mechanism (the `auth` alias) already lives. Splitting validation into its own node would have separated "is this data shaped right" from "is this actor allowed," the exact distinction §8 exists to teach side by side. Read a prerequisite of "R08" elsewhere in this roadmap as "R04."

**Next:** R09.

### R09 — Authentication and authorization

**Competency:** Implement and diagnose password verification, canonical session identity, login/logout, intended redirects, route guards, and authorization failures.

**Why it matters:** Authentication answers who the requester is; authorization answers what that identity may do. Both are stateful security boundaries.

**Prerequisites:** R04 and R07.

**Read:** `framework/Core/Authenticator.php`, `framework/Core/Middleware/Auth.php`, `Guest.php`, `framework/Core/functions.php`, and [Lesson 04: Authentication](../.dalt/course/lessons/04-authentication/README.md).

**Trace:** Attempt valid and invalid credentials, inspect session identity after login, visit a protected route as a guest, and follow the safe intended redirect. Predict what happens with a malformed identity.

**Practice:** `broken-auth`.

**Build:** Session authentication project with registration, login, logout, protected page, and fixation tests.

**Test and checkpoint:** Write tests for password hashing, guest redirect, logout, malformed identity, and unsafe intended targets. Explain why a password is never compared directly with a stored hash.

**Laravel bridge:** [Authentication](https://laravel.com/docs/13.x/authentication), [Authorization](https://laravel.com/docs/13.x/authorization), and the [authentication source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Auth).

**DALT boundary:** DALT includes one small session authenticator and middleware example. It does not include guards, providers, policies, gates, password brokers, social authentication, or starter-kit UI.

**Next:** R10 and the form/CRUD projects.

### R10 — Database boundary and safe querying

**Competency:** Configure a supported driver, resolve the lazy database service, execute prepared queries, choose `get`/`find`/`findOrFail`, and handle transaction failures.

**Why it matters:** The database is a trust and failure boundary. Prepared statements protect values, while result shape and transaction handling determine application behavior.

**Prerequisites:** R02, R01, and R09 for authenticated queries.

**Read:** `framework/Core/Database.php`, `framework/Core/DatabaseManager.php`, `config/database.php`, `database/migrations/001_create_users_table.sql`, and [Lesson 05: Database](../.dalt/course/lessons/05-database/README.md).

**Trace:** Run a bound lookup with a value such as `1 OR 1=1`, fetch before querying, query zero rows, and resolve `Database` in a request that never fetches. Predict the exact result shape and exception boundary.

**Practice:** `broken-database`.

**Build:** CRUD application with prepared SQL, validation, authorization, pagination, and explicit error handling.

**Test and checkpoint:** Write tests for injection resistance, integer result types, no-row behavior, unsupported drivers, and rollback. Explain why table names cannot be bound.

**Laravel bridge:** [Database: Getting Started](https://laravel.com/docs/13.x/database), [Queries](https://laravel.com/docs/13.x/queries), and the [database source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Database).

**DALT boundary:** DALT exposes PDO through a small query wrapper. It has no ORM, query builder, model events, connection pool, queue-aware scopes, or automatic migration on first request.

**Next:** R11, PG01, and the CRUD project.

### R11 — Schema evolution and migrations

**Competency:** Create, order, run, inspect, fail, and recover SQL migrations while understanding the driver-specific boundary.

**Why it matters:** Application code and database schema evolve together. A migration is a recorded state transition, not merely a SQL file.

**Prerequisites:** R10.

**Read:** `framework/Core/Migration.php`, `database/migrations/`, `artisan`, and [Lesson 15: PostgreSQL Reliability](../.dalt/course/lessons/15-postgres-reliability/README.md).

**Trace:** Run `php artisan migrate` twice, inspect the migration table and batch, create a failing migration in an isolated database, and observe what is rolled back and what is recorded.

**Practice:** `db-migrations-disorder`.

**Build:** Migration failure lab: create a schema, fail a later change, inspect the state, recover, and explain the result.

**Test and checkpoint:** Interpret migration ordering and failure tests. Explain why DALT converts only a small set of SQLite/PostgreSQL syntax and why migrations are explicit.

**Laravel bridge:** [Migrations](https://laravel.com/docs/13.x/migrations), [Database Testing](https://laravel.com/docs/13.x/database-testing), and the [migration source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Database/Migrations).

**DALT boundary:** Migrations are SQL files with a small runner. There are no schema-builder objects, generated down methods, migration events, or broad cross-driver translation.

**Next:** PG05 and the migration project. (R12 is out of scope — see the note below.)

### R12 — Controllers, views, escaping, and assets — out of scope

**Cut (owner's call, 2026-08-13).** Presentation — view rendering, escaping, asset pipelines — is real DALT surface (`framework/Core/View.php`, `resources/views/`) but is not where the backend-fundamentals gap this course exists to close actually is. The owner's stated goal is relearning request/data/state boundaries, not templating or frontend asset tooling. Nothing about this cut blocks R13 or R14; neither ever depended on view rendering specifically.

### R13 — Console entry points and project tooling — out of scope

**Cut (owner's call, 2026-08-13).** The HTTP-versus-CLI boot-path distinction is genuinely a different entry point worth knowing, but it is a narrower, lower-leverage competency than R06 and R14, which this course builds instead. `artisan`'s commands are still read in passing wherever a lesson already asks the learner to run one (`php artisan migrate`, `php artisan challenge:verify`); there is simply no dedicated checkpoint for the CLI boot path itself.

### R14 — Testing a framework contract

**Competency:** Choose a unit, contract, feature, or integration test that proves a public behavior, isolates state, and rejects plausible false positives.

**Why it matters:** Documentation and challenge verification are only trustworthy when they execute or inspect the behavior they claim to protect.

**Prerequisites:** R06, R10, and one completed application project.

**Read:** `tests/`, `phpunit.xml`, `.dalt/Core/ChallengeVerifier.php`, `.dalt/course/challenges/*/tests.php`, [Contributor Content Guide](contributor-content.md), the verifier implementation under `.dalt/Core/`, and [Lesson 19: Testing a Framework Contract](../.dalt/course/lessons/19-testing-framework-contracts/README.md).

**Trace:** Compare a source check with `class_contract` and `handler_result`. Reproduce the dead-code shape that a source match accepts, then run the controller check against seeded data.

**Practice:** `untested-contract`, plus extending one source-matched challenge with an executable check and proving that the broken fixture fails before the corrected fixture passes.

**Build:** Mini framework extension: add one behavior, its focused tests, its documentation example, and a failure test for a plausible misuse.

**Test and checkpoint:** Explain why a green source check is not proof that a controller ran. Write a test that would catch a dead-code fix and describe the isolation boundary.

**Laravel bridge:** [Testing](https://laravel.com/docs/13.x/testing), [HTTP Tests](https://laravel.com/docs/13.x/http-tests), and the [testing source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Testing).

**DALT boundary:** DALT uses Pest/PHPUnit and a small verifier. It does not provide Laravel's HTTP test client, model factories, database refresh traits, browser tests, or parallel test orchestration.

**Next:** platform branch, DKR branch, PG branch, and contribution project.

## Optional infrastructure branches

These branches deepen backend deployment and database reasoning. They are visually separate from the framework foundation. Docker is the declared environment path for the current PostgreSQL lesson sequence, but it is not a conceptual prerequisite for understanding R10.

### Docker branch

#### DKR01 — Containers, images, and process boundaries

**Competency:** Explain an image, container, port, volume, and process boundary and run the basic Docker lifecycle commands.

**Prerequisites:** None. **Read:** [Lesson 06: Docker Basics](../.dalt/course/lessons/06-docker-basics/README.md). **Trace/predict:** inspect the image, container, exposed port, and mounted code in a minimal run. **Practice/build:** run the DALT app in a disposable container and document what survives removal. **Test/checkpoint:** predict which state is lost with the container and explain why a container is not a VM. **Laravel bridge:** [Laravel Sail](https://laravel.com/docs/13.x/sail). **DALT boundary:** DALT teaches Docker concepts; it does not provide a container orchestrator or deployment platform. **Next:** DKR02.

#### DKR02 — Dockerfiles and PHP runtime images

**Competency:** Build a reproducible PHP image with system dependencies, Composer, application files, and a correct PHP-FPM/Nginx boundary.

**Prerequisites:** DKR01. **Read:** [Lesson 07: Writing Dockerfiles](../.dalt/course/lessons/07-dockerfile/README.md). **Trace/predict:** follow each layer and predict which cache invalidates after a source change. **Practice:** `docker-incomplete-dockerfile`, `docker-broken-nginx`. **Build:** a Dockerfile for a small DALT controller that installs every required system dependency explicitly. **Test/checkpoint:** build it, explain the missing-library and missing-Composer failures, and identify the public root. **Laravel bridge:** [Deployment](https://laravel.com/docs/13.x/deployment). **DALT boundary:** the lesson's image is educational and is not a hardened production image. **Next:** DKR03.

#### DKR03 — Compose service topology

**Competency:** Connect PHP, PostgreSQL, and Nginx services with mounts, networks, environment, and readiness assumptions.

**Prerequisites:** DKR02. **Read:** [Lesson 08: Docker Compose](../.dalt/course/lessons/08-docker-compose/README.md). **Trace/predict:** identify which service owns code, static files, database state, and PHP execution. **Practice:** `docker-compose-missing-services`. **Build:** a three-service stack with a documented health/readiness check. **Test/checkpoint:** explain why start order is not readiness and why Nginx needs the public files. **Laravel bridge:** [Configuration](https://laravel.com/docs/13.x/configuration) and [Sail](https://laravel.com/docs/13.x/sail). **DALT boundary:** Compose is an optional local environment, not an application dependency. **Next:** DKR04 and PG01.

#### DKR04 — Intermediate image and volume patterns

**Competency:** Use multi-stage builds, health checks, `.dockerignore`, and backup-aware volumes to reduce risk without hiding the runtime boundary.

**Prerequisites:** DKR03. **Read:** [Lesson 12: Docker Intermediate](../.dalt/course/lessons/12-docker-intermediate/README.md). **Trace/predict:** identify what belongs in a build stage versus the runtime stage and what a health check actually proves. **Practice:** `docker-missing-multistage`. **Build:** produce a smaller image and a restore procedure for a disposable database volume. **Test/checkpoint:** compare image contents and prove the health condition affects startup behavior. **Laravel bridge:** [Deployment](https://laravel.com/docs/13.x/deployment). **DALT boundary:** no production orchestrator or zero-downtime deployment contract is provided. **Next:** DKR05.

#### DKR05 — Production container patterns

**Competency:** Keep secrets out of source, configure health/readiness and restart behavior, and state what a container security measure does not protect.

**Prerequisites:** DKR04. **Read:** [Lesson 14: Docker Production Patterns](../.dalt/course/lessons/14-docker-production/README.md). **Trace/predict:** distinguish a mounted secret, an image-specific `_FILE` convention, and DALT's `env()` behavior. **Practice:** `docker-missing-healthcheck`, `docker-plaintext-secrets`. **Build:** a deployment note with secret flow, least-privilege role, health checks, and a rollback path. **Test/checkpoint:** demonstrate that a mounted secret is not automatically read by DALT and explain the remaining process-environment exposure. **Laravel bridge:** [Configuration](https://laravel.com/docs/13.x/configuration) and [Deployment](https://laravel.com/docs/13.x/deployment). **DALT boundary:** DALT does not implement Docker secret loading or production orchestration. **Next:** optional PG branch and observability.

### PostgreSQL branch

#### PG01 — PostgreSQL connection and first queries

**Competency:** Connect to PostgreSQL, inspect schema, write safe raw SQL, and distinguish database-server behavior from DALT's wrapper.

**Prerequisites:** R10 and either DKR03 or an equivalent PostgreSQL environment. **Read:** [Lesson 09: PostgreSQL First Contact](../.dalt/course/lessons/09-postgres-first-contact/README.md). **Trace/predict:** connect, inspect tables, bind a value, and compare PostgreSQL result types with SQLite. **Practice:** `db-first-queries`. **Build:** a small query-backed endpoint with an injection test. **Test/checkpoint:** explain which failure belongs to PostgreSQL and which belongs to `Core\Database`. **Laravel bridge:** [Database](https://laravel.com/docs/13.x/database) and the [PostgreSQL connector source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Database/Connectors). **DALT boundary:** only SQLite and PostgreSQL are supported and DALT does not abstract SQL dialects completely. **Next:** PG02.

#### PG02 — Joins, constraints, and transactions

**Competency:** Choose a join, connect foreign keys correctly, and make a failed multi-write operation safe and explainable.

**Prerequisites:** PG01. **Read:** [Lesson 10: PostgreSQL Core](../.dalt/course/lessons/10-postgres-intermediate/README.md). **Trace/predict:** vary missing authors, wrong join columns, and a failure between two writes. **Practice:** `db-broken-join`, `db-broken-transaction`. **Build:** a transfer endpoint with a transaction and explicit failure response. **Test/checkpoint:** seed rows that distinguish INNER from LEFT JOIN and prove balances remain consistent after failure. **Laravel bridge:** [Query Builder](https://laravel.com/docs/13.x/queries), [Database Transactions](https://laravel.com/docs/13.x/database#database-transactions), and the [query source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Database/Query). **DALT boundary:** SQL remains visible; there is no Eloquent relationship or automatic transaction wrapper. **Next:** PG03, PG04, and PG05.

#### PG03 — DALT database patterns and pagination

**Competency:** Shape a database-backed endpoint with limit/offset, stable ordering, typed results, and a response contract.

**Prerequisites:** R10 and PG02. **Read:** [Lesson 11: DALT Database Layer](../.dalt/course/lessons/11-dalt-db-layer/README.md). **Trace/predict:** compare pages at boundaries and inspect the generated SQL parameters. **Practice:** `db-missing-pagination`. **Build:** paginated CRUD list with a stable tie-breaker and an empty-page test. **Test/checkpoint:** prove that page size is bounded and that the response still uses the framework boundary. **Laravel bridge:** [Pagination](https://laravel.com/docs/13.x/pagination) and the [pagination source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Pagination). **DALT boundary:** pagination is a query/controller pattern, not a built-in paginator object. **Next:** PG04 and PG07.

#### PG04 — Advanced PostgreSQL queries

**Competency:** Choose a PostgreSQL-specific feature—CTE/window query, JSONB, or full-text search—and expose it through a safe DALT controller.

**Prerequisites:** PG02. **Read:** [Lesson 13: PostgreSQL Advanced](../.dalt/course/lessons/13-postgres-advanced/README.md). **Trace/predict:** inspect the required schema, run the query directly, then run the controller with bound values. **Practice:** `db-broken-fts`, `db-missing-jsonb`. **Build:** a search or metadata endpoint with a migration/prerequisite note and a response test. **Test/checkpoint:** explain which part is PostgreSQL-specific and what a SQLite learner must provision separately. **Laravel bridge:** [Database](https://laravel.com/docs/13.x/database) and the [query source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Database/Query). **DALT boundary:** DALT does not emulate PostgreSQL features on SQLite or provide ORM-specific abstractions for them. **Next:** PG06.

#### PG05 — Reliable schema operations

**Competency:** Back up, restore, review, and recover database changes while treating migrations as code and failure as a normal state.

**Prerequisites:** R11 and PG02. **Read:** [Lesson 15: PostgreSQL Reliability](../.dalt/course/lessons/15-postgres-reliability/README.md). **Trace/predict:** simulate an ordered migration failure and inspect which schema state remains. **Practice:** `db-migrations-disorder`. **Build:** migration failure lab from R11 with a written recovery runbook. **Test/checkpoint:** explain why a backup is not a migration and why a successful command is not proof of recoverability. **Laravel bridge:** [Migrations](https://laravel.com/docs/13.x/migrations), [Database Testing](https://laravel.com/docs/13.x/database-testing), and the [migration source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Database/Migrations). **DALT boundary:** DALT's migration runner is intentionally small and does not provide a backup service or production rollout orchestration. **Next:** PG06 and PG07.

#### PG06 — Database-enforced isolation

**Competency:** Design and verify row-level security with the correct database role, session setting, policy, and schema prerequisites.

**Prerequisites:** PG04 and PG05. **Read:** [Lesson 16: Advanced PostgreSQL](../.dalt/course/lessons/16-postgres-advanced-patterns/README.md). **Trace/predict:** compare a superuser and a non-superuser, set the tenant context with `set_config`, and query as two tenants. **Practice:** `db-missing-rls`. **Build:** a two-tenant endpoint with database-enforced isolation and a cross-tenant regression test. **Test/checkpoint:** prove isolation with two roles and explain why a correct policy can still be ineffective for a superuser. **Laravel bridge:** [Authorization](https://laravel.com/docs/13.x/authorization) and the [database connection source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Database/Connection). **DALT boundary:** RLS is PostgreSQL behavior; DALT does not create roles, provision tenant columns, or guarantee least privilege.

#### PG07 — Observability and query performance

**Competency:** Find a slow query from evidence, choose an index based on the query shape, and verify the change rather than guessing.

**Prerequisites:** PG05 and either PG03 or PG04. **Read:** [Lesson 17: Observability](../.dalt/course/lessons/17-observability/README.md). **Trace/predict:** inspect a query plan or statistics entry, identify the filtering/order column, and compare before/after behavior. **Practice:** `db-slow-queries`. **Build:** an observability note with a reproducible slow query, index rationale, and rollback. **Test/checkpoint:** explain what the index improves and what it does not; include a write-cost or selectivity consideration. **Laravel bridge:** [Database](https://laravel.com/docs/13.x/database) and the [query source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Database/Query). **DALT boundary:** DALT does not ship a metrics backend, query profiler, or automatic index advisor.

## Learning-platform branch

This branch is for learners who want to understand how a course system discovers content, changes files safely, verifies repairs, and teaches through HTTP. Unlock it after R01, R07, and R14. (The original blueprint also listed R05 and R12 — R05 is now part of R01, and R12's view-rendering competency is out of scope; neither blocks this branch in practice, since P-R05 reads the learning platform's own views directly rather than assuming general view-layer competency.)

### P-R01 — Optional platform bootstrapping

**Competency:** Explain how `.dalt` is discovered, validated, booted, and removed without making the application depend on it.

**Read:** `framework/Core/Platform.php`, `.dalt/bootstrap.php`, `.dalt/routes/routes.php`, and `.dalt/Core/`. **Trace:** run the app with `.dalt` present and absent; predict route/view/controller precedence. **Practice/build:** create a minimal optional integration with required paths and a no-op absence case. **Test/checkpoint:** prove the core application works without the platform. **Laravel bridge:** [Package Development](https://laravel.com/docs/13.x/packages), [Service Providers](https://laravel.com/docs/13.x/providers), and the [package source](https://github.com/laravel/framework/tree/13.x/src/Illuminate/Support). **DALT boundary:** `.dalt` is a repository-local optional package, not Composer package discovery.

### P-R02 — Metadata-driven course discovery

**Competency:** Add a lesson or challenge whose metadata is valid, ordered, connected, and rejected clearly when malformed.

**Read:** `.dalt/Core/CourseLoader.php`, `.dalt/course/lessons/`, `.dalt/course/challenges/`, and [Contributor Content Guide](contributor-content.md). **Trace:** change an isolated metadata fixture to duplicate an order, unknown prerequisite, or invalid icon and predict the loader error. **Practice:** create a temporary lesson metadata fixture and validate it. **Build:** one lesson/challenge pair with a dependency edge and a navigation expectation. **Test/checkpoint:** explain why explicit order and prerequisites are separate concepts. **Laravel bridge:** [Package Development](https://laravel.com/docs/13.x/packages). **DALT boundary:** the loader is filesystem/JSON-based and has no database content registry.

### P-R03 — Safe challenge transactions and recovery

**Competency:** Start, reset, stop, and recover a challenge without overwriting unrelated learner work or following unsafe paths.

**Read:** `.dalt/Core/ChallengeManager.php`, `.dalt/baseline/`, and `.dalt/scripts/`. **Trace:** start a challenge, inspect the recovery manifest, modify an unrelated file, reset, stop, and verify only the challenge paths change. **Practice:** use any small framework challenge, then stop it. **Build:** a fixture transaction test covering interruption and preservation. **Test/checkpoint:** explain the allowlist, backup, lock, and stop behavior without relying on the UI. **Laravel bridge:** [Testing](https://laravel.com/docs/13.x/testing) and [Package Development](https://laravel.com/docs/13.x/packages). **DALT boundary:** this is a local educational file transaction, not a version-control replacement or sandbox for arbitrary code.

### P-R04 — A verifier that resists false positives

**Competency:** Choose static versus executable verification, validate a test specification, isolate learner code, and reject a plausible dead-code repair.

**Read:** `.dalt/Core/ChallengeVerifier.php`, `.dalt/Core/probe-class-contract.php`, and challenge `tests.php` files. **Trace:** compare `file_contains` with `class_contract` and `handler_result` against the corresponding broken fixture. **Practice:** extend one source-matched challenge with an executable check. **Build:** an authoring test that fails the broken fixture, passes the canonical fix, and rejects a source-only false positive. **Test/checkpoint:** explain what the isolated process protects and why `handler_result` is restricted to controllers. **Laravel bridge:** [Testing](https://laravel.com/docs/13.x/testing) and [HTTP Tests](https://laravel.com/docs/13.x/http-tests). **DALT boundary:** the verifier does not execute arbitrary learner PHP, SQL, containers, or database behavior outside its explicit probes.

### P-R05 — Framework concepts in educational HTTP/UI flow

**Competency:** Connect lesson discovery, challenge state, API responses, CSRF, navigation, and graceful JavaScript failure into a usable learning flow.

**Read:** `.dalt/Http/controllers/`, `.dalt/resources/views/`, `.dalt/resources/js/`, and the learning-flow tests under `tests/Feature/`. **Trace:** load a lesson, start a challenge, verify with and without CSRF, and observe the UI when JavaScript is unavailable. **Practice:** inspect the existing learning flow tests. **Build:** a small lesson status view with server-rendered fallback and an accessible error state. **Test/checkpoint:** prove that progress is recorded only after a real pass. **Laravel bridge:** [HTTP Tests](https://laravel.com/docs/13.x/http-tests), [CSRF Protection](https://laravel.com/docs/13.x/csrf), and [Views](https://laravel.com/docs/13.x/views). **DALT boundary:** the UI is an optional Vue layer over server routes, not a general LMS or SPA framework.

### P-R06 — Authoring and validating content

**Competency:** Write a lesson/challenge pair whose prerequisites, behavior claims, hints, tests, and transfer exercise survive review.

**Read:** [Contributor Content Guide](contributor-content.md), `.dalt/stubs/`, `.dalt/scripts/`, and the existing lesson/challenge catalog. **Trace:** author metadata, run the catalog loader, run the broken fixture, apply the fix, verify, and stop. **Practice:** convert one static check to an executable check where behavior is the competency. **Build:** the learning-content contribution project below. **Test/checkpoint:** pass the authoring rubric and explain every claim from an executed output. **Laravel bridge:** [Contribution Guide](https://laravel.com/docs/13.x/contributions) and [Package Development](https://laravel.com/docs/13.x/packages). **DALT boundary:** content is validated by repository tooling; there is no remote course marketplace or automatic pedagogical review.

## Project ladder

Projects unlock when their prerequisite nodes are complete. A project can use any domain theme, but its acceptance tests must prove the listed behavior.

1. **Request inspector** — R01–R02. Display selected request data safely; test query/body precedence and route strings.
2. **Tiny route debugger** — R03. Add dynamic and closure routes, deliberate 404s, and route-contract tests.
3. **Middleware trace viewer** — R04. Record before/after order and demonstrate short-circuiting.
4. **Form workflow** — R04 (validation and CSRF), R06, R07. Validate, redirect, flash errors, preserve old input, enforce CSRF, and show success.
5. **Session authentication** — R07–R09. Register, login, logout, protect a page, rotate identity, and test unsafe redirect targets.
6. **CRUD application** — R10–R11, with R04 for validation. Use prepared SQL, validation, authorization, pagination, and explicit failures.
7. **Migration failure lab** — R11 and PG05. Create, fail, inspect, recover, and explain schema state.
8. **Mini framework extension** — R01, R06, R14. Add one scoped mechanism, test it, document it, and test misuse.
9. **Learning-content contribution** — R14 and P-R01–P-R06. Ship a lesson, challenge, verifier check, and catalog/UI integration that passes review.

## Completion rubric

A node is complete when the learner can do most of these without following a recipe:

- draw or narrate the execution path;
- predict behavior for a new input or failure;
- locate the responsible source file from a symptom;
- write or interpret a focused contract test;
- fix or extend the behavior without breaking an adjacent path;
- explain the Laravel 13.x mechanism at a larger scale;
- name one behavior DALT intentionally omits;
- identify a relevant security or state-management risk.

Challenge progress is a separate signal from content completion. The learning UI can record passed challenges, but the roadmap checkpoint requires explanation, transfer, and test evidence as well.

## How maintainers update this roadmap

When framework behavior changes:

1. update or add the relevant contract test;
2. rerun the affected lesson/challenge and public documentation example;
3. update the node's source paths, trace, boundary, and checkpoint;
4. update `docs/AUDIT_COVERAGE.md`, `docs/FINDINGS_LEDGER.md`, and `docs/WORKLOG.md`;
5. run `composer test` and leave no active challenge before committing.

Do not add time estimates. If a node is not supported by current code or content, label the gap and record the next implementation unit instead of hiding it in the roadmap.
