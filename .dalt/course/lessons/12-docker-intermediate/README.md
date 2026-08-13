# Lesson 12: Docker Intermediate

## Why Your Image Is Too Big

You've built a working Dockerfile. It installs Composer, copies source files, and starts PHP-FPM. It runs. But here's the problem: your final image probably contains Composer itself, `git`, and a bunch of build-time tools that have absolutely no business being in production.

Check the size of a naïve PHP image built with Composer installed directly:

```bash
docker images | grep myapp
# myapp   latest   ...   420MB
```

A production PHP-FPM image should be around 80–120MB. The rest is dead weight that enlarges your registry storage, slows your CI pipeline, and expands your attack surface.

Multi-stage builds fix this.

## Learning Objectives

- Write a two-stage Dockerfile that separates `build` from `runtime`
- Add `HEALTHCHECK` so Docker knows when a service is actually ready
- Use `condition: service_healthy` in Compose so containers wait for each other properly
- Understand Docker's network model at the DNS level
- Back up a named volume without stopping any containers
- Write a `.dockerignore` that keeps your images clean

## Predict before reading

Before reading further, write down what you expect for each:

| Question | What do you expect? |
|---|---|
| The `builder` stage installs Composer and `vendor/`; the runtime stage never runs `RUN composer install`. Is Composer present in the final image? | ? |
| `docker compose up` starts `app` with plain `depends_on: [db]` (no `condition`). Does `app` ever try to connect before Postgres is ready to accept connections? | ? |
| The same stack, but `db` has a `HEALTHCHECK` and `app` uses `depends_on: db: condition: service_healthy`. Same question. | ? |
| `docker run -v pgdata:/data -v $(pwd):/backup busybox tar czf ...` runs while the `db` container is still up and accepting writes. Does the backup fail? | ? |
| `.dockerignore` excludes `vendor/`, but the builder stage's `COPY composer.json composer.lock ./` happens before `.dockerignore` is even relevant to that stage. Does the builder stage still get `composer.json`? | ? |

The first is the one worth being wrong about — the answer is the entire reason multi-stage builds exist, and it is easy to assume something so central to the build must leak through by accident.

---

## Multi-Stage Builds

A multi-stage build uses multiple `FROM` blocks in one Dockerfile. Each `FROM` starts a fresh layer chain. You can `COPY --from=<stage>` to pull files from a previous stage into the current one. The final image is built from only the last stage.

Here's the pattern for DALT.PHP:

```dockerfile
# ---- Stage 1: builder ----
# Uses composer:2, which has PHP + Composer pre-installed
FROM composer:2 AS builder

WORKDIR /app

# Copy only what Composer needs first — layer caching wins here
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

# ---- Stage 2: runtime ----
# Starts fresh from a minimal PHP-FPM image — no Composer here
FROM php:8.2-fpm-alpine AS runtime

WORKDIR /var/www/html

# Install the OS packages and PHP extensions the app needs.
# postgresql-dev supplies libpq-fe.h, which pdo_pgsql compiles against —
# without it the build stops at "Cannot find libpq-fe.h" (Lesson 07).
RUN apk add --no-cache curl postgresql-dev \
 && docker-php-ext-install pdo pdo_pgsql

# Pull ONLY the installed vendor directory from the builder stage
# Composer itself stays in the builder — it never reaches the runtime image
COPY --from=builder /app/vendor ./vendor

# Copy application source
COPY . .

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD php-fpm -t || exit 1

CMD ["php-fpm"]
```

### What changed and why

| Single-stage | Multi-stage |
|---|---|
| Composer binary in final image | Composer stays in `builder` |
| `composer install` in runtime layer | `vendor/` copied as a static directory |
| One thick layer chain | Thin runtime layer chain |
| ~400MB image | ~90MB image |

The runtime image never sees `composer`, `git`, or any build tooling. If someone gets RCE inside the container, they can't use Composer to pull more code.

