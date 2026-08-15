# FS11.1 — Query performance and PostgreSQL capabilities

Lesson ID: FS11.1
Title: Query performance and PostgreSQL capabilities
Part: 11 — PostgreSQL deeper
Order: 1
Status: Published
Estimated effort: 100–130 minutes
Difficulty: Advanced
Prerequisites: FS10.2 — Builds, health and debugging
Project milestone: B11 — Database-aware application
Primary source dossier: POSTGRESQL_DOCS.md
Last reviewed: 2026-08-15

## Why this matters

An issue list can be correct and still become the slowest request in the product. A table with fifteen rows tells you almost nothing about the query that will filter a workspace with thousands of issues. PostgreSQL is not a passive place to store rows: its planner estimates alternatives from statistics, then chooses an access path. An index is therefore a cost paid on writes and storage for a measured read workload, not a badge to attach to every column.

The mature issue tracker finally has a real question: find one workspace's open issues, optionally assigned to one person, newest first, with predictable pagination. This lesson makes the answer observable before changing schema. It also uses PostgreSQL full-text search instead of pretending a leading-wildcard `LIKE` query is the same feature. JSONB appears as a bounded capability, not a replacement for relationships.

## Before you start

Required: FS10.2 and a running Compose PostgreSQL service containing the issue tracker tables. Keep a disposable local database or a dump; `EXPLAIN ANALYZE` runs the statement, so it is not a harmless preview for mutations.

Going deeper in DALT Core — optional:
- The Core PostgreSQL material is optional study, not a prerequisite. This lesson teaches the performance decisions required for Fullstack.

Use the actual Compose service and configured database name from your project. The following `db` and `issue_tracker` names are examples; replace them deliberately.

```sh
docker compose exec db psql -U issue_tracker -d issue_tracker
docker compose exec db psql -U issue_tracker -d issue_tracker -c 'ANALYZE issues'
docker compose exec db psql -U issue_tracker -d issue_tracker -c '\\d+ issues'
```

## By the end

- distinguish a query's result correctness from its work and cost;
- capture and read `EXPLAIN` and `EXPLAIN ANALYZE` for a representative list query;
- justify a multicolumn index from predicates and ordering rather than guessing;
- implement PostgreSQL full-text search using an explicit configuration and GIN index; and
- decide whether flexible metadata honestly earns JSONB.

## Predict before reading

Predict the plan for a query that returns twenty `todo` issues from one workspace ordered by `created_at DESC`. Will PostgreSQL always use an index if one exists? If a filter matches most rows, why might a sequential scan be cheaper? If an index says `(workspace_id, status, created_at DESC)`, which filters can use its leading columns? Write an answer, then compare it with the plan instead of treating `EXPLAIN` as decorative output.

## Mental model

```text
application request → SQL shape → planner estimates → chosen plan → rows returned
                              ↑              ↓
                       table statistics   actual timing/rows

index: extra ordered/searchable structure
read benefit ↔ write maintenance + disk + planning complexity
```

The planner compares estimated work. A sequential scan reads the table; an index scan finds matching locations through an index and then may visit table pages. A bitmap scan collects many matching locations first. None is morally better. The useful plan is the one that answers the actual query cheaply on representative data. `EXPLAIN` reports estimates without executing. `EXPLAIN ANALYZE` executes and adds actual row counts and timing, which is why it is safe for a read query and requires transaction/rollback care for a write.

## 1. Make the workload representative

Seed deterministic data across several workspaces, projects, statuses, assignees, labels, and dates. Random-looking data without controlled distribution can hide the selectivity that the planner needs. Keep the seed command idempotent or resettable; do not hand-create a large data set that cannot be reproduced.

```sql
SELECT workspace_id, status, count(*)
FROM issues
GROUP BY workspace_id, status
ORDER BY workspace_id, status;
```

```sql
SELECT count(*) AS issues,
       count(DISTINCT workspace_id) AS workspaces,
       count(DISTINCT assignee_id) AS assignees
FROM issues;
```

