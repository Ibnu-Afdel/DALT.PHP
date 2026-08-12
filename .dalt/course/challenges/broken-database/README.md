# Challenge: Broken Database

**Difficulty:** Medium · **Bugs:** 2 · **Lesson:** [05 — Database](../../lessons/05-database/README.md)

## Start

```bash
php artisan challenge:start broken-database
php artisan migrate
php artisan serve
```

`php artisan challenge:stop` restores everything when you are done — never copy or delete these files by hand.

## Observe the symptoms first

Two separate failures live in this challenge, and they pull in opposite directions: one query refuses to find anything, the other finds far too much.

**Symptom A — a row that exists cannot be found.**

Visit `/posts/1`. The post is in the database and the controller binds `:id` correctly, yet the page reports no post. Notice there is no error and no exception; the query simply returns nothing.

**Symptom B — search returns rows it should not.**

Visit `/posts` and search for something ordinary, then try:

```
/posts?search=1' OR '1'='1
```

Every post comes back regardless of its title. Try `/posts?search='` on its own and read what happens.

Before opening any file, write down what each symptom implies about where the submitted value ends up.

## Hints

Work down this list only as far as you need.

<details>
<summary>Hint 1 — where each bug lives</summary>

Symptom A is in `framework/Core/Database.php`. Symptom B is in `app/Http/controllers/posts/index.php`. They are independent; fix them one at a time.
</details>

<details>
<summary>Hint 2 — narrowing symptom A</summary>

Read `Database::query()` line by line. The statement is prepared with a named placeholder, and then executed. Compare what `query()` was *given* with what it actually hands to the database. Then ask why this fails silently instead of raising an error — what does the driver do with a placeholder nobody supplied?

Check what a write does too: insert a row through a bound parameter and read the column back.
</details>

<details>
<summary>Hint 3 — narrowing symptom B</summary>

Look at how the search term reaches the SQL string in `posts/index.php`, and compare it with how `/posts/{id}` passes its value. One builds a string; the other passes data. Only one of those can be parsed as SQL.
</details>

<details>
<summary>Hint 4 — the concepts</summary>

- A prepared statement carries placeholders *and* values. Sending only the statement leaves the placeholders unfilled, and SQLite treats an unsupplied parameter as `NULL` rather than as an error — so writes store nothing and comparisons never match, with no exception to point at.
- Concatenating user input into SQL means the value is part of the statement before the database ever parses it, so quotes and operators inside it become syntax. Passing it as a bound parameter means the statement is parsed first and the value can only ever be data. `LIKE` wildcards belong in the bound value, not in the SQL.
</details>

## Success criteria

- `/posts/1` shows the post.
- A row inserted through a bound parameter stores the real value, not `NULL`.
- Searching for an ordinary word returns only matching posts.
- `?search=1' OR '1'='1` returns no matches instead of every post.
- No user input is concatenated into a SQL string.

## Verify

```bash
php artisan challenge:verify
```

Then confirm the behavior yourself — the checks are a completion signal, not proof:

```bash
curl -s "http://localhost:8000/posts?search=1'%20OR%20'1'='1" | grep -c '<div class="post">'
```

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 05: Database** — read this first
- **Next:** Lesson 06 — Docker Basics
