> **What exists when you finish:** an issue tracker whose representative list and search queries are measured and documented, whose indexes and full-text search are justified by that evidence, whose multi-step issue workflow is atomic under failure and safe under a reproduced race, and whose workspace records are protected by both application authorization and tested PostgreSQL row-level security.

## What you are building

Turn the Dockerized, authenticated issue tracker into a database-aware application. This is not a request to add every PostgreSQL feature. It is a request to make four specific promises observable:

```text
the list query has a measured workload and a plan-backed index decision
issue search is PostgreSQL full-text search, not a renamed substring filter
one multi-step workflow cannot half-succeed or silently win a concurrent race
tenant isolation remains true at the database role, not only in controller code
```

The project already has users, workspaces, projects, issues, authentication, authorization, tests, and Compose. Preserve those boundaries. B11 improves the existing issue tracker; it does not create a separate performance demo, replace DALT authorization with database policy, or add JSONB merely to check a feature off a list.

## Why this milestone exists

Basic CRUD can hide two expensive kinds of defect. First, a query that feels instant on a developer's handful of rows can scan, sort, and join far more work than the product needs at realistic scale. Second, a request that looks correct alone can produce partial state or contradictory outcomes when another request overlaps it. Database-aware development replaces both guesses with evidence: plans, data distributions, transaction transcripts, and a role that actually experiences RLS.

The design principle remains the course's through-line: introduce a capability because the growing project has a problem it solves. The issue list creates planner/index pressure. Search creates language-aware matching pressure. A move plus activity history creates atomicity pressure. Workspace tenancy creates defense-in-depth pressure. Keep each decision connected to one of those facts.

## Before you start

Complete FS11.1 and FS11.2. Start the Compose environment, run the current migrations, and confirm existing API behavior tests still pass before changing schema. Make a disposable database or backup before experimenting. Use the exact service, user, database, and PostgreSQL image configured by your project; commands below use `db`, `issue_tracker`, and PostgreSQL 17 only as examples.

```sh
docker compose up -d
docker compose ps
docker compose exec db psql -U issue_tracker -d issue_tracker -c 'select current_database()'
php artisan test
```

Do not use a production-like shared database for destructive experiments. Do not run `EXPLAIN ANALYZE` on mutation SQL outside a transaction that you explicitly roll back. Do not commit a local password, dump containing personal data, generated database volume, or a temporary workaround that weakens authentication.

## Stage 1 — Create reproducible representative data

Add a deterministic seed/reset path that produces several workspaces, dozens of projects, and enough issues to make the planner consider real alternatives. The exact count should be the smallest stable number that makes the chosen query meaningful on the supported environment. Distribution matters more than spectacle: include several statuses, some assignees, different dates, and searchable title/description words. Record the command and resulting counts in the project README or database notes.

**Working looks like:** a fresh local database can reach the same useful shape without hand-created rows, and the dataset contains more than one workspace so tenant tests are meaningful.

**Check it yourself:** reset/seed once, then run grouped counts. Repeat from a clean local volume or database. If the counts differ unexpectedly, fix the seed before interpreting a query plan.

```sh
docker compose exec db psql -U issue_tracker -d issue_tracker -c 'ANALYZE issues'
docker compose exec db psql -U issue_tracker -d issue_tracker -c "SELECT workspace_id, status, count(*) FROM issues GROUP BY 1,2 ORDER BY 1,2"
docker compose exec db psql -U issue_tracker -d issue_tracker -c "SELECT count(*) FROM issues"
```

## Stage 2 — Measure one issue-list query before and after

Choose the actual query powering a workspace issue-list screen. It must have a named workspace scope, a meaningful status or assignee filter, deterministic `created_at` ordering, and pagination. Save the exact SQL, parameters, database scale, `EXPLAIN`, and `EXPLAIN (ANALYZE, BUFFERS)` output before creating an index. The point is to read the plan from bottom to top: access path, filtered candidates, sort if present, and limit.

Then add the narrowest index justified by this workload. `(workspace_id, status, created_at DESC)` is a likely hypothesis, not a required incantation. Rerun the exact same statement and parameters after migration and `ANALYZE`; record whether estimated and actual rows, buffers, sort work, or elapsed time changed. A sequential scan can be correct when the filter returns most rows. Do not force an index scan or select a contrived parameter solely to make the new index look successful.

