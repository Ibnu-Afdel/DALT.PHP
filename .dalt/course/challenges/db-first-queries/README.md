# Challenge: Broken First Queries

**Difficulty:** Easy · **Bugs:** 3 · **Lesson:** [09 — PostgreSQL First Contact](../../lessons/09-postgres-first-contact/README.md)

## Start

```bash
php artisan challenge:start db-first-queries
php artisan migrate
php artisan serve
```

`php artisan challenge:stop` restores everything when you are done — never copy or delete these files by hand.

The `users` table exists but starts empty. Insert one row to have something real to query:

```bash
sqlite3 database/app.sqlite "INSERT INTO users (name, email, password) VALUES ('Alice', 'alice@example.com', 'x')"
```

(Running against Postgres instead? Use `psql` and the `INSERT ... RETURNING id` form from Lesson 09 to get the new row's id back.)

## Observe the symptoms first

Do this before opening either controller file. Use the id your insert above actually returned — the examples below assume it was `1`.

```bash
curl -i http://localhost:8000/users
curl -i -G http://localhost:8000/users --data-urlencode "search=' OR '1'='1"
curl -i http://localhost:8000/users/1        # the row you just inserted
curl -i http://localhost:8000/users/9999     # does not exist
```

(Use `-G --data-urlencode` as shown, not a hand-built `?search=...` string — an unescaped `'` and spaces in a raw URL get mangled by some shells and clients before the request ever reaches DALT, which looks exactly like the bug being "fixed" when it is not.)

Two different failures, and one surprise:

- The search term above is nonsense — it matches no real email — yet every user in the table comes back.
- `/users/1` **and** `/users/9999` both fail the same way: a `500` with a `PDOException` naming a column. That is not a coincidence — read the error message before opening any file. It already tells you which of the two bugs in `show.php` you are looking at, and it is blocking the second bug in that file from being observable at all until it is fixed.

(A leading character before the quote, e.g. `1' OR '1'='1`, does **not** reproduce the search symptom the same way — try it and compare. The bare `' OR '1'='1` is the one that matters.)

Write down what the error message tells you about where the submitted value ends up, and about what decides an HTTP status.

## Hints

Work down this list only as far as you need. Two files, three bugs: one in `Http/controllers/users/index.php`, two in `Http/controllers/users/show.php`. Neither `framework/Core/Database.php` nor `framework/Core/Response.php` needs changing.

<details>
<summary>Hint 1 — the search that matches everyone</summary>

Compare how `index.php` builds its `WHERE` clause for `search` against how `show.php` passes `:id` to the database. One of them assembles the SQL string in PHP, with the submitted value already inside it, before the database ever parses anything. Only a value passed the other way can never become part of the query itself.
</details>

<details>
<summary>Hint 2 — the column the error message already named</summary>

The `PDOException` from the "Observe" step names the exact column `show.php` queried. Compare it against the real column names on `users` — `\d users` in psql, or `database/migrations/001_create_users_table.sql`, settles it either way. This bug crashes the request before the `if (!$user)` check below it ever runs, which is why `/users/1` and `/users/9999` looked identical.
</details>

<details>
<summary>Hint 3 — the 404 that isn't one</summary>

Fix Hint 2's bug first — `/users/9999` will stop crashing and start reaching the not-found branch. Once it does, read the status line again, not just the body. `show.php` calls a PHP function to set the status, then returns an array. Re-read Lesson 02's response-boundary table: what actually decides the status the client receives when a controller returns a value instead of a `Response`?
</details>

<details>
<summary>Hint 4 — the concepts</summary>

- A bound parameter can only ever become a value; a string assembled with `.` or `"{$var}"` is exposed to the SQL parser before the database runs it, so quotes and operators inside the submitted value become part of the query's own syntax.
- A query naming a column that does not exist is not "close enough" — the database matches column names exactly, and a query against the wrong one raises an error before it ever gets to compare rows.
- `http_response_code()` sets a global PHP value that DALT's response boundary does not read. `Response::send()` sets the status from the `Response` object it was actually given. Setting one and returning the other means the one that reaches the client is never the one you set.
</details>

## Success criteria

- `/users` lists users.
- `?search=' OR '1'='1` returns no rows — only a genuine substring match should.
- `/users/{id}` finds a row that really exists.
- `/users/{id}` for a missing id returns a real `404` status, not `200` with an error body.
- No submitted value is concatenated into a SQL string.

## Verify

```bash
php artisan challenge:verify
```

Then confirm the behavior yourself — the checks are a completion signal, not proof:

```bash
curl -i -G http://localhost:8000/users --data-urlencode "search=' OR '1'='1"   # expect: no rows
curl -i http://localhost:8000/users/9999                                        # expect: a 404 status line
```

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 09: PostgreSQL First Contact** — read this first
- **Next:** Lesson 10 — PostgreSQL Core
