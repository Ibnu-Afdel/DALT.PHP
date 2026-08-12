# Lesson 14: Docker Production Patterns

## Beyond "It Works on My Machine"

Running `docker compose up` locally is easy. Running it on a server exposed to the internet requires a different set of rules.

If your database password is in your `docker-compose.yml`, it's probably committed to git. If your app container crashes, it stays dead unless you have a restart policy. If your app gets stuck in an infinite loop, it will consume 100% of the server's CPU and crash the database next to it.

This lesson covers the essential patterns to make your stack secure, resilient, and well-behaved in a shared environment.

## Learning Objectives

- Remove plaintext passwords from Compose using Docker secrets
- Read secrets securely from PHP
- Use health checks so dependent services wait for actual readiness
- Apply `restart` policies correctly
- Set `mem_limit` and `cpus` to protect neighboring containers
- Configure log rotation so your disk doesn't fill up
- Understand why PgBouncer is required for scaling PHP with Postgres

---

## Docker Secrets

Never hardcode `POSTGRES_PASSWORD: supersecret` in `docker-compose.yml`. Even `.env` files are risky if they get copied around or accidentally committed.

Docker Secrets allow you to mount sensitive data as a read-only file in memory, rather than passing it as an environment variable (which can be leaked by `phpinfo()`, crash logs, or `docker inspect`).

### Step 1: Create the secret file

Create a file on the host machine containing the password. **Do not commit this file to git.**

`secrets/db_password.txt`:
```
s3cr3t_p4ssw0rd!
```

### Step 2: Define the secret in Compose

Add a top-level `secrets` block to tell Compose where the file is:

```yaml
secrets:
  db_password:
    file: ./secrets/db_password.txt
```

### Step 3: Use it in the database service

Postgres specifically supports `_FILE` environment variables. It will read the password from the secret file instead of requiring a raw string.

```yaml
services:
  db:
    image: postgres:16-alpine
    secrets:
      - db_password
    environment:
      # DO NOT USE: POSTGRES_PASSWORD
      POSTGRES_PASSWORD_FILE: /run/secrets/db_password
```

### Step 4: Use it in the app service (PHP)

Mount the secret into the app container as well:

```yaml
  app:
    build: .
    secrets:
      - db_password
```

Now the honest part. **DALT's configuration does not read `_FILE` variables.** `config/database.php` calls `env('DB_PASSWORD', '')`, and `env()` looks only in `$_ENV`, `$_SERVER`, and `getenv()`. Mounting the secret changes nothing on its own — the file appears at `/run/secrets/db_password` and the framework never looks at it.

`POSTGRES_PASSWORD_FILE` works because the **Postgres image** implements that convention, not because Docker or DALT does. Each program has to opt in.

So you bridge it yourself in the app's entrypoint, before PHP starts:

```sh
#!/bin/sh
# docker-entrypoint.sh
set -e

if [ -f /run/secrets/db_password ]; then
    DB_PASSWORD="$(cat /run/secrets/db_password)"
    export DB_PASSWORD
fi

exec "$@"
```

```dockerfile
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
```

Nothing in the application changes: `env('DB_PASSWORD')` finds the value as usual, and the secret never appears in `docker-compose.yml` or in the image.

**Be clear about what this does and does not buy you.** The secret is no longer committed to source control or baked into the image, which is the main risk. But once the entrypoint exports it, the value *is* in the process environment and can still surface through `phpinfo()` or a crash dump. Reading the file at the point of use avoids that; exporting it trades some of the benefit for not modifying the framework's config layer.

Do **not** reach for a raw `new PDO(...)` to work around this. That bypasses `Core\Database` — its DSN validation, its PDO attributes, and the single shared connection — for no gain.

---

## Health Checks and Startup Ordering

The short array form — `depends_on: [db]` — controls **start order only**. Compose starts `db` first and then immediately starts `app`, without waiting for Postgres to finish initialising. On a cold volume that initialisation takes seconds, so the app's first connection attempt often fails while the container is technically "up".

The long form adds the wait. Give the database a readiness check and make the app depend on its health:

```yaml
services:
  app:
    depends_on:
      db:
        condition: service_healthy

  db:
    image: postgres:16-alpine
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 5s
      timeout: 5s
      retries: 5
```

`pg_isready` checks whether Postgres is accepting connections. Compose starts the app only after the database reports `healthy`, avoiding a startup race where the app tries to connect while Postgres is still initializing.

## Restart Policies

By default, if a container crashes, it stays down. `restart` policies tell Docker what to do when a process exits.

- `restart: unless-stopped` — The standard for production web services. It restarts the container if it crashes, and starts it automatically on server reboot. It only stays down if you explicitly run `docker stop`.
- `restart: on-failure` — Best for background workers. If the worker crashes due to an error (exit code > 0), it restarts. If it exits cleanly (exit code 0), it stays stopped.
- `restart: always` — Avoid this. It will blindly restart even if you meant to stop it gracefully in some contexts.

