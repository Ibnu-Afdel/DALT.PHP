# Challenge: Broken Full-Text Search

**Difficulty:** Medium · **Bugs:** 1 · **Lesson:** [13 — PostgreSQL Advanced](../../lessons/13-postgres-advanced/README.md)

## Prerequisites

This challenge runs against **PostgreSQL**, and it expects the schema you build during the Lesson 13 hands-on. Nothing here creates it for you, because a generated `TSVECTOR` column has no SQLite equivalent and would break `php artisan migrate` for anyone still on the default driver.

Before starting, confirm the column and index exist:

```bash
docker compose exec db psql -U postgres -d dalt_php_app -c "\d posts"
```

You should see a `search_vector` column of type `tsvector` and an index using GIN. If they are missing, run the Lesson 13 statements that create them:

```sql
ALTER TABLE posts ADD COLUMN IF NOT EXISTS search_vector TSVECTOR
  GENERATED ALWAYS AS (to_tsvector('english', title || ' ' || COALESCE(body, ''))) STORED;

CREATE INDEX IF NOT EXISTS idx_posts_search ON posts USING GIN(search_vector);
```

## Start

```bash
php artisan challenge:start db-broken-fts
php artisan serve
```

`php artisan challenge:stop` restores everything when you are done.

## Observe the symptoms first

`GET /posts/search?q=...` works, in the sense that it returns rows. Three things are wrong with *how*.

1. Search for a word that appears in the middle of a title. Fine.
2. Now search for a word with a different ending — `container` when the title says `containers`, or `simplify` when it says `simplifies`. Nothing comes back.
3. Search for a common word that appears in many posts. Note the order of the results, then ask what decides it.
4. Run the query plan:

   ```bash
   docker compose exec db psql -U postgres -d dalt_php_app \
     -c "EXPLAIN ANALYZE SELECT id FROM posts WHERE title ILIKE '%docker%';"
   ```

   Read the first line.

Write down what those four observations tell you before opening the controller.

## Hints

<details>
<summary>Hint 1 — where to look</summary>

`app/Http/controllers/posts/search.php`. The controller runs one query; everything wrong is in it. The column you need already exists on the table.
</details>

<details>
<summary>Hint 2 — why word endings fail</summary>

`ILIKE '%container%'` is substring matching on raw characters. It has no idea that `containers`, `contained`, and `container` share a root, and it will happily match the middle of an unrelated word. Postgres has a text-search type that normalizes words to their stems before comparing.
</details>

<details>
<summary>Hint 3 — why the plan says Seq Scan</summary>

A leading `%` wildcard makes a B-tree index useless, so the database reads every row. The GIN index on the table is built for a different operator than `ILIKE`.
</details>

<details>
<summary>Hint 4 — why the ordering is arbitrary</summary>

`ORDER BY created_at DESC` sorts by recency, which has nothing to do with how well a row matched. Postgres can score a match; use that score to sort.
</details>

<details>
<summary>Hint 5 — the pieces</summary>

The generated column holds a `tsvector`. Turn the user's input into a `tsquery` with `plainto_tsquery('english', :q)`, match the two with the `@@` operator, and sort by `ts_rank` of the same pair. Keep `:q` bound — a search term is still user input.
</details>

## Success criteria

- Searching `container` finds a post titled with `containers`.
- Results are ordered by relevance, not by date.
- The query plan uses the GIN index instead of a sequential scan.
- The search term is still passed as a bound parameter.
- An empty `q` still returns 400.

## Verify

```bash
php artisan challenge:verify
```

Then confirm the behavior yourself — re-run the `EXPLAIN ANALYZE` and check the scan type changed.

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 13: PostgreSQL Advanced** — read this first
- **Next challenge:** db-missing-jsonb
