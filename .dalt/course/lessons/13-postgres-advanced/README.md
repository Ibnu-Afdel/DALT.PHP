# Lesson 13: PostgreSQL Advanced

## Beyond Basic Queries

You know JOINs, aggregations, indexes, and transactions. Now you're going to learn the features that separate a database used as a dumb key-value store from one that's doing real work.

This lesson covers four tools:

1. **Window functions** — run a calculation over a set of rows without collapsing them into one
2. **CTEs** (`WITH` clause) — name a subquery so the outer query stays readable
3. **JSONB** — store semi-structured data in a column and query inside it efficiently
4. **Full-text search** — find documents by meaning, not substring match, with index support

Each section ends with a DALT.PHP example so you can see how it maps to a controller.

## Learning Objectives

- Write `ROW_NUMBER()`, `LAG()`, and `RANK()` window functions
- Use CTEs to break a complex query into named steps
- Store and query data in a `JSONB` column
- Add a `tsvector` generated column for full-text search
- Index JSONB with GIN for fast containment queries
- Wire all of these into DALT controllers

---

## Window Functions

A window function runs a calculation across a set of rows that are *related to the current row*, without collapsing the result set the way `GROUP BY` does. You get one output row per input row.

Syntax:

```sql
function_name() OVER (
  PARTITION BY column   -- group rows by this (optional)
  ORDER BY column       -- order within each group
)
```

### `ROW_NUMBER()` — rank rows within a group

Give each post a sequential number within its author's posts, newest first:

```sql
SELECT
  posts.id,
  posts.title,
  posts.created_at,
  users.name AS author,
  ROW_NUMBER() OVER (
    PARTITION BY posts.user_id
    ORDER BY posts.created_at DESC
  ) AS post_rank
FROM posts
LEFT JOIN users ON posts.user_id = users.id
ORDER BY users.name, post_rank;
```

`PARTITION BY posts.user_id` resets the counter for each user. Without `PARTITION BY`, `ROW_NUMBER()` runs across the entire result set as one group.

### `LAG()` — get the previous row's value

Show each post's creation date and the date of the previous post by the same author:

```sql
SELECT
  id,
  title,
  created_at,
  LAG(created_at) OVER (
    PARTITION BY user_id
    ORDER BY created_at
  ) AS prev_post_at
FROM posts;
```

`LAG(col)` returns `NULL` for the first row in each partition (no previous row exists).

`LEAD(col)` is the inverse — it looks at the next row.

### `RANK()` vs `ROW_NUMBER()`

`ROW_NUMBER()` always assigns unique numbers (1, 2, 3, ...). `RANK()` assigns the same number to tied rows and then skips:

```sql
-- Posts scored by view count:
-- ROW_NUMBER:  1, 2, 3, 4
-- RANK:        1, 2, 2, 4  (two posts tied at 2nd place)
SELECT title, views, RANK() OVER (ORDER BY views DESC) AS rank FROM posts;
```

### Window functions in a DALT controller

```php
<?php

$db = \Core\App::resolve(\Core\Database::class);

$posts = $db->query(
    'SELECT
       posts.id,
       posts.title,
       posts.created_at,
       users.name AS author,
       ROW_NUMBER() OVER (
         PARTITION BY posts.user_id
         ORDER BY posts.created_at DESC
       ) AS post_rank
     FROM posts
     LEFT JOIN users ON posts.user_id = users.id
     ORDER BY users.name, post_rank'
)->get();

return $posts;
```

---

## CTEs — `WITH` Clause

A CTE (Common Table Expression) is a named, temporary result set that you can reference in the main query. It makes complex queries readable by breaking them into named steps.

### Find users who posted more than the average

Without a CTE, you'd nest a subquery inline. With a CTE:

```sql
WITH avg_post_count AS (
  SELECT AVG(post_count) AS avg
  FROM (
    SELECT COUNT(*) AS post_count
    FROM posts
    GROUP BY user_id
  ) counts
),
user_counts AS (
  SELECT
    user_id,
    COUNT(*) AS post_count
  FROM posts
  GROUP BY user_id
)
SELECT
  users.name,
  user_counts.post_count,
  avg_post_count.avg
FROM user_counts
JOIN users ON user_counts.user_id = users.id
CROSS JOIN avg_post_count
WHERE user_counts.post_count > avg_post_count.avg
ORDER BY user_counts.post_count DESC;
```

The `WITH` block defines `avg_post_count` and `user_counts` as named subqueries. The main query references them as if they were tables. This is far easier to read than nested inline subqueries.

### Recursive CTEs

