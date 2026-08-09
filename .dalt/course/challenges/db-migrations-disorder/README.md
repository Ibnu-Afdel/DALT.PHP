# Challenge: db-migrations-disorder

## The Problem

Migrations must execute in a specific order. If Table B has a foreign key pointing to Table A, Table A must be created first. 

DALT's migration runner executes files in alphabetical order based on their filenames.

You have two migration files whose contents are reversed:
- `001_create_users_table.sql` creates `posts`
- `002_create_posts_table.sql` creates `users`

The `posts` table has a `user_id` column that `REFERENCES users(id)`. Because `001` runs before `002`, the migration crashes with an error: `relation "users" does not exist`.

Furthermore, the person who wrote the posts migration used SQLite syntax (`INTEGER PRIMARY KEY AUTOINCREMENT`) instead of the Postgres equivalent (`BIGSERIAL PRIMARY KEY`). DALT's runner normally auto-converts this for you, but for this course we want you to write raw, native Postgres SQL.

## What You Need to Fix

Load this challenge:

```bash
php artisan challenge:start db-migrations-disorder
```

Two files are copied to `database/migrations/`:
- `001_create_users_table.sql`
- `002_create_posts_table.sql`

1. Move the users-table SQL into `001_create_users_table.sql` so it runs first.
2. Move the posts-table SQL into `002_create_posts_table.sql`.
3. In the posts migration, change `INTEGER PRIMARY KEY AUTOINCREMENT` to `BIGSERIAL PRIMARY KEY`.

## Hints

- Keep both filenames in place and exchange their SQL contents. This lets challenge stop/reset own and restore the exact file set safely.
- `BIGSERIAL` is the Postgres type for a self-incrementing 8-byte integer.

## Verify

```bash
php artisan challenge:verify
```
