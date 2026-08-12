# Challenge: Missing JSONB Metadata

**Difficulty:** Easy · **Bugs:** 2 · **Lesson:** [13 — PostgreSQL Advanced](../../lessons/13-postgres-advanced/README.md)

## Prerequisites

This challenge runs against **PostgreSQL** and expects the `metadata` column you add during the Lesson 13 hands-on. Nothing here creates it, because `JSONB` has no SQLite equivalent and a migration for it would break `php artisan migrate` on the default driver.

Confirm it exists first:

```bash
docker compose exec db psql -U postgres -d dalt_php_app -c "\d posts"
```

If `metadata` is missing, add it:

```sql
ALTER TABLE posts ADD COLUMN IF NOT EXISTS metadata JSONB;
CREATE INDEX IF NOT EXISTS idx_posts_metadata ON posts USING GIN(metadata);
```

## Start

```bash
php artisan challenge:start db-missing-jsonb
php artisan serve
```

`php artisan challenge:stop` restores everything when you are done.

## Observe the symptom first

Create a post with metadata attached:

```bash
curl -s -X POST http://localhost:8000/posts \
  -d 'title=Hello&body=World&metadata={"tags":["docker"],"featured":true}'
```

It reports success. Now read the posts back:

```bash
curl -s http://localhost:8000/posts
```

The post is there. The metadata is not. Check the database directly:

```bash
docker compose exec db psql -U postgres -d dalt_php_app -c "SELECT id, title, metadata FROM posts;"
```

That last result tells you which of the two controllers is at fault — and whether it is one problem or two.

## Hints

<details>
<summary>Hint 1 — narrowing it down</summary>

The column is `NULL` in the database, not merely absent from the response. That means the write lost the data, so fixing the read alone will not help. Check whether the read would have shown it even if the data were there.
</details>

<details>
<summary>Hint 2 — the write</summary>

Look at the `INSERT` in `store.php`. A bound parameter only reaches the database if it appears in **both** the column list and the parameter array. Compare the columns named in the statement with the keys passed alongside it.
</details>

<details>
<summary>Hint 3 — the read</summary>

`index.php` selects an explicit column list rather than `*`. A column you never asked for cannot appear in the result.
</details>

<details>
<summary>Hint 4 — the type</summary>

`JSONB` accepts a JSON string and parses it on the way in. A request that omits metadata should store `NULL`, not the string `"null"` and not an empty string — Postgres will reject invalid JSON outright.
</details>

## Success criteria

- Posting with a `metadata` object stores it, and `psql` shows real JSONB, not `NULL`.
- `GET /posts` returns the metadata alongside each post.
- Posting without metadata still succeeds and stores `NULL`.
- The value is passed as a bound parameter.

## Verify

```bash
php artisan challenge:verify
```

Then confirm the round trip yourself with the two `curl` commands above.

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 13: PostgreSQL Advanced** — read this first
- **Next:** Lesson 14 — Docker Production Patterns