CTEs can reference themselves — useful for tree structures (categories, comments). That's out of scope for this lesson, but know the pattern exists.

---

## JSONB

`JSONB` stores JSON as a parsed binary representation. Unlike `TEXT` storing a JSON string, `JSONB` can be indexed and queried efficiently.

### Defining a JSONB column

In a migration:

```sql
CREATE TABLE IF NOT EXISTS posts (
  id          BIGSERIAL PRIMARY KEY,
  title       TEXT NOT NULL,
  body        TEXT NOT NULL,
  metadata    JSONB,
  created_at  TIMESTAMPTZ DEFAULT NOW()
);
```

### Inserting JSONB from PHP

Pass the JSON string with `:metadata`. PHP's `json_encode` produces the right format:

```php
$db->query(
    'INSERT INTO posts (title, body, metadata) VALUES (:title, :body, :metadata)',
    [
        'title'    => 'Getting Started with Docker',
        'body'     => 'Docker isolates your application...',
        'metadata' => json_encode([
            'type'      => 'tutorial',
            'published' => true,
            'tags'      => ['docker', 'devops'],
        ]),
    ]
);
```

### Querying JSONB

**Extract a field as text** using `->>`  (returns text, not JSON):

```sql
SELECT title, metadata->>'type' AS post_type
FROM posts
WHERE metadata->>'type' = 'tutorial';
```

**Containment operator `@>`** — check if the JSONB value contains a given JSON fragment:

```sql
-- All published posts:
SELECT id, title FROM posts WHERE metadata @> '{"published": true}';

-- Posts tagged 'docker':
SELECT id, title FROM posts WHERE metadata @> '{"tags": ["docker"]}';
```

**Path navigation with `#>>`** for nested keys:

```sql
SELECT metadata #>> '{author,name}' AS author_name FROM posts;
```

### GIN index for JSONB

Without an index, `@>` does a sequential scan. Add a GIN index for fast containment queries:

```sql
CREATE INDEX IF NOT EXISTS idx_posts_metadata ON posts USING GIN(metadata);
```

GIN (Generalized Inverted Index) maps each JSON key/value to the rows that contain it — the same structure used for full-text search.

Run `EXPLAIN ANALYZE` before and after adding the index on a large dataset to see the difference between `Seq Scan` and `Bitmap Index Scan`.

### Updating JSONB fields

To update a single key without overwriting the entire object, use `jsonb_set`:

```sql
UPDATE posts
SET metadata = jsonb_set(metadata, '{published}', 'true')
WHERE id = :id;
```

---

## Full-Text Search

`ILIKE '%keyword%'` is the beginner approach to search. It works for small tables. On a table with 100,000 rows, it does a sequential scan on every character of every row. It can't use a B-tree index. It's slow.

Full-text search in Postgres converts text into a `tsvector` (a sorted list of normalized lexemes), then matches it against a `tsquery` using the `@@` operator. GIN indexes on `tsvector` columns make this fast.

### `tsvector` and `tsquery`

A `tsvector` is a processed version of text:

```sql
SELECT to_tsvector('english', 'Docker simplifies container management');
-- 'container':3 'docker':1 'manag':4 'simplifi':2
```

Postgres normalizes words (stems them), strips stop words, and tracks positions. A `tsquery` is the search term, also normalized:

```sql
SELECT plainto_tsquery('english', 'docker containers');
-- 'docker' & 'contain'
```

The `@@` operator checks if a `tsquery` matches a `tsvector`:

```sql
SELECT id, title
FROM posts
WHERE to_tsvector('english', title || ' ' || body) @@ plainto_tsquery('english', 'docker');
```

This still requires computing the `tsvector` at query time. Better to store it.

### Generated `tsvector` column

Add a `search_vector` column that Postgres maintains automatically:

```sql
ALTER TABLE posts ADD COLUMN search_vector TSVECTOR
  GENERATED ALWAYS AS (
    to_tsvector('english', title || ' ' || COALESCE(body, ''))
  ) STORED;
```

`GENERATED ALWAYS AS (...) STORED` means the column is computed from other columns and physically stored. Postgres updates it automatically on every INSERT and UPDATE.

Now the search query is simple:

```sql
SELECT id, title, created_at
FROM posts
WHERE search_vector @@ plainto_tsquery('english', :q)
ORDER BY ts_rank(search_vector, plainto_tsquery('english', :q)) DESC;
```

`ts_rank` scores matches by frequency and position — put it in `ORDER BY` to return the most relevant results first.

### GIN index on the generated column

```sql
CREATE INDEX IF NOT EXISTS idx_posts_search ON posts USING GIN(search_vector);
```

