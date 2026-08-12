# Lesson 10: PostgreSQL Core

## The Gap Between Hello World and Production

In the last lesson you connected to Postgres, ran basic DDL and DML, and understood why parameterized queries are non-negotiable. That got you able to write working SQL.

This lesson covers the features you'll reach for every day once your app has real data: JOINs to combine tables, aggregations to summarize data, indexes to keep queries fast, constraints to keep data clean, and transactions to keep multi-step writes atomic.

By the end you'll be able to look at a slow query and know what to do about it.

## Learning Objectives

- Write `INNER JOIN` and `LEFT JOIN` with real examples
- Use `GROUP BY`, `HAVING`, `COUNT`, `SUM`, `AVG`
- Create indexes and read `EXPLAIN ANALYZE` output
- Enforce data integrity with constraints (`NOT NULL`, `UNIQUE`, `CHECK`, `FOREIGN KEY`)
- Wrap multi-step writes in transactions with proper `ROLLBACK` handling

---

## JOINs — Combining Tables

A JOIN combines rows from two tables based on a matching condition. There are two you'll use constantly.

### The tables we'll work with

DALT's users table plus a posts table you'll add:

```sql
-- users: id, name, email, password, created_at
-- posts: id, user_id, title, body, created_at
```

The relationship: each post has a `user_id` that points to `users.id`.

### INNER JOIN — only matching rows

```sql
SELECT posts.id, posts.title, users.name AS author
FROM posts
INNER JOIN users ON posts.user_id = users.id
ORDER BY posts.created_at DESC;
```

`ON posts.user_id = users.id` — this is the join condition. It says: match each post to the user whose `id` equals the post's `user_id`.

**What INNER JOIN does**: returns only rows where there is a match on both sides. If a user has no posts, they don't appear. If a post has no matching user (orphaned row), it doesn't appear either.

### LEFT JOIN — all left rows, NULL if no right match

```sql
SELECT users.name, COUNT(posts.id) AS post_count
FROM users
LEFT JOIN posts ON posts.user_id = users.id
GROUP BY users.id, users.name
ORDER BY post_count DESC;
```

**What LEFT JOIN does**: returns every row from the left table (`users`), even if there's no matching row in the right table (`posts`). When there's no match, the right-side columns are `NULL`.

This is the query to use when you want "all users, and how many posts each has" — including users with zero posts. `COUNT(posts.id)` counts only non-NULL values, so users with no posts get `0`.

### When to use which

| You want | Use |
|---|---|
| Only rows with data on both sides | `INNER JOIN` |
| All rows from left, with optional right data | `LEFT JOIN` |
| Posts with their author (every post has an author) | `INNER JOIN` |
| Users with their post count (some users have 0 posts) | `LEFT JOIN` |

### Common JOIN mistakes

**Wrong column in ON clause** — the single most common JOIN bug:
```sql
-- WRONG: this joins on the wrong columns
INNER JOIN users ON posts.id = users.id

-- RIGHT: the post's user_id links to users.id
INNER JOIN users ON posts.user_id = users.id
```

**Using INNER when LEFT was needed** — your list silently drops rows:
```sql
-- If users with no posts should appear, INNER JOIN drops them silently
INNER JOIN posts ON posts.user_id = users.id  -- wrong for "all users + post count"
LEFT JOIN posts ON posts.user_id = users.id   -- correct
```

---

## Aggregations — Summarizing Data

Aggregation functions collapse many rows into one number.

```sql
-- Count all rows
SELECT COUNT(*) AS total FROM users;

-- Count non-NULL values in a specific column
SELECT COUNT(posts.id) AS post_count FROM posts;

-- Count distinct values
SELECT COUNT(DISTINCT user_id) AS active_authors FROM posts;

-- Sum, average, min, max
SELECT
    SUM(amount) AS total_revenue,
    AVG(amount) AS avg_order,
    MIN(amount) AS smallest_order,
    MAX(amount) AS largest_order
FROM orders;
```

### GROUP BY — aggregate per group

`GROUP BY` splits rows into groups before aggregating. One output row per group.

```sql
-- Post count per user
SELECT user_id, COUNT(*) AS post_count
FROM posts
GROUP BY user_id
ORDER BY post_count DESC;
```

When you `GROUP BY`, every column in `SELECT` must either be in the `GROUP BY` or wrapped in an aggregation function. This is the rule:

```sql
-- WRONG: name is neither aggregated nor grouped
SELECT user_id, name, COUNT(*) AS post_count
FROM posts
GROUP BY user_id;

-- RIGHT: join users and group by both id and name
SELECT users.id, users.name, COUNT(posts.id) AS post_count
FROM users
LEFT JOIN posts ON posts.user_id = users.id
GROUP BY users.id, users.name
ORDER BY post_count DESC;
```

### HAVING — filter after aggregation

`WHERE` filters rows before grouping. `HAVING` filters groups after aggregation.

