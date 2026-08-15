# FS11.2 — Transactions, concurrency and row isolation

Lesson ID: FS11.2
Title: Transactions, concurrency and row isolation
Part: 11 — PostgreSQL deeper
Order: 2
Status: Published
Estimated effort: 110–140 minutes
Difficulty: Advanced
Prerequisites: FS11.1 — Query performance and PostgreSQL capabilities
Project milestone: B11 — Database-aware application
Primary source dossier: POSTGRESQL_DOCS.md
Last reviewed: 2026-08-15

## Why this matters

The request that moves an issue can update its status, append an activity event, and change a related counter. If the second write fails after the first succeeds, the tracker tells a story that never happened. A transaction gives those writes one outcome: all commit, or all roll back. But atomicity alone does not settle what two simultaneous requests can see or overwrite.

Workspace authorization in application code is essential, yet it depends on every query continuing to include the correct predicate. PostgreSQL row-level security (RLS) adds a database boundary: a correctly configured application role is filtered even when a query forgets a workspace condition. It is defense in depth, not a replacement for authentication, authorization, or careful SQL. This lesson proves behavior as the role that actually experiences the policy, because an owner, superuser, or `BYPASSRLS` role can make a green test meaningless.

## Before you start

Required: FS11.1, a Compose database, authenticated workspace membership in the application, and tables for issues plus an activity/history record or an equivalent related update.

Going deeper in DALT Core — optional:
- Core PostgreSQL transaction and security material is optional study. This lesson is self-contained for the Fullstack track.

Open two terminals connected to the same disposable database. Label them Session A and Session B. Never test a race by running two statements sequentially in one psql prompt: concurrency requires overlapping transactions.

```sh
docker compose exec db psql -U issue_tracker -d issue_tracker
docker compose exec db psql -U issue_tracker -d issue_tracker
docker compose exec db psql -U issue_tracker -d issue_tracker -c 'SHOW transaction_isolation'
```

## By the end

- place a transaction boundary around a real multi-step issue operation;
- reproduce and explain a lost-update-style race with two sessions;
- choose a practical protection such as a row lock, optimistic version check, constraint, or retry;
- explain PostgreSQL's default Read Committed visibility at a useful level; and
- enable and verify RLS with a non-owner, non-superuser role that cannot bypass it.

## Predict before reading

Two browser requests read issue 42 with `status = 'todo'`. Each independently decides it may claim the issue, then both write `in_progress`. What final state will the table show? Does that prove only one claim happened? Now predict what occurs if Session A updates a row but does not commit while Session B runs `SELECT ... FOR UPDATE` on it. Record the prediction before coordinating the two prompts.

## Mental model

```text
transaction = a private sequence that either commits together or disappears

Session A: read ─ decide ─ write ─ commit
Session B: read ─ decide ─ write ─ commit    ← can invalidate A's assumption

application authorization → intended access decision
RLS policy + application role → database backstop for each row
```

PostgreSQL uses MVCC: readers generally see a consistent version without blocking ordinary writers. At the default Read Committed isolation, each statement sees rows committed before that statement begins. This is often a good default, but “I read that it was available” is not automatically reserved until your operation makes it so. Locks serialize selected conflicting operations. Constraints make invalid final states impossible. Serializable isolation detects some unsafe interleavings and can require a retry. Choose the smallest mechanism that protects the named invariant.

## 1. Make one operation atomic

Start with a failure that is visible. Moving an issue should update the issue and add an activity record. If a related project counter exists, include it only when it is a real invariant rather than a new feature invented for the lesson. Run the operation inside a transaction in your actual DALT database boundary; do not claim a controller's two separate `execute` calls are atomic merely because they are adjacent.

```sql
BEGIN;
UPDATE issues
SET status = 'in_progress', updated_at = now()
WHERE id = 42;
```

```sql
INSERT INTO issue_activity (issue_id, actor_id, event_type, created_at)
VALUES (42, 9, 'moved_to_in_progress', now());
COMMIT;
```

```sql
BEGIN;
UPDATE issues SET status = 'done' WHERE id = 42;
INSERT INTO issue_activity (issue_id, actor_id, event_type)
VALUES (42, 9, 'done');
ROLLBACK;
```

