> **What exists when you finish:** an evidence-backed performance and database decision record for one real issue-tracker query, including before/after plans, correctness proof, tradeoffs, and an honest review of the project’s PostgreSQL boundaries.

## What you are building

Select one query that the completed C02 workflow actually executes—normally a workspace-scoped issue list, filter, or search—and perform a final database review around it. Produce a durable record with the exact SQL and parameters, representative data scale, pre-change plan, hypothesis, smallest justified change, post-change plan, correctness evidence, and read/write/storage tradeoff. Then review constraints, indexes, full-text search, JSONB, transaction boundaries, concurrency, and RLS as decisions in the final product rather than as features to collect.

This is not a benchmark contest and it is not an instruction to force an index scan. A sequential scan may be correct at your scale. A slower plan may be safer when it preserves a necessary ordering or authorization predicate. The deliverable is an explanation supported by `EXPLAIN`, representative data, and a working application—not a screenshot of a lower millisecond number.

## Why this milestone exists

Part 11 introduced planner evidence, full-text search, transactions, concurrency, and row-level security because a growing application needs more than CRUD intuition. The capstone makes sure those decisions still match the application that survived C02–C04. A query that was once a lesson example can drift away from the UI; an index can be retained without workload evidence; a policy can be true on paper but irrelevant to the role that runs the app.

Reviewing one real path keeps the work bounded. It teaches a repeatable method: observe the exact workload, state a hypothesis, change one cause, remeasure, prove results remain correct, and record the cost. That method applies when the answer is “keep the current design” just as much as when the answer is a migration.

## Before you start

Finish C04. Use resettable representative local data, not a shared or production-like database. Identify the application’s PostgreSQL version, service, role, and database from its actual configuration. Run the normal behavior suite first so you can distinguish a query change from an unrelated regression.

```sh
docker compose up -d
php artisan test
npm run test
docker compose exec db psql -U issue_tracker -d issue_tracker -c 'ANALYZE issues'
```

Create `docs/database-review.md` in the learner project. Redact values that should not live in source. Never execute `EXPLAIN ANALYZE` against a mutation unless it is safely inside a transaction you roll back. Never disable RLS, force planner settings, or use an owner role merely to make an experiment look favorable.

## Stage 1 — Capture an honest workload and baseline

Choose a query with the real workspace predicate, filters, ordering, pagination, joins, and search terms used by the feature. Record parameters and data distribution: number of workspaces, projects, issues, status proportions, and result size. Capture `EXPLAIN` and `EXPLAIN (ANALYZE, BUFFERS)` before changing anything. Read the plan from access path through filter, join, sort, limit, estimates, actual rows, buffers, and elapsed time.

**Working looks like:** another developer can reproduce the baseline and understand why it represents the product rather than a conveniently selective SQL fragment.

**Check it yourself:** trigger the matching browser/API request and compare its parameters with the recorded query. Run grouped counts and a second realistic parameter set. If an assumed index is not used, record that fact before deciding it is a problem.

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, title, status, created_at
FROM issues
WHERE workspace_id = 7 AND status = 'todo'
ORDER BY created_at DESC
LIMIT 20;
```

## Stage 2 — Make one justified change or justify no change

State one falsifiable hypothesis. It may be that a composite index matching `workspace_id`, a selective filter, and order removes sort/candidate work; that FTS needs its GIN index; that a join needs no extra index because the current data/result shape makes the scan sensible; or that a query must be rewritten to preserve a correct tenant predicate. Apply only the smallest change that tests the hypothesis, preferably through migration/query code rather than an unrecorded console tweak.

**Working looks like:** the post-change plan is comparable to the baseline and the record explains both observed benefit and cost.

**Check it yourself:** rerun the same SQL/parameters after `ANALYZE`; inspect actual versus estimated rows and buffers, not only total time. Insert/update representative rows and run the workflow. If the change did not help, revert it and document the rejection; an evidence-backed no is a successful review.

## Stage 3 — Prove correctness and review database promises

Run the C02 workflow with the new or retained query. Test empty search, no match, punctuation/word-form search if FTS is present, pagination/order, and workspace isolation. Review constraints against invariants, each index against observed workload, FTS configuration and scope, and JSONB as a specific use or explicit rejection. Review transaction boundaries and concurrency invariant from C03/C04; an index review does not erase atomicity work. Review RLS using the restricted role and tenant context, not a privileged connection.

**Working looks like:** performance evidence is paired with behavior/security evidence, so a faster query is not accepted if it returns another tenant’s data or changes ordering semantics.

**Check it yourself:** query with two workspace contexts, run the role-based RLS proof, and rerun the transaction/authorization tests after the database change. Explain which constraints reject bad data even if a future handler has a bug.

## Stage 4 — Make the record reproducible

Write the final decision in concise form: workload; baseline; hypothesis; change or rejection; before/after plans; correctness checks; write/storage/maintenance cost; rollback path; and next signal that would justify revisiting it. Document the data reset/seed command and database role assumptions. Link to migrations and tests rather than pasting private dumps or huge plan output without context.

**Working looks like:** a maintainer can repeat the evidence from a clean local database and knows what fact would invalidate the decision.

**Check it yourself:** follow your own record in a new shell or after reset. Check that service names, environment variables, role setup, migration order, and seed preconditions are explicit. Re-run one list/search, one transaction failure, and one cross-tenant denial.

## Decisions you have to make

- Which query and parameters are representative of actual user behavior?
- Which observed plan feature is worth changing, and which is acceptable at this scale?
- Does an index column order match predicates and ordering, and what maintenance cost does it add?
- Does FTS serve a real product promise, and is JSONB justified or explicitly rejected?
- Which security/correctness checks must accompany an apparently faster plan?

## Acceptance criteria

Nothing here is checked automatically. Read every item against software you actually ran.

- [ ] A real workflow query has saved SQL, parameters, representative data description, and pre-change plan evidence.
- [ ] The record states one hypothesis and either a migration/query change or an evidence-backed decision to retain the design.
- [ ] Comparable post-change plan evidence explains rows, buffers, ordering, and observed tradeoffs.
- [ ] The workflow, ordering/pagination, search semantics, and tenant scope remain correct after the decision.
- [ ] Constraints, indexes, FTS, JSONB, transaction boundaries, concurrency, and RLS are each reviewed as explicit final decisions.
- [ ] Role-based RLS proof and relevant authorization/transaction tests still pass.
- [ ] A resettable seed and exact reproduction commands are documented without secrets or private data.
- [ ] The decision record names a signal that would cause future remeasurement.

## Prove it to yourself

Close the plan output and explain the selected query from UI filter to PostgreSQL access path. Why is the chosen index useful or unnecessary? Which cost does it impose on writes? Why can a lower elapsed time still be wrong? Then describe how application membership checks and RLS both constrain the query. Consult evidence only after your explanation.

## What this unlocks

The database layer is now measurable and explainable rather than frozen by intuition. C06 maps the verified DALT mechanisms to Laravel without rewriting the app, while C07 packages the plans, test commands, and final workflow into a result you can defend and preserve.
