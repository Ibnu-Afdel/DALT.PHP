> **What exists when you finish:** restarting your application leaves an issue tracker whose React client reads and changes PostgreSQL-backed DALT resources, rather than a resettable fixture.

## What you are building

Replace B04's temporary fixture with an application-owned API and PostgreSQL schema.

```text
React → typed client → DALT route/controller → parameterized SQL → PostgreSQL
```

The initial domain is deliberately small: `workspaces`, `projects`, and `issues`. Users,
memberships, comments, labels, and permissions are later work, not fields to guess now. Every
column you add speculatively is a column you will migrate away from in Part 06 when the real
requirement disagrees with the guess.

This is the largest single step in the track so far. B04 gave React a server to talk to; B05
makes that server yours, end to end, with durable data underneath it. When you finish, nothing
in the running system is course scaffolding.

## Why this milestone exists

A UI that survives one browser session is not an application database. This milestone makes
every boundary inspectable: a route selects a handler, a handler validates untrusted input,
SQL changes durable rows, constraints reject invalid states, and a stable JSON contract
returns to React.

That separation is the whole point, and it pays off the first time something is wrong. When a
value is missing from the screen, you can ask five separate questions — did the contract
promise it, did the handler map it, did the query select it, does the row contain it, did the
client parse it — and answer each one independently. Without the separation there is one
question, "why is it broken", and no way to divide it.

There is a second reason. Everything after this part assumes durable data. Part 06 attaches
users and authorization to rows; Part 07 routes to resources that persist; Part 10 puts this
database in a container. A fixture cannot carry any of that weight.

## Before you start

Complete FS05.1–FS05.3. Keep the B04 client boundary intact; replace its base URL only after
the real GET contract works under curl.

Configure PostgreSQL through the supported environment values, and read `config/database.php`
rather than copying a SQLite setting:

```sh
DB_DRIVER=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=dalt_php_app
DB_USERNAME=postgres
DB_PASSWORD=
DB_CHARSET=utf8
```

Prove the connection before you write a migration, so that the first failure has one possible
cause rather than two:

```sh
pg_isready -h 127.0.0.1 -p 5432
psql "postgresql://postgres@127.0.0.1:5432/dalt_php_app" -c '\conninfo'
```

Create migrations under `database/migrations/` and run them with `php artisan migrate`. Record
the output; you will compare against it in Stage 1.

Two placement rules. The issue tracker is learner application code: it belongs in `app/`,
`routes/`, `database/`, and `resources/`. Nothing you write in this milestone may go into
`framework/`, `config/`, `public/`, or `.dalt` — deleting `.dalt` must still leave your
application working. And keep any scratch experiments in `.dalt/workspace/`, which is
gitignored.

## Stage 1 — Make the schema tell the truth

Create ordered SQL migrations for workspaces, projects, and issues. Use primary keys, foreign
keys, intentional `NOT NULL`, timestamps, status and priority check constraints, a domain
uniqueness rule such as `UNIQUE(workspace_id, slug)`, and an index on the issues foreign key.

**Working looks like:** `php artisan migrate` applies each new migration exactly once; a second
run reports `No migrations to run.`; `php artisan migrate:fresh` rebuilds the whole schema from
empty; the database rejects an issue without a project and rejects a duplicate project slug
within one workspace while accepting it in another.

**Check it yourself:** run `\d workspaces`, `\d projects`, and `\d issues` in psql and read the
constraint list against the SQL you wrote — "the migration ran" and "the schema is what I
intended" are different claims. Then attempt one invalid insert of each kind and record the
exact error text. Explain why a TypeScript union cannot enforce any of this when another client
writes directly to the database.

## Stage 2 — Replace the read fixture

Register DALT routes for project and issue list and detail behaviour. Return the documented
JSON envelope consistently, map rows through one function rather than returning `SELECT *`, and
make a missing item return 404 rather than an empty 200. Use a join where the response needs
project context; do not issue a query per row.

**Working looks like:** a direct GET returns persisted data after an application restart, and
the existing React client renders it without knowing PostgreSQL exists.

**Check it yourself:** use curl before opening React, and compare status, JSON, and database
rows for four cases — an existing id, a missing id, an empty collection, and a filtered
collection. Then restart the server and repeat one of them; the data must be identical.

## Stage 3 — Make mutations honest

