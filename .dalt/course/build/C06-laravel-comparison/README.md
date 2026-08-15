> **What exists when you finish:** a version-pinned comparison map from the working DALT issue tracker to Laravel’s public APIs and relevant internal implementation points, showing what Laravel makes convenient without pretending the project was rewritten.

## What you are building

Create a Laravel comparison audit alongside the learner project. Map the backend mechanisms you actually use: routing, request lifecycle, middleware, handlers/controllers, validation, database access, migrations, transactions, sessions/authentication, authorization, errors/exceptions, and testing. For every important row include the DALT concept and project location, Laravel public API, Laravel internal class/file at the exact pinned Laravel version, and what Laravel adds, hides, or configures for you.

This is a reading and reasoning exercise, not a migration plan. Do not introduce Laravel, Composer packages, Eloquent, a new scaffold, or a Laravel rewrite into the issue tracker. The point is to recognize abstractions because you understand their lower-level counterpart, not to replace working software at the end of a course.

## Why this milestone exists

Framework fluency can become memorized method names. DALT makes routing, middleware, sessions, exceptions, and database boundaries visible; Laravel packages many of those choices behind stable public interfaces and a larger convention system. Comparing the real project lets you see both: Laravel is not magic, but neither is it merely a file rename. It adds lifecycle orchestration, configuration, integration, defaults, conventions, and ecosystem tooling.

The version pin matters because internals change. A claim such as “Laravel uses this class” is only auditable when it names a release and a source location. Public APIs are the contract application authors should rely on; internals are evidence for understanding, not extension points to copy into an app.

## Before you start

Finish C01–C05. Select the Laravel version already pinned by the project/control documents or record an explicit version in the audit before reading source. Use official Laravel documentation and the corresponding tagged source tree; do not rely on memory, search snippets, or an unversioned blog. Keep the issue tracker running as the primary reference.

```sh
php artisan test
docker compose up -d
git -C /path/to/laravel checkout <pinned-tag>
```

The source checkout is an inspection aid, not a dependency to add to this repository. If you cannot access a local source checkout, cite the official tagged source URL in your own notes and state that the class/file was verified there. Never copy vendor code into the learner project to satisfy the map.

## Stage 1 — Start from the DALT request trace

Use C01’s real request trace and select the C02 workflow. For each step identify DALT’s project/framework mechanism first: route declaration, router dispatch, middleware, request parsing, handler, validation, database/transaction call, session/auth lookup, authorization decision, exception/response mapping, and test client. Include actual paths/functions from your project so the comparison is anchored in behavior.

**Working looks like:** the audit begins with a request you can run and does not turn into a generic Laravel glossary.

**Check it yourself:** execute the workflow and annotate where each DALT step occurs. If a category is not used in your app, mark it absent and explain the boundary rather than inventing an implementation.

## Stage 2 — Map public Laravel APIs

For every required category name the Laravel public surface a normal application author would use: routes and route groups; middleware registration/assignment; controller actions; request validation; query builder/Eloquent where relevant; migrations; `DB::transaction`; session/auth facilities; gates/policies; exception handling; and HTTP/database test helpers. Include a tiny usage sketch only when it clarifies the comparison, and label it illustrative rather than code to paste into this project.

```php
Route::middleware('auth')->post('/issues', [IssueController::class, 'store']);

DB::transaction(function () use ($request) {
    // application-specific durable work belongs here
});
```

**Working looks like:** each API answers the same concern as a real DALT step while making clear that the naming and lifecycle are not identical.

**Check it yourself:** confirm each API in official documentation for the pinned version. Do not call an internal class the public API merely because source code mentions it.

## Stage 3 — Inspect internals with appropriate caution

For each important category identify one relevant tagged Laravel class/file and explain its role at a high level: route dispatch/lifecycle, middleware pipeline, validation, database connection/transaction, session guard, authorization, exception handler, or test framework integration. Record the exact tag and path. Say explicitly what Laravel adds or hides: container resolution, configuration, middleware ordering, response conversion, model/query conventions, transaction handling, guards/providers, policy discovery, or test setup.

**Working looks like:** internal references support an explanation of the public behavior without becoming an invitation to depend on undocumented internals.

**Check it yourself:** open the tagged source and trace one request only as far as needed to validate the map. When an internal detail is uncertain, record “needs source verification” rather than asserting it.

## Stage 4 — Compare security and database boundaries honestly

Map DALT session/cookie behavior to Laravel’s session/auth stack, DALT ownership checks to Laravel authorization mechanisms, and DALT query/transaction code to Laravel database APIs. Then record what remains your responsibility in either framework: tenant scoping, authorization policy design, database constraints, transaction selection, RLS role/context setup, and meaningful behavior tests. Laravel conveniences do not automatically make a cross-workspace query safe or an owner-role RLS test meaningful.

**Working looks like:** the audit distinguishes application policy, framework mechanism, and PostgreSQL enforcement instead of claiming one replaces the others.

**Check it yourself:** use one C03 failure and one C05 database decision to explain how the analogous Laravel surface would help, what it would not prove, and why the DALT evidence remains relevant.

## Stage 5 — Write a maintainable reading map

Organize the result as a table with DALT concern/location, observed project behavior, Laravel public API/documentation, Laravel tag/internal file, what Laravel adds/hides, and caution/verification note. End with a short “not a rewrite” section and a list of three concepts you can now transfer to Laravel work. Keep URLs/version references current to the selected pin, and mark the audit’s review date.

**Working looks like:** a maintainer can follow an audited path from a DALT request to Laravel documentation/source without treating framework internals as a dependency.

**Check it yourself:** pick routing, transaction, and authorization rows. Explain each comparison aloud without reading. Then verify the public API in docs and file path/tag in source. Correct any drift you find.

## Decisions you have to make

- Which exact Laravel version/tag makes the internal comparison reproducible?
- Which DALT workflow gives the clearest request-lifecycle anchor?
- Which public Laravel API corresponds to the concern without falsely claiming equivalent semantics?
- Which internal file is useful explanatory evidence but unsafe to depend on?
- Which responsibilities remain application or PostgreSQL design decisions in both frameworks?

## Acceptance criteria

Nothing here is checked automatically. Read every item against research you actually performed.

- [ ] The audit pins a Laravel version/tag and records official documentation and tagged-source references.
- [ ] Routing, lifecycle, middleware, handlers/controllers, validation, database access, migrations, and transactions have DALT-to-Laravel rows.
- [ ] Sessions/authentication, authorization, errors/exceptions, and testing have DALT-to-Laravel rows.
- [ ] Each important row names a public API, relevant internal class/file, and what Laravel adds or hides.
- [ ] The map is anchored in a real C02 request trace and project locations, not only generic definitions.
- [ ] Security, tenant scope, constraints, transaction choices, and RLS are identified as responsibilities not erased by framework APIs.
- [ ] No Laravel dependency, rewrite, generated scaffold, or copied framework implementation was added to the issue tracker.
- [ ] Three comparisons have been verified against both pinned documentation and tagged source.

## Prove it to yourself

Explain the C02 request once in DALT terms and once in Laravel terms. At each step say what Laravel public API offers, what machinery it hides, and what remains your design decision. Then explain why a tagged internal class is evidence for learning but a fragile application dependency. Use the map only after attempting the translation.

## What this unlocks

You can now carry the mechanisms you built into Laravel work without treating either framework as magic. C07 uses this map, the C01 trace, C03 failures, C04 tests, and C05 evidence to make one final defensible explanation and freeze the project.