```yaml
services:
  app:
    build: .
    restart: unless-stopped
```

---

## Resource Limits

In a Compose stack, a memory leak in your PHP app can consume all the RAM on the host machine, causing the Linux OOM (Out Of Memory) killer to terminate a random process — which is almost always the Postgres database, because it looks like the biggest target.

Protect your services by isolating them:

```yaml
services:
  app:
    build: .
    deploy:
      resources:
        limits:
          cpus: "0.5"      # Max 50% of a single CPU core
          memory: 256M     # Max 256MB RAM. If it tries to use more, Docker kills the app, not the DB.
```

*(Note: Older Compose versions used `mem_limit` and `cpus` directly on the service, but the `deploy.resources.limits` syntax is the modern standard.)*

---

## Log Rotation

By default, Docker captures all stdout/stderr from your containers and writes it to a JSON file on the host. If your app prints a lot of logs, this file will eventually consume 100% of your disk space, bringing down the whole server.

Configure the `json-file` driver to rotate logs automatically:

```yaml
services:
  app:
    build: .
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```

This keeps a maximum of 3 log files, each capped at 10MB. You'll never use more than 30MB of disk for logs per service.

*(For large deployments, you'd use `driver: syslog` to ship logs to a centralized aggregator like Datadog or ELK.)*

---

## Connection Pooling with PgBouncer

### The Problem

PHP's execution model is "shared nothing". Every single HTTP request spins up, creates a new connection to Postgres, runs queries, and drops the connection.

Establishing a Postgres connection is slow (it creates a new OS process per connection). Postgres also has a strict limit on concurrent connections (often 100). If you get 150 simultaneous HTTP requests, the 101st request will get `FATAL: sorry, too many clients already`.

### The Solution

A connection pooler sits between PHP and Postgres. It maintains a small pool of permanent connections to Postgres (e.g., 20), and accepts thousands of connections from PHP.

PHP connects to PgBouncer. PgBouncer hands PHP a temporary lease on one of its permanent Postgres connections, then takes it back the millisecond the query is done.

### Adding PgBouncer to Compose

```yaml
services:
  db:
    image: postgres:16-alpine
    # db configuration...

  pgbouncer:
    image: edoburu/pgbouncer
    environment:
      - DB_USER=postgres
      - DB_PASSWORD=supersecret  # Or use secrets!
      - DB_HOST=db
      - POOL_MODE=transaction
      - MAX_CLIENT_CONN=1000
      - DEFAULT_POOL_SIZE=20
    ports:
      - "5432:5432"
    depends_on:
      db:
        condition: service_healthy

  app:
    environment:
      # App now connects to PgBouncer, not directly to db
      - DB_HOST=pgbouncer
```

**`POOL_MODE=transaction`** is critical for PHP. It means PgBouncer returns the connection to the pool immediately after the `COMMIT`, rather than waiting for the PHP script to finish execution.

---

## CI/CD Pipeline (Concept)

How does this code get to the server? You don't run `git pull` on the production box.

1. You push code to GitHub.
2. A GitHub Action runs `docker build -t ghcr.io/yourname/myapp:main .`
3. The Action runs `docker push ghcr.io/yourname/myapp:main` to a registry.
4. The server runs `docker pull` and `docker compose up -d` to swap the old container for the new one.

This guarantees the image you tested in CI is exactly the bit-for-bit identical image running in production.

---

## Checkpoint

Answer from memory:

1. Explain why `POSTGRES_PASSWORD_FILE` works while `DB_PASSWORD_FILE` does nothing, in terms of who implements the convention.
2. State what mounting a secret into the app container achieves on its own, before you write any code.
3. Name the risk that survives exporting a secret in an entrypoint, and the alternative that avoids it.
4. Explain the difference between `depends_on: [db]` and `depends_on: db: condition: service_healthy`, and the failure the second one prevents.
5. `pg_isready` reports healthy. State what that does and does not guarantee about your application's queries.
6. Explain why reaching for `new PDO(...)` to read a secret is a bad trade.

## Your Task

Load the broken challenge:

```bash
php artisan challenge:start docker-plaintext-secrets
```

The `docker-compose.yml` file has a plaintext password hardcoded as an environment variable.

1. Add a `secrets` block to the top level of the compose file that points `db_password` to `./secrets/db_password.txt`.
2. In the `db` service, remove the `POSTGRES_PASSWORD` environment variable.
3. Reference the secret in the `db` service using the `secrets:` array and set `POSTGRES_PASSWORD_FILE: /run/secrets/db_password`.

Verify:

```bash
php artisan challenge:verify
```

## Next Steps

- **Challenge: docker-plaintext-secrets** — move a hardcoded password to a Docker secret
- **Challenge: docker-missing-healthcheck** — add health checks to Compose services
- **Lesson 15: PostgreSQL Reliability** — backups, migrations, and the job queue project
