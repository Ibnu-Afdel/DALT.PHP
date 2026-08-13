# Challenge: Missing Row-Level Security

**Difficulty:** Hard · **Bugs:** 2 · **Lesson:** [16 — Advanced PostgreSQL Patterns](../../lessons/16-postgres-advanced-patterns/README.md)

## Prerequisites

This challenge needs **PostgreSQL**, a `tenant_id` column on `posts`, and — critically — a connection that is **not** a superuser.

```sql
ALTER TABLE posts ADD COLUMN IF NOT EXISTS tenant_id INT NOT NULL DEFAULT 1;

CREATE ROLE app_user LOGIN PASSWORD 'change-me';
GRANT SELECT, INSERT, UPDATE, DELETE ON posts TO app_user;
```

```env
DB_USERNAME=app_user
DB_PASSWORD=change-me
```

Seed at least two tenants so you have something to isolate:

```sql
INSERT INTO posts (tenant_id, title, body, user_id)
VALUES (1, 'Tenant one', 'x', 1), (1, 'Also tenant one', 'x', 1), (2, 'Tenant two', 'x', 1);
```

**Do not skip the role.** Superusers and table owners bypass row-level security silently, so with the default `postgres` user every policy you write will appear to do nothing.

## Start

```bash
php artisan challenge:start db-missing-rls
php artisan serve
```

`php artisan challenge:stop` restores everything when you are done.

## Observe the current design

`GET /tenant/{tenant_id}/posts` returns the right rows today. Try it for tenant 1 and tenant 2 — the data really is separated.

So nothing is visibly broken. The problem is *where* the separation lives: read the controller and note what is doing the filtering. Then ask what happens the day someone adds a second endpoint over the same table and forgets that line.

That is the failure this challenge prevents: not a bug you can see, but one you cannot see yet.

## Hints

<details>
<summary>Hint 1 — move the rule</summary>

The database can enforce which rows a session may see, so that *every* query against the table is filtered whether or not the developer remembered. Two pieces are needed: switching the feature on for the table, and a rule describing what is visible.
</details>

<details>
<summary>Hint 2 — the rule needs to know the tenant</summary>

A policy cannot read PHP variables. It reads a per-session setting, which your application sets once per request before querying. Look up `current_setting(name, missing_ok)`.
</details>

<details>
<summary>Hint 3 — setting it from PHP</summary>

The obvious statement does not work:

```php
$db->query('SET app.tenant_id = :id', ['id' => $tenantId]);
// SQLSTATE[42601]: syntax error at or near "$1"
```

`SET` is a utility statement and takes no bind parameters. Interpolating the value instead would put request data straight into SQL — in the feature meant to stop tenant data leaking. There is an ordinary *function* that does the same job and accepts parameters normally.
</details>

<details>
<summary>Hint 4 — finishing the job</summary>

Once the database enforces the rule, the hand-written `WHERE tenant_id = :id` is exactly what you delete. Removing it is the point: the endpoint should stay correct without it.
</details>

## Success criteria

- `003_enable_rls.sql` enables row-level security and creates a policy driven by the session setting.
- The controller sets the tenant with a **bound** parameter, not string interpolation.
- The controller no longer filters by `tenant_id` in SQL.
- Requesting tenant 1 returns only tenant 1's rows, and tenant 2 only tenant 2's.

## Verify

```bash
php artisan challenge:verify
```

The checks connect as `app_user` and prove the isolation directly — including a raw, unfiltered read that bypasses the controller entirely, so a fix that keeps the hand-written PHP filter and skips real RLS still fails. If they report a connection error, confirm `DB_USERNAME`/`DB_PASSWORD` in `.env` point at `app_user`, not `postgres`. It's still worth requesting both tenants yourself and comparing — the checks tell you *that* isolation holds, watching the two responses side by side is how you see *why*.

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 16: Advanced PostgreSQL Patterns** — read this first
- **Next:** Lesson 17 — Observability