### Layer caching with multi-stage

The caching strategy still applies inside each stage. Copy `composer.json` and `composer.lock` before the application source so that a code change doesn't re-run `composer install`:

```dockerfile
# In builder stage:
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction  # ← cached unless composer files change

COPY . .                                        # ← app source, changes every commit
```

---

## HEALTHCHECK

`HEALTHCHECK` lets Docker periodically test whether a service inside a container is actually working, not just running. A container can be "running" (process alive) but the service inside it can be broken.

```dockerfile
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD php-fpm -t || exit 1
```

Flags:
- `--interval=30s` — run the check every 30 seconds
- `--timeout=5s` — if the check takes longer than 5 seconds, treat it as a failure
- `--start-period=10s` — don't count failures during the first 10 seconds (let the service boot)
- `--retries=3` — mark unhealthy only after 3 consecutive failures

For a Postgres service in Compose, use `pg_isready`:

```yaml
db:
  image: postgres:16-alpine
  healthcheck:
    test: ["CMD-SHELL", "pg_isready -U postgres"]
    interval: 10s
    timeout: 5s
    retries: 5
    start_period: 10s
```

Check health status at any time:

```bash
docker inspect --format='{{.State.Health.Status}}' <container-id>
# healthy
```

---

## `depends_on` with `condition: service_healthy`

`depends_on: db` (without a condition) starts the `app` container as soon as the `db` container's process starts. Postgres takes a few seconds to initialize its data directory. Your PHP app will try to connect during that window and fail.

The fix: combine `HEALTHCHECK` on the `db` service with `condition: service_healthy` in the `app` service:

```yaml
services:
  db:
    image: postgres:16-alpine
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 10s
      timeout: 5s
      retries: 5

  app:
    build: .
    depends_on:
      db:
        condition: service_healthy   # ← waits for pg_isready to pass
    env_file:
      - .env
```

Now `app` only starts once `pg_isready` returns healthy. No more connection-refused errors on first boot.

---

## Docker Networking in Compose

When you run `docker compose up`, Compose creates a private bridge network and attaches all services to it. Each service gets a DNS name equal to its service name.

So from inside the `app` container:

```bash
# These are the same:
ping db         # resolves to the db container's IP
ping nginx      # resolves to the nginx container's IP
```

This is why your `.env` has `DB_HOST=db` instead of `DB_HOST=localhost` or an IP address. The service name is the hostname.

Inspect networks:

```bash
docker network ls
docker network inspect daltphp_default
```

You'll see a list of containers, their IPs, and their DNS aliases on the network.

---

## Volume Backups Without Downtime

Named volumes persist data between `docker compose down` runs. But they're not automatically backed up. Here's how to snapshot a running Postgres volume without stopping anything:

```bash
docker run --rm \
  -v pgdata:/data \
  -v $(pwd):/backup \
  busybox \
  tar czf /backup/pgdata-$(date +%Y%m%d).tar.gz /data
```

What this does:
- Spins up a throwaway `busybox` container
- Mounts the `pgdata` volume at `/data`
- Mounts the current directory at `/backup`
- Runs `tar` to compress `/data` into `/backup/pgdata-20240101.tar.gz`

To restore:

```bash
docker run --rm \
  -v pgdata:/data \
  -v $(pwd):/backup \
  busybox \
  tar xzf /backup/pgdata-20240101.tar.gz -C /
```

Run this while the `db` container is stopped if you need a clean restore.

---

## `.dockerignore`

Just as `.gitignore` keeps files out of git, `.dockerignore` keeps files out of the build context. Without it, every `COPY . .` sends your entire working directory — including `vendor/`, `.git/`, and log files — to the Docker daemon. That slows the build and inflates the image.

Create `.dockerignore` at the project root:

