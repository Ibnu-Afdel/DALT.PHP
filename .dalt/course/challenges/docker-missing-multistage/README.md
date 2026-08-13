# Challenge: docker-missing-multistage

## The Problem

Docker builds your image in a single stage. That means the final image contains Composer, its dependencies, and any build tooling — none of which belong in production.

Right now your image is unnecessarily large. Multi-stage builds let you use a heavy `builder` image to install dependencies, then throw it away and copy only the artifacts you need into a lean `runtime` image.

## What You Need to Fix

Load this challenge:

```bash
php artisan challenge:start docker-missing-multistage
```

A `Dockerfile` is copied into your project root. It works — but it installs Composer directly into the final image. This is the broken pattern you need to fix.

Open the `Dockerfile`. You'll see three problems:

1. **No builder stage** — there's only one `FROM` block, and it installs everything into the same image that runs PHP-FPM in production
2. **Composer binary copied into the runtime image** — the line `COPY --from=composer:2 /usr/bin/composer /usr/bin/composer` puts Composer where it doesn't belong
3. **No HEALTHCHECK** — Docker has no way to know whether PHP-FPM is actually accepting requests, only that the process is alive

## What You Must Do

Convert the Dockerfile to a proper two-stage build:

### Stage 1: `builder`

Use `FROM composer:2 AS builder` as your first stage. This image has PHP and Composer pre-installed. Your job in this stage is only to install dependencies:

```
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader
```

### Stage 2: `runtime`

Start fresh with `FROM php:8.2-fpm-alpine`. Install extensions. Then copy the vendor directory from the builder — not Composer itself:

```
COPY --from=builder /app/vendor ./vendor
```

Remove `COPY --from=composer:2 /usr/bin/composer /usr/bin/composer` entirely — that line installs the Composer binary into your production image, which is what you're trying to avoid.

### Add a HEALTHCHECK

Before the `CMD`, add a `HEALTHCHECK` that tests PHP-FPM's configuration:

```
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD php-fpm -t || exit 1
```

## Hints

- The first `FROM` should be `FROM composer:2 AS builder` and sets up a temporary build environment
- The second `FROM` is `FROM php:8.2-fpm-alpine` — this is the image that actually runs in production
- `COPY --from=builder /app/vendor ./vendor` pulls the installed `vendor/` directory from the builder stage into the runtime image
- `HEALTHCHECK` goes after the `EXPOSE` instruction, before `CMD`

## Verify

```bash
php artisan challenge:verify
```

All five checks must pass.

## Testing the Build

The checks above confirm shape — the right instructions in the right form — not that an image actually builds. A full build is not part of `challenge:verify`: `docker-php-ext-install pdo_pgsql` compiles against `postgresql-dev`, which on Alpine pulls in a full `clang`/`llvm` toolchain and can take several minutes even with a warm layer cache. (This is the same slow step documented for Lesson 07's Dockerfile — it is not specific to your fix.)

A fast sanity check that *is* worth running before and after, because it costs under a second:

```bash
docker build --check -f Dockerfile .
```

Read this for what it is: a linter, not a functional check. It confirms the Dockerfile parses and follows BuildKit's own best-practice rules — it reports "no warnings" on both the broken single-stage version and a correct multi-stage one, because neither the missing builder stage nor the leftover `COPY --from=composer:2 /usr/bin/composer` is a syntax problem. It cannot replace `challenge:verify` or a real build.

If you want to confirm the actual point of a multi-stage build — that Composer never reaches the image that runs in production — build it and check:

```bash
docker build -t dalt-php-multistage .
docker run --rm dalt-php-multistage which composer
```

Before your fix, this prints `/usr/bin/composer` — the interpreter is standard `which`, so the exact path matches what the `COPY --from=composer:2 /usr/bin/composer /usr/bin/composer` line put there. After your fix, `which` should exit non-zero and print nothing: the runtime stage never had Composer, only `builder` did, and `builder` was discarded.