Run `EXPLAIN ANALYZE` on the search query before and after adding the index:

```sql
EXPLAIN ANALYZE
SELECT id, title FROM posts
WHERE search_vector @@ plainto_tsquery('english', 'docker');
```

Before: `Seq Scan on posts`. After: `Bitmap Index Scan on idx_posts_search`.

### Full-text search in a DALT controller

```php
<?php

$db = \Core\App::resolve(\Core\Database::class);

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    return Response::json(['error' => 'Query parameter q is required'], 400);
}

$posts = $db->query(
    "SELECT id, title, created_at,
            ts_rank(search_vector, plainto_tsquery('english', :q)) AS relevance
     FROM posts
     WHERE search_vector @@ plainto_tsquery('english', :q)
     ORDER BY relevance DESC
     LIMIT 20",
    ['q' => $q]
)->get();

return ['query' => $q, 'results' => $posts];
```

Note: `:q` is used twice — PDO's named parameters allow this as long as the same value is intended.

---

## `EXPLAIN ANALYZE` Before/After GIN

Here's what you'll see adding a GIN index on `search_vector`:

**Before index:**
```
Seq Scan on posts  (cost=0.00..2345.00 rows=50 width=40)
  Filter: (search_vector @@ plainto_tsquery('english', 'docker'))
  Rows Removed by Filter: 99950
  Planning Time: 0.4ms
  Execution Time: 48.2ms
```

**After GIN index:**
```
Bitmap Heap Scan on posts  (cost=12.50..89.20 rows=50 width=40)
  Recheck Cond: (search_vector @@ plainto_tsquery('english', 'docker'))
  ->  Bitmap Index Scan on idx_posts_search
      Index Cond: (search_vector @@ plainto_tsquery('english', 'docker'))
  Planning Time: 0.3ms
  Execution Time: 0.8ms
```

48ms → 0.8ms. That's the difference between an index scan and touching 100,000 rows.

---

## Summary

| Feature | What it solves | Key syntax |
|---|---|---|
| Window functions | Rank/compare rows without GROUP BY collapse | `ROW_NUMBER() OVER (PARTITION BY ... ORDER BY ...)` |
| CTEs | Make complex queries readable | `WITH name AS (subquery) SELECT ... FROM name` |
| JSONB | Semi-structured data in one column | `metadata @> '{"key": "val"}'`, `metadata->>'key'` |
| GIN index | Fast JSONB and full-text queries | `CREATE INDEX ... USING GIN(column)` |
| Full-text search | Relevance-ranked search with index support | `search_vector @@ plainto_tsquery('english', :q)` |
| Generated column | Auto-maintained computed value | `GENERATED ALWAYS AS (...) STORED` |

---

## Your Task

Load the broken full-text search controller:

```bash
php artisan challenge:start db-broken-fts
```

The `GET /posts/search?q=term` endpoint uses `WHERE title ILIKE :q` with `'%' . $q . '%'`. Your job:

1. Replace `ILIKE` with a `tsvector` match using `search_vector @@ plainto_tsquery('english', :q)`
2. The `posts` table already has a `search_vector` column (it was added in an earlier migration)
3. Remove the `ILIKE` and the `%` concatenation entirely

Verify:

```bash
php artisan challenge:verify
```

---

## End-of-Phase Project: URL Shortener API

You have all the tools. Now build something real.

### What you're building

A URL shortener API with click tracking and expiry. No framework magic — just SQL, PHP controllers, and DALT's routing.

### Schema design

Write this migration yourself in `database/migrations/003_url_shortener.sql`:

```sql
CREATE TABLE IF NOT EXISTS urls (
  id           BIGSERIAL PRIMARY KEY,
  short_code   TEXT NOT NULL UNIQUE,
  original_url TEXT NOT NULL,
  expires_at   TIMESTAMPTZ,
  created_at   TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS clicks (
  id          BIGSERIAL PRIMARY KEY,
  url_id      BIGINT NOT NULL REFERENCES urls(id) ON DELETE CASCADE,
  ip_address  TEXT,
  clicked_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_urls_short_code ON urls(short_code);
CREATE INDEX IF NOT EXISTS idx_clicks_url_id ON clicks(url_id);
CREATE INDEX IF NOT EXISTS idx_clicks_clicked_at ON clicks(clicked_at);
```

Why those indexes:
- `urls(short_code)` — every redirect lookup hits this column; it must be fast
- `clicks(url_id)` — stats queries JOIN or filter on `url_id`
- `clicks(clicked_at)` — grouping clicks by day uses this column

### Routes