The rollback case must leave neither the status change nor the event. Write a behavior test or direct SQL proof that checks both tables after the intentional failure. A transaction protects its enclosed statements; it does not correct a missing authorization predicate, validate a bad transition, or make a remote HTTP call transactional.

## 2. Reproduce a concurrent decision

Use a simple claim rule: only an unassigned issue can be claimed. First reproduce the broken read-then-write flow. Both sessions can read the same old state before either writes, so the later write can silently replace a decision the application thought was exclusive.

```sql
-- Session A
BEGIN;
SELECT id, assignee_id FROM issues WHERE id = 42;
-- wait while Session B makes the same observation
```

```sql
-- Session B
BEGIN;
SELECT id, assignee_id FROM issues WHERE id = 42;
UPDATE issues SET assignee_id = 11 WHERE id = 42;
COMMIT;
```

```sql
-- Session A, acting on stale observation
UPDATE issues SET assignee_id = 9 WHERE id = 42;
COMMIT;
```

The row ends assigned to 9, but that final value does not reveal that both people received success. That is the defect. Capture the timeline and define the invariant in one sentence: “at most one successful claim can occur for an unassigned issue.” Avoid inventing a generic “increase isolation” fix before the invariant is explicit.

## 3. Protect the invariant deliberately

For a single-row claim, an atomic conditional update is often simpler than a separate read and lock. The affected row count is the evidence: one means this request claimed it; zero means another request already did. This approach also makes the application response honest.

```sql
UPDATE issues
SET assignee_id = 9, updated_at = now()
WHERE id = 42
  AND assignee_id IS NULL
RETURNING id, assignee_id;
```

```php
$statement = $pdo->prepare(
    'UPDATE issues SET assignee_id = :user, updated_at = now() '
    . 'WHERE id = :issue AND assignee_id IS NULL RETURNING id'
);
$statement->execute(['user' => $userId, 'issue' => $issueId]);
if ($statement->fetch() === false) {
    throw new DomainException('Issue was already claimed.');
}
```

When a decision needs to read related rows before writing, lock the selected row within the same transaction. Session B will wait for Session A's lock, then re-read current state rather than acting on a stale observation.

```sql
BEGIN;
SELECT id, assignee_id FROM issues WHERE id = 42 FOR UPDATE;
-- validate current state, update, append activity
COMMIT;
```

```sql
SELECT pid, wait_event_type, wait_event, query
FROM pg_stat_activity
WHERE datname = current_database();
```

Keep transactions short: do not hold locks while waiting for a browser, human input, or external API. Lock ordering matters when several rows are involved; acquire a consistent order. A deadlock is PostgreSQL protecting progress by aborting one transaction, so the application must treat the error as a retryable outcome where the operation is safe to retry.

## 4. Isolation is a contract, not a magic switch

Read Committed gives each statement a fresh committed view. Repeatable Read provides a stable transaction snapshot but may raise serialization failures in conflicting updates. Serializable asks PostgreSQL to prevent outcomes that cannot be explained by some serial order; it can abort a transaction that must be retried. Do not switch the whole app to Serializable to avoid reasoning about one invariant.

```sql
BEGIN ISOLATION LEVEL REPEATABLE READ;
SELECT status FROM issues WHERE id = 42;
COMMIT;
```

```sql
BEGIN ISOLATION LEVEL SERIALIZABLE;
-- perform a retry-safe invariant-preserving operation
COMMIT;
```

```text
serialization failure → rollback → retry whole transaction with bounded attempts
deadlock detected     → rollback → retry only if operation is idempotent/safe
constraint violation  → report domain conflict; do not blindly retry
```

An application retry begins the entire transaction again because the old snapshot is no longer valid. A retry must not duplicate an email, payment, external request, or activity entry. For the issue tracker, prefer a conditional update, a lock, or a constraint when it directly expresses the invariant, then test the concurrent behavior rather than merely asserting that `BEGIN` appears in source.

## 5. Add RLS as a database backstop

First make the tenant key explicit. Policies are easier to reason about when protected tables carry `workspace_id`; indirect joins can be valid but obscure a security boundary. The application establishes a trusted workspace context for each request using a connection-lifecycle-safe mechanism chosen for the actual PHP connection. This example uses a transaction-local setting, so it cannot leak to a reused connection after commit.