**Working looks like:** the repository has a migration for an evidence-backed index and durable plan notes that explain its predicate/order and write/storage cost.

**Check it yourself:** compare before/after output and explain one plan node. Insert/update a representative issue and confirm the application still behaves correctly; an index is maintained on writes, so read benefit is not free.

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, title, status, assignee_id, created_at
FROM issues
WHERE workspace_id = 7 AND status = 'todo'
ORDER BY created_at DESC
LIMIT 20;
```

## Stage 3 — Add bounded full-text search and decide JSONB honestly

Implement issue search over title and description with PostgreSQL text search, an explicit configuration, and a GIN index. Define behavior for an empty search, a no-match search, and punctuation or an inflected word. Keep every search scoped to the authorized workspace in application code. A query containing `ILIKE '%term%'` may be useful elsewhere, but it does not satisfy this stage's FTS requirement.

If the project truly has flexible integration metadata—such as an external source and reference number—document its shape and use JSONB narrowly. If it does not, write “rejected” in the decision record and do the focused JSONB exercise from FS11.1 without adding a permanent column. Foreign keys, status, workspace ownership, labels, and fields the product filters/orders regularly remain relational.

**Working looks like:** search returns matching issue documents by text-search semantics, and the migration shows the selected configuration and GIN index. The JSONB decision names a real use or an explicit rejection.

**Check it yourself:** search for one expected match, one expected no-match, and one punctuation/word-form case. Inspect the query plan. Confirm a workspace user cannot search another workspace's issue through an altered URL or API request.

```sql
SELECT id, title
FROM issues
WHERE workspace_id = 7
  AND search_document @@ websearch_to_tsquery('english', 'login timeout')