```sql
ANALYZE issues;
SELECT relname, n_live_tup
FROM pg_stat_user_tables
WHERE relname = 'issues';
```

Statistics are estimates and can become stale after substantial change. `ANALYZE` refreshes statistics without rebuilding the table. Do not select a row count merely to force a particular plan: the dataset should make the product query meaningful, even if the supported laptop still finds it fast.

## 2. Capture a baseline plan

Start with parameter values representing a normal workspace, not a special empty or all-row case. Preserve the SQL and both plans in a project note. The query needs a deterministic ordering; pagination without a stable order lets rows drift between pages.

```sql
EXPLAIN
SELECT id, title, status, assignee_id, created_at
FROM issues
WHERE workspace_id = 7
  AND status = 'todo'
ORDER BY created_at DESC
LIMIT 20;
```

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, title, status, assignee_id, created_at
FROM issues
WHERE workspace_id = 7
  AND status = 'todo'
ORDER BY created_at DESC
LIMIT 20;
```

```text
Limit
  → Sort
      Sort Key: created_at DESC
      → Seq Scan on issues
          Filter: workspace_id = 7 AND status = 'todo'
```

Read from the bottom upward: the scan produces candidates, a filter removes rows, a sort orders survivors, and limit returns a small prefix. Compare `rows=` with `actual rows=` at each node. A large mismatch is evidence that the statistics or value distribution does not model reality; it is not automatic permission to add an index.

## 3. Design an index for the query shape

Equality conditions generally lead a multicolumn B-tree index; an ordering column can follow when it lets PostgreSQL avoid a separate sort for this exact query. The index is a hypothesis. Create it, analyze, rerun the same captured query, and record what changed.

```sql
CREATE INDEX issues_workspace_status_created_at_idx
ON issues (workspace_id, status, created_at DESC);
```

```sql
ANALYZE issues;
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, title, status, assignee_id, created_at
FROM issues
WHERE workspace_id = 7 AND status = 'todo'
ORDER BY created_at DESC
LIMIT 20;
```

```sql
SELECT indexrelname, idx_scan
FROM pg_stat_user_indexes
WHERE relname = 'issues';
```

The leftmost-prefix property matters: this index helps a `workspace_id` filter, and can narrow further by status; it is not a universal answer to `WHERE status = 'todo'` across all workspaces. A partial index may be justified when a stable predicate dominates, but only if it corresponds to a real query and data distribution. Every index slows inserts, updates to indexed values, vacuum work, and consumes disk.

## 4. Search is language-aware matching

`ILIKE '%bug%'` can be a valid small administrative query, but it is not PostgreSQL full-text search. FTS turns text into lexemes under a configuration, makes a query expression, ranks matching documents, and commonly uses GIN to avoid scanning every document. Select a configuration deliberately; do not rely on whatever a server happens to default to.

```sql
ALTER TABLE issues
ADD COLUMN search_document tsvector GENERATED ALWAYS AS (
  to_tsvector('english', coalesce(title, '') || ' ' || coalesce(description, ''))
) STORED;
```

```sql
CREATE INDEX issues_search_document_gin_idx
ON issues USING GIN (search_document);
```

```sql
SELECT id, title,
       ts_rank(search_document, websearch_to_tsquery('english', 'login timeout')) AS rank
FROM issues
WHERE workspace_id = 7
  AND search_document @@ websearch_to_tsquery('english', 'login timeout')