```sql
ALTER TABLE issues ENABLE ROW LEVEL SECURITY;
ALTER TABLE issues FORCE ROW LEVEL SECURITY;

CREATE POLICY issues_workspace_select ON issues
FOR SELECT TO issue_app
USING (workspace_id = current_setting('app.workspace_id', true)::bigint);
```

```sql
CREATE POLICY issues_workspace_write ON issues
FOR INSERT TO issue_app
WITH CHECK (workspace_id = current_setting('app.workspace_id', true)::bigint);

CREATE POLICY issues_workspace_change ON issues
FOR UPDATE TO issue_app
USING (workspace_id = current_setting('app.workspace_id', true)::bigint)
WITH CHECK (workspace_id = current_setting('app.workspace_id', true)::bigint);
```

```sql
CREATE POLICY issues_workspace_delete ON issues
FOR DELETE TO issue_app
USING (workspace_id = current_setting('app.workspace_id', true)::bigint);
```

`USING` determines existing rows visible or targetable by a command; `WITH CHECK` determines new row values allowed by insert/update. Both are needed: a policy that filters reads but lets an insert name another workspace is not tenant isolation. Apply equivalent policies to projects, comments, and other protected tenant-owned tables. Application authorization must still establish that the signed-in user is allowed to choose the workspace context; RLS does not authenticate a browser.

## 6. Prove RLS under the right role

Table owners typically bypass RLS unless forced, and superusers/BYPASSRLS roles bypass it. Create or use a restricted application role, grant only required table privileges, and execute evidence as that role. A policy listed in `pg_policies` proves syntax exists, not that it protects rows.

```sql
CREATE ROLE issue_app LOGIN NOINHERIT NOBYPASSRLS PASSWORD 'local-development-only';
GRANT USAGE ON SCHEMA public TO issue_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON issues TO issue_app;
```

```sql
SET ROLE issue_app;
BEGIN;
SELECT set_config('app.workspace_id', '7', true);
SELECT id, workspace_id FROM issues ORDER BY id;
COMMIT;
RESET ROLE;
```

```sql
SET ROLE issue_app;
BEGIN;
SELECT set_config('app.workspace_id', '7', true);
SELECT id FROM issues WHERE workspace_id = 8;
UPDATE issues SET title = 'should not change' WHERE workspace_id = 8;
INSERT INTO issues (workspace_id, title, status)
VALUES (8, 'should be refused', 'todo');
ROLLBACK;
RESET ROLE;
```

Run the reciprocal case for workspace 8. Assert tenant 7 sees its own records, cannot read/update/delete tenant 8 records, and cannot write a row claiming tenant 8. Also inspect the role attributes and table owner; do not accept a test authenticated as the database owner. Keep development passwords out of commits and use your Compose environment's secret mechanism.

## 7. Put the transaction boundary in the application deliberately

The SQL examples demonstrate behavior, but the issue tracker needs one connection for every statement in the transaction. Obtain the connection, begin, establish tenant context if it belongs to the transaction, perform the guarded writes, then commit. On every exception, roll back only when the transaction is active and translate known database outcomes into an honest HTTP/domain response. Do not catch an exception and continue issuing writes on a failed PostgreSQL transaction: PostgreSQL marks it aborted until rollback.

```php
$pdo->beginTransaction();
try {
    $context = $pdo->prepare("SELECT set_config('app.workspace_id', :workspace, true)");
    $context->execute(['workspace' => (string) $workspaceId]);

    $move->execute(['issue' => $issueId, 'status' => $targetStatus]);
    $activity->execute(['issue' => $issueId, 'actor' => $userId]);
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}
```

```text
connection A: BEGIN → set local tenant → statements → COMMIT/ROLLBACK
connection B: different request, different transaction/context
pooled connection: context disappears at transaction end because it was local
```

Inspect DALT's actual connection and transaction API before applying this shape. The essential rule is not a particular helper name: statements that must commit together share one connection and one explicit boundary. Put validation that needs no lock before opening the transaction when possible, then re-check state that can change after acquiring the lock or conditional update. This keeps locks short and prevents a slow request from becoming an accidental denial of service.

## 8. Constraints are concurrent correctness tools

