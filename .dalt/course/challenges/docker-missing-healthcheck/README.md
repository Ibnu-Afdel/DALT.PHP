# Challenge: docker-missing-healthcheck

## The Problem

Your `docker-compose.yml` has a `depends_on: db` directive in the `app` service.

But `depends_on` only waits for the `db` container's **process** to start. It doesn't wait for Postgres to finish booting up and accepting connections.

If you run `docker compose up`, your PHP app will start instantly, try to connect to the database, and crash with a "Connection refused" error because Postgres is still initializing its data directory.

To fix this, you must tell Docker how to check if Postgres is *healthy*, and tell the app to wait for that health status.

## What You Need to Fix

Load this challenge:

```bash
php artisan challenge:start docker-missing-healthcheck
```

The `docker-compose.yml` file is copied to your project root.

1. Add a `healthcheck` block to the `db` service. Use `pg_isready -U postgres` as the test command.
2. Update the `depends_on` block in the `app` service. It currently uses the short array syntax (`- db`). Change it to the object syntax and add `condition: service_healthy`.

## Hints

- A Postgres healthcheck looks like this:
  ```yaml
  healthcheck:
    test: ["CMD-SHELL", "pg_isready -U postgres"]
    interval: 5s
    timeout: 5s
    retries: 5
  ```
- The long syntax for `depends_on` looks like this:
  ```yaml
  depends_on:
    db:
      condition: service_healthy
  ```

## Verify

```bash
php artisan challenge:verify
```

This reports 3 checks, not 2 — the healthcheck fix is confirmed by two checks (the block exists, and it uses `pg_isready`), the `depends_on` fix by one. Still two things to fix.