```
# Dependencies — rebuilt by Composer in the builder stage
vendor/

# Git history — never needed in an image
.git/
.gitignore

# Logs and caches
storage/logs/*.log
*.log

# SQLite databases used locally but not in Docker
database/*.sqlite

# Development-only config
.env.local
.env.testing

# Node.js tooling (not used at runtime)
node_modules/
npm-debug.log

# IDE config
.vscode/
.idea/
```

After adding `.dockerignore`, run:

```bash
docker build -t myapp . --no-cache
```

Watch how much faster the build context upload step is.

---

## Summary

| Concept | What it gives you |
|---|---|
| Multi-stage build | Smaller, cleaner runtime image with no build tools |
| `HEALTHCHECK` | Docker tracks real service health, not just process state |
| `condition: service_healthy` | App waits for Postgres to actually be ready |
| Service DNS in Compose | Containers talk to each other by name, not IP |
| Volume backup with busybox | Snapshot a live volume without downtime |
| `.dockerignore` | Smaller build context, faster builds, no secrets in image |

---

## Checkpoint

Answer from memory:

1. Explain what ends up in the final image from a multi-stage build, and what is discarded.
2. State the security argument for keeping Composer out of the runtime stage.
3. `COPY --from=builder /app/vendor ./vendor` — explain why the source path is `/app` and not `/var/www/html`.
4. Explain why layer-cache ordering still matters *inside* each stage.
5. Name the OS package `pdo_pgsql` compiles against, and the error you get without it.
6. Explain what `depends_on: condition: service_healthy` gives you that plain `depends_on` does not.

## Your Task

Load the broken Dockerfile:

```bash
php artisan challenge:start docker-missing-multistage
```

The Dockerfile is single-stage and installs Composer directly into the runtime image. Convert it to a multi-stage build:

1. Add a `builder` stage using `FROM composer:2 AS builder`
2. Run `composer install` in the builder stage
3. In the runtime stage, use `COPY --from=builder /app/vendor ./vendor` instead of running Composer
4. Add a `HEALTHCHECK` instruction to the runtime stage
5. Remove the `COPY --from=composer:2 /usr/bin/composer /usr/bin/composer` line — it belongs in the builder, not runtime

Verify:

```bash
php artisan challenge:verify
```

## Laravel bridge

Compared against Laravel 13.x ([laravel.com/docs/13.x/sail](https://laravel.com/docs/13.x/sail), consulted 2026-08-13).

| Laravel 13.x (Sail) | DALT |
|---|---|
| Sail's published images are already built and slimmed by the Sail team; `sail artisan sail:publish` gives you the Dockerfiles to customize, but most projects never touch multi-stage layout themselves | you write the `builder`/`runtime` split yourself and can see exactly what each stage discards |
| no first-party `HEALTHCHECK` wiring documented for Sail's own `compose.yaml` — container readiness is typically handled by application-level retry logic instead | `HEALTHCHECK` + `depends_on: condition: service_healthy` push readiness into Docker itself, so a connection is never attempted before Postgres accepts one |
| service DNS works the same way — Sail's app service reaches its database at the compose service name | identical mechanism, same `db`/`app`/`nginx` names from Lesson 08 |
| no documented built-in volume-backup command; teams reach for `pg_dump` (Lesson 15) or a third-party backup tool | the `busybox`+`tar` snapshot above is a Docker-level backup of the whole volume, independent of what's running inside it — useful, but `pg_dump` is the one you actually want for a live Postgres backup (Lesson 15 covers why) |

There is no Laravel-specific concept mapping onto multi-stage builds or `HEALTHCHECK` — these are Docker primitives Sail's prebuilt images use for you without ever showing you the Dockerfile that does it. Writing the split yourself here is what makes Sail's own images legible later, if you ever `sail:publish` them.

## Next Steps

- **Challenge: docker-missing-multistage** — convert a single-stage Dockerfile to multi-stage with a HEALTHCHECK
- **Lesson 13: PostgreSQL Advanced** — window functions, CTEs, JSONB, and full-text search