Some invariants are best represented by database constraints rather than application branches. A unique constraint can stop two transactions from creating the same logical membership. A foreign key prevents a reference to a row that does not exist. A check constraint constrains values in one row. PostgreSQL arbitrates constraint conflicts even when two sessions raced past the same prior `SELECT`; application code must catch the specific violation and present a useful conflict rather than a 500.

```sql
ALTER TABLE workspace_memberships
ADD CONSTRAINT workspace_memberships_workspace_user_key
UNIQUE (workspace_id, user_id);
```

```sql
ALTER TABLE issues
ADD CONSTRAINT issues_status_check
CHECK (status IN ('todo', 'in_progress', 'done'));
```

```sql
INSERT INTO workspace_memberships (workspace_id, user_id)
VALUES (7, 9);
-- a concurrent duplicate is refused by the database, not accepted twice
```

A constraint does not encode every workflow rule. “Only a current workspace member may move this issue” needs authorization and often a transaction/lock across related records. “An issue belongs to a project in the same workspace” may need a schema design that makes the relationship enforceable. Write the invariant in product language first, then decide which portion is a constraint, which is a conditional update, and which remains application authorization.

## 9. Policy lifecycle and connection safety

RLS applies after normal privilege checks; a role without `SELECT` privilege does not gain it from a policy. Grant only the operations the application needs. `FORCE ROW LEVEL SECURITY` is important when a table owner might otherwise bypass policies, but it does not make a superuser test meaningful. Use a migration that records ownership, grants, enabling/forcing RLS, and policies together so a fresh database has the same boundary as your development machine.

`current_setting('app.workspace_id', true)` returns null when no context exists. A comparison to null does not match rows, which is safer than silently choosing a default workspace. Still, an absent context should be diagnosed in application tests: a query unexpectedly returning an empty list can otherwise look like a harmless product bug. `set_config(..., true)` is transaction-local only inside a transaction. A session-level `SET` can leak across reused/persistent connections and is unacceptable unless the lifecycle has an explicit reset discipline you can prove.

```sql
BEGIN;
SELECT set_config('app.workspace_id', '7', true);
SELECT current_setting('app.workspace_id', true);
COMMIT;
SELECT current_setting('app.workspace_id', true);
```

```text
inside transaction: '7'
after commit:       null (or absent context)
```

Policies must cover every command whose cross-tenant effect matters. A `SELECT` policy can make an update target zero rows, but explicit `UPDATE ... USING ... WITH CHECK` states both old-row and new-row requirements clearly. Test policy behavior with direct SQL and through the API: direct SQL proves the database boundary; API tests prove identity-to-workspace context is established correctly.

## 10. Design failure responses before the race happens

Concurrency control is visible product behavior. A conditional claim that returns no row is not an internal mystery: the request should return a conflict-shaped response, reload authoritative issue state, and tell the user the issue was claimed elsewhere. A serialization failure is different from an authorization denial; retry it only with a bounded policy and only when repeating the entire operation cannot duplicate an external effect. A unique-constraint violation is often a normal “already exists” conflict, while a foreign-key violation may reveal a stale selection or a programming mistake. Preserve the database error for server diagnostics, but do not expose raw SQL, role names, or policy expressions to a browser.

```text
0 rows from guarded update → 409-style domain conflict, refresh state
serialization/deadlock     → rollback, bounded whole-operation retry if safe
unique constraint          → domain conflict with a specific message
RLS empty result           → application distinguishes absent from unauthorized carefully
```

For RLS, do not turn every empty result into a confirmation that a resource does not exist if that changes your application's information-disclosure policy. The application can use its membership knowledge to choose an honest response, while the database still refuses the row. Test that the frontend invalidates or refetches server state after a losing race; a stale optimistic UI claiming success would recreate the defect at a different layer. This is why the milestone keeps existing API and frontend behavior tests alongside direct database proofs.

Record these responses in the operation's contract and tests. A maintainer should be able to distinguish a legitimate conflict from a transient database retry and from an authorization refusal without looking at an incidental exception message. That clarity makes concurrency behavior supportable after the two-session exercise is over.

## Common mistakes

- Treating adjacent SQL statements as a transaction without an actual boundary.
- Testing “concurrency” in one session, so no transactions overlap.
- Holding a lock while calling an external service or waiting for a user.
- Adding RLS only for `SELECT`, then allowing cross-workspace inserts through a missing `WITH CHECK`.
- Testing as a superuser, owner, or bypassing role and calling the result RLS coverage.

