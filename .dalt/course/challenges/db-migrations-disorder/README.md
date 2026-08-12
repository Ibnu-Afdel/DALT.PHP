# Challenge: Migrations Out of Order

**Difficulty:** Easy · **Bugs:** 2 · **Lesson:** [15 — PostgreSQL Reliability](../../lessons/15-postgres-reliability/README.md)

## Prerequisites

Run this against **PostgreSQL**. The defect depends on Postgres refusing a foreign key to a table that does not exist yet; SQLite accepts that reference at creation time and the failure never appears.

```bash
docker compose exec app php artisan migrate
```

## Start

```bash
php artisan challenge:start db-migrations-disorder
```

`php artisan challenge:stop` restores everything when you are done.

## Observe the symptom first

Run the migrations:

```bash
docker compose exec app php artisan migrate
```

It fails. Read the error before anything else — it names a relation, and that name is the clue.

Now list the files and read them:

```bash
ls database/migrations/
```

Compare each **filename** with the table each file actually creates. Then ask what order the runner uses, and why that order matters here specifically.

## Hints

<details>
<summary>Hint 1 — the ordering rule</summary>

The runner applies migrations in filename order. Nothing inspects the SQL to work out dependencies, so the numeric prefix is the only thing deciding what runs first.
</details>

<details>
<summary>Hint 2 — what the error is telling you</summary>

A foreign key can only point at a table that already exists. One of these two tables references the other. Work out which must exist first, then check whether the file that creates it actually runs first.
</details>

<details>
<summary>Hint 3 — keep the filenames</summary>

Do not rename or delete the files. `challenge:stop` restores the exact set it copied in, so exchanging the *contents* keeps recovery reliable — and it leaves each filename describing what it really does.
</details>

<details>
<summary>Hint 4 — the second defect</summary>

One migration mixes SQLite's auto-increment idiom with Postgres types. The runner would rewrite it for you, but this course writes native Postgres, so use the Postgres type for a self-incrementing 8-byte primary key.
</details>

## Success criteria

- `php artisan migrate` completes without error.
- Each filename matches the table its SQL creates.
- The posts migration uses native Postgres auto-increment, not the SQLite idiom.
- `\d posts` shows the foreign key to `users`.

## Verify

```bash
php artisan challenge:verify
```

Then confirm it for real: `docker compose exec app php artisan migrate:fresh` should rebuild both tables cleanly from empty.

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 15: PostgreSQL Reliability** — read this first