Register these in `routes/routes.php`:

```php
$router->post('/shorten',          'urls/store.php');
$router->get('/{code}',            'urls/redirect.php');
$router->get('/stats/{code}',      'urls/stats.php');
$router->delete('/shorten/{code}', 'urls/destroy.php');
```

### Step 1: `POST /shorten`

Accept `original_url` (required) and `expires_at` (optional ISO 8601 datetime) from `$_POST`.

Generate a short code:

```php
// Retry loop in case of UNIQUE collision (extremely rare with 8 chars)
do {
    $code = substr(base64_encode(random_bytes(6)), 0, 8);
    // Make it URL-safe:
    $code = strtr($code, '+/', '-_');

    $existing = $db->query(
        'SELECT id FROM urls WHERE short_code = :code',
        ['code' => $code]
    )->find();
} while ($existing);

$db->query(
    'INSERT INTO urls (short_code, original_url, expires_at) VALUES (:code, :url, :expires)',
    [
        'code'    => $code,
        'url'     => $_POST['original_url'],
        'expires' => $_POST['expires_at'] ?? null,
    ]
);

return Response::json(['short_code' => $code, 'url' => "http://localhost:8080/{$code}"], 201);
```

### Step 2: `GET /{code}` — redirect

Look up the short code. Check expiry. Log the click. Redirect.

```php
$url = $db->query(
    'SELECT id, original_url, expires_at FROM urls WHERE short_code = :code',
    ['code' => \Core\App::resolve(\Core\Request::class)->route('code')]
)->find();

if (!$url) {
    return Response::text('Not found', 404);
}

// Check expiry
if ($url['expires_at'] !== null && strtotime($url['expires_at']) < time()) {
    return Response::text('Link expired', 410);
}

// Log the click (fire and forget — don't let logging failures block the redirect)
try {
    $db->query(
        'INSERT INTO clicks (url_id, ip_address) VALUES (:url_id, :ip)',
        ['url_id' => $url['id'], 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]
    );
} catch (\Exception $e) {
    // Log failed — don't crash the redirect
}

header('Location: ' . $url['original_url'], true, 302);
exit;
```

### Step 3: `GET /stats/{code}`

Return total clicks, unique IPs, and clicks grouped by day:

```php
$url = $db->query(
    'SELECT id, short_code, original_url, expires_at, created_at FROM urls WHERE short_code = :code',
    ['code' => \Core\App::resolve(\Core\Request::class)->route('code')]
)->findOrFail();

$totals = $db->query(
    'SELECT COUNT(*) AS total, COUNT(DISTINCT ip_address) AS unique_visitors
     FROM clicks WHERE url_id = :id',
    ['id' => $url['id']]
)->find();

$byDay = $db->query(
    "SELECT DATE(clicked_at) AS day, COUNT(*) AS clicks
     FROM clicks
     WHERE url_id = :id
     GROUP BY DATE(clicked_at)
     ORDER BY day DESC
     LIMIT 30",
    ['id' => $url['id']]
)->get();

return [
    'url'              => $url,
    'total_clicks'     => (int)$totals['total'],
    'unique_visitors'  => (int)$totals['unique_visitors'],
    'clicks_by_day'    => $byDay,
];
```

### Step 4: `DELETE /shorten/{code}`

```php
$affected = $db->query(
    'DELETE FROM urls WHERE short_code = :code',
    ['code' => \Core\App::resolve(\Core\Request::class)->route('code')]
);

// clicks are deleted by CASCADE

return ['deleted' => true];
```

### Success criteria

You're done when:
- `POST /shorten` with a URL returns a JSON response with `short_code`
- `GET /{code}` redirects to the original URL with a 302
- `GET /{code}` on an expired URL returns 410
- `GET /stats/{code}` returns `total_clicks`, `unique_visitors`, and `clicks_by_day`
- `DELETE /shorten/{code}` removes the URL (clicks cascade-delete automatically)
- Running `EXPLAIN ANALYZE` on the redirect lookup shows an Index Scan on `idx_urls_short_code`

### Bonus

Add a `JSONB` column `metadata` to `urls` so callers can attach arbitrary data to a link (campaign name, source, etc.):

```sql
ALTER TABLE urls ADD COLUMN metadata JSONB;
```

Accept it in `POST /shorten` and return it in `GET /stats/{code}`.

## Next Steps

- **Challenge: db-broken-fts** — fix ILIKE to use proper full-text search
- **Challenge: db-missing-jsonb** — add JSONB metadata to the posts controller
- **Phase 5: Production Patterns** — secrets, health checks, PgBouncer, migration strategies