ORDER BY rank DESC, created_at DESC
LIMIT 20;
```

The workspace predicate remains an authorization and product boundary; FTS does not replace it. Verify punctuation, inflections, empty input, and a query that returns no rows. Ranking is a product decision, not a security boundary. Explain exactly which fields are searched, which language configuration is used, and why `%term%` is deliberately not the fallback called “search.”

## 5. Use JSONB only for flexible attributes

Issue title, status, project, assignee, labels, and workspace relationships deserve normal columns/tables because the application joins, constrains, and queries them. A small external integration payload whose keys genuinely vary can be JSONB. State the boundary first, then choose operators and an index only if queries need them.

```sql
ALTER TABLE issues
ADD COLUMN integration_metadata jsonb NOT NULL DEFAULT '{}'::jsonb;
```

```sql
UPDATE issues
SET integration_metadata = '{"source":"github","external_number":42}'::jsonb
WHERE id = 101;
```

```sql
CREATE INDEX issues_metadata_gin_idx
ON issues USING GIN (integration_metadata);
SELECT id FROM issues
WHERE integration_metadata @> '{"source":"github"}'::jsonb;
```

Do not put relational foreign keys, per-field validations, or frequently ordered product attributes into a JSON document because it feels flexible. JSONB makes some data variable; it does not make an unclear model clear. If your tracker has no honest flexible metadata need, keep this as a focused database exercise and do not alter the permanent schema.

## 6. Read estimates, buffers, and scan choices together

`cost=...` is the planner's relative model, not milliseconds. It combines estimates of I/O and CPU work; comparing it across alternatives is useful, treating it as a stopwatch is not. `actual time` and `Execution Time` come from a run of the query in that environment. `BUFFERS` adds whether nodes found blocks already cached (`shared hit`) or needed reads. A warm local cache can make two plans look similar even when one does much more work, which is why the node shape and row counts remain part of the evidence.

An Index Scan retrieves index entries then visits heap rows. An Index Only Scan can avoid many heap visits only when the selected columns are in the index and PostgreSQL's visibility map says pages are safe; it is an optimization that vacuum and update behavior influence. A Bitmap Index Scan commonly appears when many index matches are collected before heap pages are visited. A Seq Scan is often best when a predicate is not selective, because following many random index pointers costs more than reading a table sequentially. Do not turn off scan types to force a prettier demonstration: that changes the planner rather than improving the workload.

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, title, created_at
FROM issues
WHERE workspace_id = 7 AND status = 'todo'
ORDER BY created_at DESC
LIMIT 20;
```

```text
Index Scan using issues_workspace_status_created_at_idx on issues
  Index Cond: ((workspace_id = 7) AND (status = 'todo'))
  Buffers: shared hit=18 read=2
```

If an estimate is wrong by a large factor, ask whether values are correlated (for example, a workspace has mostly one status), whether statistics are stale, and whether the representative parameter is unusually common. More statistics or extended statistics may be a later, measured response; they are not the first answer to every mismatch. The learner-facing decision is precise: record what the plan says for this query and data, then retest after a meaningful data or schema change.

## 7. Pagination needs an order the index can serve

`OFFSET` pagination is approachable but makes PostgreSQL locate and discard earlier rows as the offset grows. That can be acceptable for small internal lists; it should be a conscious cost. A stable ordering also needs a tiebreaker: timestamps can collide, so `ORDER BY created_at DESC, id DESC` gives a total order. Cursor/keyset pagination uses the last row's ordering values to ask for the next slice and usually fits an index shaped around the scope, filters, and ordering.

```sql
CREATE INDEX issues_workspace_status_created_id_idx
ON issues (workspace_id, status, created_at DESC, id DESC);
```

```sql
SELECT id, title, created_at
FROM issues
WHERE workspace_id = 7
  AND status = 'todo'
  AND (created_at, id) < ('2026-08-15 10:00:00+00', 9001)
ORDER BY created_at DESC, id DESC
LIMIT 20;
```

```text
page cursor = last returned (created_at, id)
next request = values strictly after that cursor in the chosen order
```

Do not change the production list to cursor pagination just because it appears in this lesson. First decide what the product needs: direct page numbers, stable export traversal, or an infinite list. Whatever choice you keep must still be scoped to the workspace and tested around rows inserted between requests. Index design follows the retained query shape; it does not decide UX on its own.

## 8. Keep search documents current and inspect their semantics