Implement POST, PATCH, and DELETE for issues. Allowlist input fields, collect all validation
errors rather than returning on the first, return 422 in the contract's shape, classify
constraint failures deliberately, and return the stored row rather than a client guess. Use 201
for creation and 204 with no body for a successful delete.

**Working looks like:** an invalid title preserves the React draft and shows the server's
message; changing an issue survives a reload; deleting it removes both the row and any stale UI
selection.

**Check it yourself:** inspect each request and response, then query PostgreSQL after every
mutation — the response is a claim and the row is the fact. Send an extra unaccepted JSON field
such as `"is_admin": true` and prove from the row that it altered nothing. Send a PATCH with an
empty object and confirm it is refused rather than silently succeeding.

## Stage 4 — Prove one atomic operation

Add a minimal activity table only if it earns its place, then make one operation write an issue
and an activity record inside a single database transaction. Force the second write to fail in a
controlled way.

**Working looks like:** a successful operation creates both records; a forced second failure
leaves neither record committed and the client receives an error rather than a 201.

**Check it yourself:** count rows before and after each case from a *separate* psql session —
inside your own uncommitted transaction you would see your own writes and conclude wrongly. A
caught exception without a rollback is not proof, and neither is a rollback you did not observe
from outside.

## Stage 5 — Retire the fixture and confirm the seam

Point `VITE_API_BASE_URL` at your DALT server and change nothing else in the frontend. Work
through every screen, fix the differences, and then delete the fixture copy from your workspace.

**Working looks like:** the application runs against your own backend with no component
changes — only the environment variable and, at most, the API client module.

**Check it yourself:** list every difference you had to fix and name which side was wrong. Ids
are now database integers rather than `ISS-41` strings, and CORS is now your server's
responsibility rather than the fixture's; both are expected. If you had to edit a component,
write down why — that is where the FS04.3 boundary leaked, and it is worth knowing before
Part 07 depends on it.

## Decisions you have to make

- Which response envelope lets the current client distinguish data from errors?
- Which issue fields are required on create and permitted on patch?
- What should deleting a project do to its issues, and why?
- Which filters and sort keys will you explicitly allow rather than interpolate?
- What is your maximum page size, and what happens to a request that exceeds it?
- Which real two-write event best communicates your application's history?
- Does an unknown sort key fall back to a default or return 422?

## Acceptance criteria

Nothing here is checked automatically. Read each item against software you ran.

- [ ] PostgreSQL contains migrations for workspaces, projects, and issues with keys and intentional constraints.
- [ ] A clean `php artisan migrate` run creates the schema, and a repeated run applies nothing new.
- [ ] `php artisan migrate:fresh` rebuilds the schema from an empty database.
- [ ] GET list and detail responses are persisted, and a missing resource returns 404.
- [ ] POST, PATCH, and DELETE use documented status codes and response bodies.
- [ ] Invalid input produces a stable 422 shape with no database write.
- [ ] An unaccepted JSON field provably cannot alter a row.
- [ ] Filters, sorting, and pagination are parameterized and explicitly allowlisted.
- [ ] No response returns a raw database row or a raw PDO error message.
- [ ] React uses its existing API client boundary, with no direct database knowledge.
- [ ] Restarting DALT does not erase projects or issues.
- [ ] A forced second write in the chosen transaction rolls back the first, proven from a separate session.
- [ ] The fixture is deleted and nothing in the running application depends on it.
- [ ] Deleting `.dalt` would leave my application working.
- [ ] `php artisan test`, `npm run typecheck`, `npm run lint`, and `npm run build` pass.

## Prove it to yourself

Close the editor and draw the complete path for creating an issue: URL, method, input fields,
validation decision, transaction boundary, SQL parameters, constraint, status code, response
JSON, client parse, client state update, and visible row. Then draw the forced-failure path and
mark exactly where the first write ceases to exist.

Then answer one question without looking: if a user reports that an issue they created is
missing, what are the first three things you would inspect, and in what order? A milestone you
have genuinely finished makes that question easy, because you built each boundary separately
and you know how to look at each one.

## What this unlocks

Part 06 can now test real behaviour and attach users, sessions, and authorization to a real
persistence boundary. It does not need to undo a fixture or explain away data that disappears
on restart. Part 07's routing has resources worth routing to, and Part 10's containers have a
database worth containerising.