ORDER BY created_at DESC
LIMIT 20;
```

## Stage 4 — Make one issue operation atomic and race-safe

Choose a workflow with more than one durable effect, such as moving an issue, writing an activity record, and updating a real related state. Put the database effects inside one transaction. Deliberately cause the second write to fail in a safe test/transaction and prove the first write did not remain. Keep external requests outside the open database transaction or use an explicit reliable delivery design; this milestone does not claim a remote call can be rolled back by PostgreSQL.

Next choose a single invariant and reproduce its broken concurrent version with two `psql` sessions. A good bounded example is “only one successful claim of an unassigned issue.” Capture Session A and B's overlapping reads/writes. Fix it with an atomic conditional update, a carefully scoped row lock, a constraint, or another mechanism that directly expresses the invariant. The final state alone is insufficient: show that the losing request receives an honest conflict outcome.

**Working looks like:** a failure rolls back every database effect of the selected workflow, and two overlapping attempts cannot both report success.

**Check it yourself:** save a short transcript or test evidence for rollback, the broken race, and the fixed race. Rerun existing backend tests after the change; authorization and CSRF behavior must not regress.

```sql
UPDATE issues
SET assignee_id = 9, updated_at = now()
WHERE id = 42 AND assignee_id IS NULL
RETURNING id;
```

## Stage 5 — Add and prove row-level tenant isolation

Apply RLS only after the application-level workspace membership checks already work. Identify protected tenant-owned tables—at minimum projects, issues, and comments or their equivalent—and make their workspace key and policy reasoning explicit. Choose a tenant-context mechanism that is safe for the real PHP database connection lifecycle. If using a setting, make it transaction-local and establish it inside the same transaction as the query; do not leak tenant context across reused connections.

Create policies with both `USING` and `WITH CHECK` where appropriate. `USING` controls existing rows that can be seen or targeted. `WITH CHECK` stops a tenant from inserting or updating a row to claim another workspace. Grant the required table privileges to a restricted application role. Do not test as superuser, table owner, or a role with `BYPASSRLS`; those can bypass the policy and turn a green check into false confidence.

**Working looks like:** the restricted role can read/write its configured workspace only, and both directions of cross-tenant read/update/delete/insert are refused or affect zero rows as the policy semantics dictate.

**Check it yourself:** execute the tests from the restricted role with workspace A context, then repeat for B. Inspect `pg_roles`, table ownership, and `pg_policies`. Confirm the real application uses the intended restricted role in the environment where this boundary is claimed.

```sql
SET ROLE issue_app;
BEGIN;
SELECT set_config('app.workspace_id', '7', true);
SELECT id FROM issues WHERE workspace_id = 8;
INSERT INTO issues (workspace_id, title, status)
VALUES (8, 'cross-tenant attempt', 'todo');
ROLLBACK;
RESET ROLE;
```

## Stage 6 — Document evidence and run the complete behavior path

Add a compact `docs` or project-README section with: seed command/data scale; the list SQL and before/after plans; index and FTS rationale; JSONB decision; transaction invariant; concurrency transcript; role name and tenant-context lifecycle; tables/policies; and the exact role-based RLS proof. Keep secrets and raw sensitive data out of that record. Then run the normal application path: authenticated API/browser work, backend tests, frontend typecheck/lint/build where those commands live, and the Compose database commands.

**Working looks like:** a new maintainer can reproduce the evidence and explain why each database feature exists without reading source archaeology.

**Check it yourself:** from a clean copy or resettable database, follow the documentation. If any service name, environment precondition, role password, migration order, or data-reset step is implicit, document it. Re-run one workspace list, one search, one move, one claim conflict, and one tenant-crossing attempt.

## Decisions you have to make

- Which issue-list SQL and parameters represent the product's real workload?
- What dataset scale/distribution is the smallest honest basis for planning evidence?
- Which index column order follows the predicates and ordering shown by the plan, and what write cost will you accept?
- What text-search configuration and fields match the application's language and search promise?
- Does flexible integration metadata truly justify JSONB, or should it remain a lesson exercise?
- Which workflow needs atomicity, and which exact invariant is endangered by overlap?
- Does a conditional write, lock, constraint, or transaction retry best express that invariant?
- How is tenant context passed to PostgreSQL safely for the actual connection lifecycle?
- Which restricted role proves RLS, and why is it neither owner nor bypassing role?

## Acceptance criteria

Nothing here is checked automatically. Read every item against software you actually built and ran.

- [ ] A deterministic, resettable seed produces several workspaces and a representative issue distribution.
- [ ] A real workspace issue-list query has saved SQL, parameters, pre-index plan, and post-index plan.
- [ ] The chosen index is implemented by migration and its column order, benefit, and write/storage cost are documented.
- [ ] Issue search uses explicit PostgreSQL FTS over title/description with a GIN index, not a `%term%` substitute.
- [ ] Empty, no-result, and punctuation/word-form search behavior has direct evidence.
- [ ] JSONB is either bounded by a real flexible metadata need or explicitly rejected without corrupting relational fields.
- [ ] A selected move/activity workflow rolls back completely when one database effect fails.
- [ ] Two overlapping sessions reproduce a named broken invariant, and the repaired operation gives only one success.
- [ ] RLS policies cover protected tenant records with correct `USING` and `WITH CHECK` behavior.
- [ ] A non-owner, non-superuser, no-BYPASSRLS role proves own tenant access and reciprocal cross-tenant denial for read, change, delete, and write attempts.
- [ ] Existing API authorization, authentication, CSRF, and frontend behavior still pass independently of RLS.
- [ ] Documentation lets a fresh maintainer reproduce the data, plans, transaction proof, and role-based policy test.
- [ ] Deleting `.dalt` would still leave the framework and learner application independent of course artifacts.

## Prove it to yourself

Close the lessons and draw one issue-list request: browser/filter, API authorization, SQL predicates, planner choice, index/search structure, returned rows. Then draw Session A and B around the race and name the exact point your fix serializes or conditionally permits work. Finally draw the two security layers: application membership decides which workspace context may be chosen; RLS makes a query under the restricted database role unable to escape that context. Explain why a successful policy test as an owner proves much less than the same test as `issue_app`.

## What this unlocks

The capstone starts with an application whose database decisions are explainable rather than accidental. You have evidence for performance and search, a bounded concurrency design, and a tenant boundary that survives a missing SQL predicate. Part 12 adds no major technology: it audits, hardens, explains, and freezes this integrated system.