A generated stored `tsvector` is a good fit when the document is derived only from columns in the same row. If search must include labels or comments from other tables, a generated column cannot magically follow those relations. Decide whether those belong in issue search at all; if they do, use an intentional update path or materialized/search design and document its refresh behavior. A trigger can be valid, but it adds write-path complexity that must be tested. Do not concatenate entire related tables into a value without measuring update and relevance costs.

Weights let a title matter more than a description, while `ts_rank_cd` can rank using coverage/density. They improve ordering, not matching authorization. `websearch_to_tsquery` is friendly for ordinary words and quoted phrases, but it still needs an explicit language configuration. Log or inspect the resulting query during development without exposing it as a SQL string to the browser.

```sql
SELECT setweight(to_tsvector('english', coalesce(title, '')), 'A') ||
       setweight(to_tsvector('english', coalesce(description, '')), 'B')
FROM issues WHERE id = 42;
```

```sql
SELECT ts_rank_cd(search_document,
                  websearch_to_tsquery('english', '"login timeout"')) AS rank;
```

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT id FROM issues
WHERE search_document @@ websearch_to_tsquery('english', 'login timeout');
```

Search input is still data: bind it as a parameter in application SQL rather than interpolating it. An FTS query parser is not an excuse to concatenate user input into SQL. Define a maximum query length and an empty-query behavior at the HTTP boundary, then verify the database behavior with normal and awkward terms.

## 9. Turn one plan into a durable operational decision

The plan capture belongs beside the migration because an index without its workload story is hard to maintain. Record the table size/date, query text, representative parameter values, PostgreSQL version, pre-change plan, post-change plan, and the observed reason for retaining the change. A short record is enough when it makes the next decision reversible: “this index serves the workspace todo list ordered by newest; it avoided a sort and reduced heap work for the measured distribution; reevaluate if status distribution or pagination changes.” Do not promise a fixed number of milliseconds across hardware, cache state, and production data.

Also name the invalidation conditions. Adding a second optional filter may create a different query shape. Changing `ORDER BY` from `created_at` to priority can make the old index irrelevant. Growing a table changes the relative costs. An index that is perfect for the interactive first page might not help a background export that reads all matching rows. Re-run the evidence when one of those application facts changes, not only when someone complains that the page feels slow.

```text
evidence record
  workload: workspace + status, newest first, first 20 rows
  data: seeded multi-workspace dataset, analyzed on PostgreSQL 17
  baseline: scan/filter/sort plan captured
  decision: multicolumn B-tree migration retained
  review trigger: query predicate/order or data distribution changes
