# Challenge: Slow Queries

**Difficulty:** Easy · **Bugs:** 2 · **Lesson:** [17 — Observability](../../lessons/17-observability/README.md)

## Prerequisites

Run this against **PostgreSQL**, with a `status` column on `posts` and enough rows that a sequential scan is measurably slower than an index scan. On a table of ten rows every plan looks fast.

```sql
ALTER TABLE posts ADD COLUMN IF NOT EXISTS status TEXT NOT NULL DEFAULT 'draft';

INSERT INTO posts (user_id, title, body, status)
SELECT 1, 'post ' || g, 'body', CASE WHEN g % 3 = 0 THEN 'published' ELSE 'draft' END
FROM generate_series(1, 50000) AS g;

ANALYZE posts;
```

## Start

```bash
php artisan challenge:start db-slow-queries
php artisan serve
```

`php artisan challenge:stop` restores everything when you are done.

## Measure before you change anything

Do not guess. Ask the database what it is doing:

```bash
docker compose exec db psql -U postgres -d dalt_php_app -c \
  "EXPLAIN ANALYZE SELECT * FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 50;"

docker compose exec db psql -U postgres -d dalt_php_app -c \
  "EXPLAIN ANALYZE SELECT * FROM posts WHERE user_id = 1 ORDER BY created_at DESC;"
```

Write down, for each: the scan type on the first line, the reported execution time, and the `Rows Removed by Filter` count. Those three numbers are your baseline, and you will compare against them at the end.

## Hints

<details>
<summary>Hint 1 — read the plan, not the code</summary>

`Seq Scan` means the database read every row and discarded the ones that did not match. `Rows Removed by Filter` tells you how much of that work was wasted.
</details>

<details>
<summary>Hint 2 — what to index</summary>

Each controller filters on one column. Those are the columns doing the discarding. `004_add_indexes.sql` is where the change belongs, so it is repeatable rather than typed into psql once.
</details>

<details>
<summary>Hint 3 — the cost you are accepting</summary>

An index is not free: it consumes storage and every write must update it. That is the trade you are making deliberately, which is why you index the columns you filter on rather than all of them.
</details>

## Success criteria

- `004_add_indexes.sql` creates an index for each filtered column.
- `php artisan migrate` applies cleanly.
- Both plans change from `Seq Scan` to an index scan.
- Execution time drops measurably against the baseline you wrote down.

## Verify

```bash
php artisan migrate
php artisan challenge:verify
```

The checks read `pg_indexes` — Postgres's own catalog of what indexes actually exist — after your migration has run, so they can tell a real index apart from a comment or a `CREATE INDEX` statement on the wrong column. They still can't tell you the queries got *faster*: re-run both `EXPLAIN ANALYZE` commands and compare with your baseline.

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 17: Observability** — read this first
