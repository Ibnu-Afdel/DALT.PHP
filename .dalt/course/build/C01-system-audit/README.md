> **What exists when you finish:** a closed-book-first, evidence-backed map of the issue tracker that a maintainer can use to find every important boundary before changing code.

## What you are building

Build a system audit, not a feature. Before editing the application, write a compact record that reconstructs the working issue tracker as it exists today: browser routes, React features, API routes, database tables and relationships, authentication/session movement, one request trace, state ownership, runtime services, and known weak areas. Put the record in the learner project, for example `docs/system-audit.md`; it belongs with the application, not inside `.dalt`.

Start closed-book. Draw from memory first, then verify every assertion by running the system, reading the source, inspecting PostgreSQL, and observing the browser. The point is to discover the difference between a system you can operate and a system you can explain. Keep the audit specific to your implementation; a generic diagram copied from this brief is not evidence.

## Why this milestone exists

Large changes fail when their author changes the visible component while missing the data, authorization, cache, or deployment boundary behind it. The preceding parts deliberately introduced a complete chain: React UI, client and server state, HTTP/JSON, DALT routing and middleware, sessions, validation, PostgreSQL queries and policies, tests, and Docker. C01 makes that chain inspectable before capstone work makes it more complex.

An audit is also a practical debugging tool. A route map identifies which handler should receive a request. A feature map reveals where a loading state belongs. A schema map makes foreign-key and tenant implications visible. A request trace gives an incident a finite path to inspect rather than an invitation to guess. Treat uncertainty as useful output: a box marked “unknown; inspect this” is more honest than an invented arrow.

## Before you start

Finish B11 and start the same Compose environment you use for development. Confirm that the existing system is runnable before documenting it. Substitute your own service and database names for the examples.

```sh
docker compose up -d
docker compose ps
php artisan test
npm run typecheck
npm run build
```

Have browser DevTools, the route source, migrations, and a `psql` connection available. Do not put credentials, session cookies, database dumps, or personal data in the audit. Use IDs and redacted examples. The audit describes the architecture; it is not a place to commit a temporary secret merely because it made a command convenient.

## Stage 1 — Reconstruct the user-facing and API route maps

List every user-visible URL and every API endpoint that your final workflow can reach. For each API route record method, path parameters, authenticated actor, expected success status, important failure statuses, handler, and resource it affects. For each UI route record the React feature or page, loader/error boundary, and the API calls it initiates. Begin from memory; then compare it with the router and a browser network trace.

**Working looks like:** a reader can start at a URL or an HTTP method/path pair and locate the responsible UI and server code without a repository-wide search.

**Check it yourself:** navigate through the real application while DevTools Network is open. Compare the observed method/status pairs with your map. Send one unauthenticated request and one request for a workspace the current user does not belong to; record the actual boundary and response without claiming an error status you did not see.

```sh
curl -i http://localhost:8000/api/workspaces
docker compose exec db psql -U issue_tracker -d issue_tracker -c '\dt'
```

## Stage 2 — Map features, state, and authentication

Make a frontend feature map. Name the modules responsible for issue listing, issue detail, creation/editing, filtering/search, login/logout, workspace selection, and errors. For each piece of state say whether it is URL state, local component state, context/reducer/store state, or server state/cache; name its owner and its invalidation or reset event. Include absent states: loading, empty, permission-denied, malformed response, and network failure.

Draw the authentication/session flow separately. Include login input, server validation, password verification, session creation, cookie transport, middleware lookup, user/role/workspace membership resolution, logout, and the frontend’s reaction to a 401/403 or redirect. Do not call a client-side boolean authorization. The server and database boundaries must appear in the diagram.

**Working looks like:** every important state has one identified owner, and a reader can tell why server data is refreshed instead of copied into a second permanent cache.

**Check it yourself:** sign in, reload, switch workspace, sign out, and attempt a protected URL. Watch storage and network headers only as needed; never paste a live cookie into the audit. Identify one state you originally classified incorrectly and correct the map from evidence.

## Stage 3 — Map the database and runtime environment

Draw tables, primary keys, foreign keys, tenant/workspace keys, unique constraints, indexes, full-text structures, transaction-sensitive operations, and RLS-protected tables. State which database role the application uses and which role proves RLS. For each table, distinguish application authorization from PostgreSQL policy; neither replaces the other. Include migrations or schema files that establish each important fact.

Then map runtime dependencies: browser, frontend dev/build artifact, DALT/PHP service, PostgreSQL, and Docker Compose networking/health checks. Note where configuration enters and which values are safe to publish. Record how a fresh developer migrates and seeds the system. This map should make an unavailable service diagnosable, not merely list container names.

**Working looks like:** the schema map explains an issue’s workspace boundary, related records, search/index decision, and the role/policy path that protects tenant data.

**Check it yourself:** inspect the live schema and roles, then compare them with migrations. Resolve differences instead of documenting an idealized schema.

```sql
SELECT table_name FROM information_schema.tables WHERE table_schema = 'public';
SELECT schemaname, tablename, policyname FROM pg_policies ORDER BY tablename, policyname;
```

## Stage 4 — Trace one request end to end

Choose a meaningful action, such as creating an issue or assigning an unassigned one. Trace it from click to rendered result: React handler, validation and local state, mutation/cache behavior, HTTP request, DALT route, middleware, authentication and authorization, request validation, query or transaction, constraints/RLS, response mapping, cache invalidation/update, and render. Name the data shape at the browser/API/database boundaries and any conversion or parser between them.

**Working looks like:** the trace has real file/function names and observed request/response evidence, not arrows labelled “backend magic.”

**Check it yourself:** trigger the action with DevTools open and correlate the response with a database query. Deliberately use invalid input once so the trace includes where success and validation failure diverge.

## Decisions you have to make

- Which workflow is representative enough to become the audit request trace?
- What detail lets a maintainer navigate the system without turning the audit into a source-code copy?
- Which state classifications were assumptions until you observed a reload, retry, or invalidation?
- Which tables and policies truly participate in tenant isolation?
- What known weakness is safe to defer, and what weakness blocks the capstone workflow?

## Acceptance criteria

Nothing here is checked automatically. Read every item against the application you actually ran.

- [ ] A route map identifies every capstone-relevant UI route and API method/path with its handler or feature owner.
- [ ] A frontend feature map identifies state category, owner, loading, empty, and failure behavior for each major feature.
- [ ] An authentication/session map follows login, middleware lookup, protected action, and logout through real boundaries.
- [ ] A schema map records keys, relationships, constraints, indexes/search structures, tenant keys, and RLS scope.
- [ ] A runtime map names services, configuration boundaries, migration/seed path, and health/dependency assumptions.
- [ ] One observed request trace reaches from click through PostgreSQL and back to a rendered state update.
- [ ] Known weak areas are ranked with evidence and distinguish a blocker from a consciously deferred improvement.
- [ ] The audit contains no secret, live cookie, private dump, or copied production data.

## Prove it to yourself

Close the source files. Draw the chosen request trace and answer: where does the user identity become trusted, where is workspace membership checked, where can the database reject a write after application validation, and which cache or state owner makes the UI change? Then point to each answer in the running system. If you cannot locate an edge, make that uncertainty a capstone task rather than smoothing it over.

## What this unlocks

You now have the map needed to complete and harden a coherent workflow without accumulating accidental features. C02 uses the map to select one user-visible slice; C03–C05 use it to choose failure, test, and performance boundaries; C06 turns the verified backend map into a Laravel comparison; C07 uses it to explain and freeze the result.