```

Measure end-to-end behavior too. A faster database plan may still leave an API response slow because PHP serializes a large relationship graph or the browser requests more than it renders. Conversely, a browser delay is not a reason to optimize an unmeasured SQL query. Keep a request trace that identifies the SQL statement, then improve the narrowest demonstrated bottleneck. This prevents the database lesson from becoming a license for speculative tuning.

Finally, treat migrations as the deployable artifact. `CREATE INDEX` on a busy real database can have operational implications, and production migration choices may need an online/concurrent strategy appropriate to the project deployment process. Do not paste a production-only option into a transaction-managed migration without verifying its restrictions. The learner's local migration should be honest, repeatable, and reviewed; production rollout is a separate decision that starts from the same measured query evidence.

Plan evidence is time-bound, not eternal. Keep the captured output small enough to review, but retain enough context to reproduce it: database major version, seed revision, the result columns, and whether the run was warm or cold. When a query returns a radically different number of rows after a feature change, revisit the decision rather than assuming the old index remains correct. That discipline is the capability this lesson is building: PostgreSQL features remain tied to a named workload, observable evidence, and a reversible migration.

## Common mistakes

- Adding an index before capturing the SQL, parameters, and baseline plan.
- Calling an index “unused” because one small test selected a sequential scan.
- Reading only total execution time and ignoring estimated-versus-actual rows and buffer work.
- Running `EXPLAIN ANALYZE` on a destructive statement outside a transaction.
- Calling substring matching full-text search, or indexing every JSONB column by habit.

## When this goes wrong

If the plan does not change, first confirm that you are connected to the Compose database you seeded, the migration ran, and `ANALYZE` completed. Check the exact schema and index definition with `\d+ issues`. If it still chooses a sequential scan, that may be correct for the selected values; inspect selectivity rather than disabling planner options. If search returns no expected result, inspect `to_tsvector` and `websearch_to_tsquery` separately to find the configuration or tokenization mismatch. Roll back an experiment instead of deleting a useful production index blindly.

```sql
SELECT to_tsvector('english', 'Connection failures while logging in');
SELECT websearch_to_tsquery('english', 'login failure');
```

## Exercise

**Goal:** turn one measured issue-list request and one search request into documented, evidence-based database decisions.

**Starting state:** the Compose database has a reproducible representative seed and an issue list endpoint that filters by workspace and status.

**Requirements:** capture the exact list SQL and pre-index `EXPLAIN (ANALYZE, BUFFERS)` output; add only an index justified by its predicates/order; capture the post-index plan; add FTS across title and description with an explicit configuration and GIN; and write a short decision for JSONB that names a real flexible field or rejects JSONB.

**Verification:** show a peer or your future self the seed command, both plans, the migration, and three searches: a hit, no hit, and an inflection/punctuation case. Explain a plan node and one write cost of the index without reading this page.

**Mode: tool-run and manual-proof.** PostgreSQL and your project tests produce the evidence; the course does not pretend to grade your environment.

**Hints:** start with `EXPLAIN` if a query is unsafe to execute. Read plans bottom-up. Keep the same parameters before and after the index. Use `coalesce` when a nullable description enters a search vector.

## In the project

Commit migrations for the justified index and FTS schema, not a screenshot of a lucky plan. Put the captured query plan and data scale in the project documentation so the next maintainer knows what workload it serves. The API must continue to scope every search and list query to the authorized workspace; an index makes a query faster, never more authorized. B11 asks for representative data and evidence, while FS11.2 will make multi-step changes and tenant isolation safe under concurrent requests.

## Closed-book checkpoint

1. What is the difference between `EXPLAIN` and `EXPLAIN ANALYZE`?
2. Why might a sequential scan be the right plan even when an index exists?
3. For the list query above, why does column order in a multicolumn index matter?
4. What does a GIN index support in this lesson, and what does it not prove?
5. Name one field that should stay relational even if JSONB is available.

## Resources

- [PostgreSQL EXPLAIN](https://www.postgresql.org/docs/17/using-explain.html)
- [PostgreSQL indexes](https://www.postgresql.org/docs/17/indexes.html)
- [PostgreSQL full-text search](https://www.postgresql.org/docs/17/textsearch.html)
- [PostgreSQL JSON types](https://www.postgresql.org/docs/17/datatype-json.html)

Use the documentation version matching the image tag you actually pin. The links above correspond to the Part 10 Compose baseline, PostgreSQL 17.

## You are done when

You can reproduce the representative data, explain the before-and-after plan for one list query, defend the index's column order and write cost, and demonstrate FTS across title and description. You have either implemented a bounded, query-driven JSONB use or explicitly rejected it. You have not claimed that a plan from an empty table is performance evidence.

## Maintainer source record

Source dossier: `docs/dalt-fullstack/sources/POSTGRESQL_DOCS.md`.

Official sources: PostgreSQL 17 documentation for EXPLAIN, indexes, text search, JSON types, and statistics; URLs above.

Versions: learner environment is PostgreSQL 17 as pinned by Part 10 Compose material; update links and this record if that image major changes.

Consulted: 2026-08-15; repository Compose conventions and the authoritative Part 11 curriculum were checked before writing.

Curriculum authority: `docs/dalt-fullstack/CURRICULUM.md` §22, FS11.1; `PROJECT_BLUEPRINT.md` §§62–67.