```sql
-- Only users with more than 5 posts
SELECT users.name, COUNT(posts.id) AS post_count
FROM users
LEFT JOIN posts ON posts.user_id = users.id
GROUP BY users.id, users.name
HAVING COUNT(posts.id) > 5
ORDER BY post_count DESC;
```

Use `WHERE` for row-level conditions, `HAVING` for aggregate conditions.

---

## Indexes — Keeping Queries Fast

An index is a data structure that lets Postgres find rows without scanning the whole table. Without one, every query that filters by a column scans every row — called a **sequential scan**. On a small table this is fine. On a million-row table it kills performance.

### When to add an index

Add an index when you repeatedly query or sort by a column:

```sql
-- You run this query often:
SELECT * FROM posts WHERE user_id = 42;

-- Add an index on posts.user_id:
CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);
```

```sql
-- Searching users by email is common:
SELECT * FROM users WHERE email = 'alice@example.com';

-- Index on email:
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
```

### EXPLAIN ANALYZE — reading the query plan

`EXPLAIN ANALYZE` shows you what Postgres actually does to execute a query, plus how long it took.

```sql
EXPLAIN ANALYZE
SELECT * FROM posts WHERE user_id = 42;
```

**Before an index** you'll see something like:
```
Seq Scan on posts  (cost=0.00..24.00 rows=5 width=68) (actual time=0.012..0.089 rows=5 loops=1)
  Filter: (user_id = 42)
  Rows Removed by Filter: 495
```

`Seq Scan` = sequential scan = reads every row. `Rows Removed by Filter: 495` = 495 rows were read and discarded to find 5 matching rows.

**After adding the index**:
```
Index Scan using idx_posts_user_id on posts  (cost=0.15..8.17 rows=5 width=68) (actual time=0.013..0.017 rows=5 loops=1)
  Index Cond: (user_id = 42)
```

`Index Scan` = Postgres used the index to jump directly to matching rows. Much faster on large tables.

### Index tradeoffs

Indexes speed up reads, but slow down writes slightly (because the index must be updated too). Don't index every column — only columns you filter or sort by frequently. Primary keys and foreign keys should almost always be indexed.

---

## Constraints — Keeping Data Clean

Constraints enforce rules at the database level. They're cheaper than application-level validation because the database enforces them even if your PHP code has a bug.

### NOT NULL

```sql
CREATE TABLE posts (
    id BIGSERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,   -- must always have an author
    title TEXT NOT NULL,         -- no empty titles
    body TEXT,                   -- body can be NULL (draft posts)
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

### UNIQUE

```sql
-- Only one row per email
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE
);

-- Or as a separate constraint (useful for composite uniqueness):
CREATE UNIQUE INDEX ON users(email);
```

### CHECK

```sql
CREATE TABLE orders (
    id BIGSERIAL PRIMARY KEY,
    amount NUMERIC NOT NULL CHECK (amount > 0),
    status TEXT NOT NULL CHECK (status IN ('pending', 'paid', 'cancelled'))
);
```

A `CHECK` constraint rejects any row that doesn't satisfy the condition.

### FOREIGN KEY with cascade

```sql
CREATE TABLE posts (
    id BIGSERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title TEXT NOT NULL
);
```

`REFERENCES users(id)` — the `user_id` column must point to a real `users.id`. You can't insert a post with a `user_id` that doesn't exist.

`ON DELETE CASCADE` — when a user is deleted, all their posts are deleted automatically. Without this, trying to delete a user with posts would throw an error.

---

## Transactions — Atomic Multi-Step Writes

A transaction groups multiple SQL statements so they either all succeed or all fail together. Without transactions, a crash between two related writes leaves your data in a corrupt state.

### The classic example: transferring credits

```sql
BEGIN;

UPDATE users SET credits = credits - 100 WHERE id = 1;  -- deduct from sender
UPDATE users SET credits = credits + 100 WHERE id = 2;  -- add to receiver

COMMIT;
```

If anything fails between `BEGIN` and `COMMIT`, call `ROLLBACK` instead. The database resets to its state before `BEGIN` — as if neither UPDATE happened.

```sql
BEGIN;

UPDATE users SET credits = credits - 100 WHERE id = 1;
-- imagine an error happens here...

ROLLBACK;  -- both updates are undone
```

### The bug that corrupts data

The most dangerous pattern: committing the first write before checking if the second one succeeds.

```sql
-- DANGEROUS: no transaction
UPDATE users SET credits = credits - 100 WHERE id = 1;  -- committed immediately
-- server crashes here
UPDATE users SET credits = credits + 100 WHERE id = 2;  -- never runs
-- result: 100 credits vanished
```

Without `BEGIN`, each statement auto-commits. The first deduction is permanent even if the second fails.

**Be precise about which failure this is.** Once you *have* opened a transaction, an error partway through does not corrupt anything: nothing before `commit()` is durable, and a connection that closes with a transaction still open has it discarded by the driver. Data loss is the no-transaction case above.

That is why the reason to write `rollBack()` explicitly is not "otherwise the money disappears". It is that you want a controlled error response instead of an uncaught exception, you want row locks released now rather than at teardown, and you do not want to depend on cleanup you did not write — implicit rollback is driver behavior, and it stops applying the moment a connection is pooled or something catches the error and carries on inside a poisoned transaction.

### In PHP with PDO

```php
$pdo = $db->getConnection();