## When this goes wrong

If Session B does not block on `FOR UPDATE`, verify both sessions target the same committed row and Session A still holds the transaction open. If an activity record survives a failed move, confirm both statements use the same connection and transaction. If RLS returns no rows for everything, inspect the local setting inside the transaction and the column type cast. If RLS returns every row, check `rolbypassrls`, ownership, `FORCE ROW LEVEL SECURITY`, policy roles, and whether your app used the restricted role at all.

```sql
SELECT rolname, rolbypassrls FROM pg_roles WHERE rolname = 'issue_app';
SELECT tablename, policyname, cmd, roles, qual, with_check
FROM pg_policies WHERE tablename = 'issues';
```

## Exercise

**Goal:** prove one business operation cannot half-succeed, one concurrent claim cannot silently succeed twice, and tenant A cannot reach tenant B rows through the database role used by the application.

**Starting state:** the tracker has workspaces, issues, membership authorization, an activity table or equivalent related state, and the B11 query work.

**Requirements:** implement a transaction around a move plus activity write; reproduce a broken two-session claim; replace it with an invariant-preserving conditional update or lock; enable policies for protected tables; and prove both directions of tenant isolation with a non-owner, non-superuser, no-BYPASSRLS role.

**Verification:** save the two-session transcript/timeline, a rollback proof, and role-based SQL output showing own rows allowed, cross-tenant reads/writes denied, and reciprocal behavior. Run your existing API behavior tests afterward: RLS must not be the only authorization evidence.

**Mode: tool-run and manual-proof.** PostgreSQL sessions and project tests are the evidence; the milestone is explicitly self-assessed.

**Hints:** express the invariant before selecting a lock. `RETURNING` lets an atomic update tell you whether it won. Use `set_config(..., true)` inside an explicit transaction for transaction-local context. `ROLLBACK` makes destructive RLS experiments safe.

## In the project

Document the exact database role, tenant-context mechanism, connection-lifecycle rationale, and tables covered by RLS. Keep the app-level membership checks: they provide useful API errors and defend every non-database operation. The database policy catches a missing tenant predicate at the SQL boundary. B11 is complete only when the same product story holds across both: moving an issue is atomic, claiming it is race-safe, and an authorized workspace cannot become a cross-tenant query because a developer forgot one condition.

## Closed-book checkpoint

1. What does a transaction guarantee, and what concurrency problem can remain after adding one?
2. Why is a conditional `UPDATE ... WHERE assignee_id IS NULL RETURNING` safer than read-then-write?
3. When might a retry be needed, and why must it restart the whole transaction?
4. What is the difference between a policy's `USING` and `WITH CHECK` expressions?
5. Why can an RLS test pass while proving nothing when run as the wrong role?

## Resources

- [PostgreSQL transaction isolation](https://www.postgresql.org/docs/17/transaction-iso.html)
- [PostgreSQL explicit locking](https://www.postgresql.org/docs/17/explicit-locking.html)
- [PostgreSQL row security policies](https://www.postgresql.org/docs/17/ddl-rowsecurity.html)
- [PostgreSQL roles](https://www.postgresql.org/docs/17/user-manag.html)

Use the major version matching the PostgreSQL image actually pinned by the learner project.

## You are done when

You can show a rollback leaves no partial move, reproduce a real overlapping-session race, and explain why your chosen fix enforces its invariant. You can also demonstrate, using the restricted application role, that each tenant can access only its own protected rows and cannot insert/update a row claiming another tenant. You have preserved application authorization rather than presenting RLS as a replacement for it.

## Maintainer source record

Source dossier: `docs/dalt-fullstack/sources/POSTGRESQL_DOCS.md`.

Official sources: PostgreSQL 17 documentation for transaction isolation, locking, roles, and row security policies; URLs above.

Versions: learner environment is PostgreSQL 17 as pinned by Part 10 Compose material; policy syntax and URLs must be rechecked if the pinned image major changes.

Consulted: 2026-08-15; DALT's repository database/connection behavior was treated as the implementation truth and tenant-context architecture remains an application decision to document.

Curriculum authority: `docs/dalt-fullstack/CURRICULUM.md` §22, FS11.2; `PROJECT_BLUEPRINT.md` §§68–72.