try {
    $pdo->beginTransaction();

    $db->query(
        'UPDATE users SET credits = credits - :amount WHERE id = :id',
        ['amount' => $amount, 'id' => $fromId]
    );

    $db->query(
        'UPDATE users SET credits = credits + :amount WHERE id = :id',
        ['amount' => $amount, 'id' => $toId]
    );

    $pdo->commit();

    return ['success' => true];
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    return \Core\Response::json(['error' => 'Transfer failed'], 500);
}
```

Four details in that `catch`:

- **`\Throwable`, not `\Exception`** — a `TypeError` inside the block must roll back too.
- **`inTransaction()` guard** — `rollBack()` throws `PDOException: There is no active transaction` when none is open, which is exactly the state you are in if `beginTransaction()` itself failed. Unguarded, your error handler throws a second exception that hides the first.
- **Return a `Response`, do not print** — `http_response_code()` is overwritten by `Response::send()`, and `exit` abandons the outward middleware path. Lesson 11 covers this boundary.
- **The rollback is explicit** — not because the data would otherwise be lost, but for the reasons given above: a controlled response, locks released promptly, and no dependence on driver teardown behavior.

### Isolation levels (conceptual)

Postgres supports multiple isolation levels that control what concurrent transactions can see. The default is `READ COMMITTED` — each statement sees only committed data from other transactions. That's correct for 99% of use cases.

`SERIALIZABLE` makes transactions behave as if they ran one at a time (no interleaving), which prevents subtle race conditions at the cost of performance. You'll encounter this if you build a ledger or inventory system. For now, the default `READ COMMITTED` is what you need.

---

## Hands-On: JOINs, Aggregations, and EXPLAIN

Connect to your Compose Postgres container:

```bash
docker compose exec db psql -U postgres -d dalt_php_app
```

First, create a posts table (if it doesn't exist yet):

```sql
CREATE TABLE IF NOT EXISTS posts (
    id BIGSERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    body TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

Insert some test data (use real user ids from your users table):

```sql
-- List your users first
SELECT id, name FROM users;

-- Insert posts for the first two users
INSERT INTO posts (user_id, title, body) VALUES
    (1, 'First post', 'Hello world'),
    (1, 'Second post', 'More content'),
    (2, 'Another user post', 'Their content');
```

Now run these queries and observe the results:

```sql
-- All users with post count (LEFT JOIN includes users with 0 posts)
SELECT users.name, COUNT(posts.id) AS post_count
FROM users
LEFT JOIN posts ON posts.user_id = users.id
GROUP BY users.id, users.name
ORDER BY post_count DESC;

-- Only users who have posts (INNER JOIN)
SELECT users.name, COUNT(posts.id) AS post_count
FROM users
INNER JOIN posts ON posts.user_id = users.id
GROUP BY users.id, users.name
ORDER BY post_count DESC;

-- Posts with author name
SELECT posts.title, users.name AS author, posts.created_at
FROM posts
INNER JOIN users ON posts.user_id = users.id
ORDER BY posts.created_at DESC;
```

Check the query plan before adding an index:

```sql
EXPLAIN ANALYZE
SELECT * FROM posts WHERE user_id = 1;
```

Add the index:

```sql
CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);
```

Run `EXPLAIN ANALYZE` again and compare. On a small dataset the difference is subtle — the real impact shows up at scale.

---

## Summary

- `INNER JOIN` — matching rows only; `LEFT JOIN` — all left rows, NULL on no match
- The ON clause must reference the correct foreign key: `posts.user_id = users.id`, not `posts.id = users.id`
- `GROUP BY` collapses rows into groups; every non-aggregated column must be grouped
- `HAVING` filters after aggregation; `WHERE` filters before
- Indexes speed up reads on large tables — use `EXPLAIN ANALYZE` to confirm they're being used
- `NOT NULL`, `UNIQUE`, `CHECK`, `FOREIGN KEY` enforce integrity at the DB level
- Transactions: `BEGIN` + `COMMIT` for success, `ROLLBACK` in the `catch` block for failure

## Next Steps

- **Lesson 11: DALT Database Layer** — wire these queries into DALT controllers, add pagination, handle transactions from PHP
- **Challenge: db-broken-join** — fix a JOIN query that uses the wrong join type and wrong ON clause
- **Challenge: db-broken-transaction** — add `ROLLBACK` to a transfer endpoint that currently corrupts data on failure
