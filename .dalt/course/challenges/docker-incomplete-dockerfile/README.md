# Challenge: Incomplete Dockerfile

## Difficulty: Easy — 3 missing pieces

## What This Challenge Is

A Dockerfile for DALT.PHP has been started but is missing three critical parts: `WORKDIR`, the Postgres PHP extension, and `CMD`.

`docker build` will actually succeed without any of the three — the base image, `php:8.2-fpm-alpine`, already ships its own `WORKDIR /var/www/html` and `CMD ["php-fpm"]`, so the build doesn't fail and the container starts either way. That is exactly why this challenge matters: a build that succeeds is not proof the image is correct. The one part whose absence you can actually observe is the missing extension — try to use the database and you get `could not find driver`. The "Testing the Build" section below shows all three, verified, so you know what to expect and what not to.

Run the challenge to load the incomplete Dockerfile into your project root:

```bash
php artisan challenge:start docker-incomplete-dockerfile
```

Then open `Dockerfile` in your editor and complete it.

## The Three Missing Parts

### 1. Working directory

PHP-FPM expects to serve files from `/var/www/html`. Every `COPY` and `RUN` instruction after this point executes from whatever `WORKDIR` is currently set. The base image, `php:8.2-fpm-alpine`, already defaults it to `/var/www/html` — so leaving this out won't visibly break anything here. Set it explicitly anyway; see "Why These Three Things" below for why that matters even when nothing observably fails.

The instruction is `WORKDIR`. Set it to `/var/www/html`.

### 2. PHP extensions

DALT.PHP uses PDO to connect to databases. The `pdo` extension provides the base PDO class. The `pdo_pgsql` extension provides the PostgreSQL driver. Neither is installed in the base `php:8.2-fpm-alpine` image.

The helper command for installing PHP extensions inside Docker is `docker-php-ext-install`. You can install multiple extensions in one `RUN` command by listing them space-separated.

The `RUN` instruction must come before the `COPY` instructions — this keeps the slow extension compilation in a cached layer.

### 3. CMD to start PHP-FPM

When the container starts, it needs to know what process to run. PHP-FPM is started with the `php-fpm` command. Use the array form of `CMD` so the process runs as PID 1 and receives signals correctly.

## Files Involved

- `Dockerfile` — the only file you need to edit

## Verify Your Solution

```bash
php artisan challenge:verify
```

The verifier checks:
- `WORKDIR /var/www/html` is present
- `docker-php-ext-install` is called
- `pdo_pgsql` extension is installed
- `CMD ["php-fpm"]` is present
- No `# TODO` comments remain

## Testing the Build

The checks above confirm the Dockerfile's *shape* — the right instructions, in the right form. They do not run a build; a full `docker build` is too slow to run on every check (`docker-php-ext-install pdo_pgsql` compiles from source and can take several minutes). Prove the actual behavior yourself, before and after your fix, with the commands below — every command and every line of output here was run against this exact challenge.

**Before your fix**, the build still succeeds — this is the trap:

```bash
docker build -t dalt-php .
```

```
...
#11 exporting to image
#11 writing image sha256:1f629f04e70d1a674cd1e7665d360721a271ef10a003ccc669953b2aa737d2ed done
#11 naming to docker.io/library/dalt-php done
```

No error. `WORKDIR` and `CMD` are silently supplied by the base image, so the build gives no signal that anything is missing. The database driver is the one part that's actually gone:

```bash
docker run --rm dalt-php php -m | grep pdo
```

```
PDO
pdo_sqlite
```

`pdo_pgsql` is absent. Trying to use it throws exactly this:

```bash
docker run --rm dalt-php php -r 'new PDO("pgsql:host=localhost;dbname=x", "u", "p");'
```

```
PHP Fatal error:  Uncaught PDOException: could not find driver
```

**After your fix**, the same extension check should show `pdo_pgsql` in the list:

```bash
docker build -t dalt-php . && docker run --rm dalt-php php -m | grep pdo
```

```
PDO
pdo_pgsql
pdo_sqlite
```

If you also want to see the explicit `WORKDIR`/`CMD` take effect (rather than falling back to the base image's), check them directly:

```bash
docker inspect --format '{{.Config.WorkingDir}} {{json .Config.Cmd}}' dalt-php
```

Setting them explicitly is still the right call even though this base image happens to default to the same values — a Dockerfile that depends on an undocumented base-image default breaks the moment that base image changes, and it's not obvious to the next reader that `WORKDIR`/`CMD` were ever considered. Explicit is the behavior you're actually verifying; implicit is the behavior you're hoping for.

**Note:** this repository has both a learning platform (`.dalt/`) and a plain PHP skeleton at the root; a build without a `.dockerignore` sends both, plus `.git/` and `node_modules/`, into the build context. If you haven't already, create the `.dockerignore` from this lesson's "The `.dockerignore` File" section before building.

## Hints

- `WORKDIR` takes a single path argument: `WORKDIR /var/www/html`
- `docker-php-ext-install pdo pdo_pgsql` — both on the same line saves one layer
- `CMD ["php-fpm"]` — square brackets, quoted string, no flags needed

## Why These Three Things

**WORKDIR:** `php:8.2-fpm-alpine` happens to already set `WORKDIR /var/www/html`, so `COPY composer.json ./` lands in the right place even without it — verified with `docker inspect`. That's a property of this one base image tag, not something a Dockerfile should lean on. Set it explicitly: if the base image ever changes (a different variant, a future major version), an implicit `WORKDIR` you never wrote is not a "safe default," it's a broken build waiting to be diagnosed with `COPY composer.json ./` landing in the wrong place with no error to point at why.

**Extensions:** Without `pdo_pgsql`, the database connection throws `could not find driver` — reproduced above. Without `pdo`, nothing database-related works at all. This is the one part of the three whose absence is actually observable.

**CMD:** Same story as `WORKDIR` — the base image already ships `CMD ["php-fpm"]`, so the container starts fine either way, verified above. Set it explicitly for the same reason: relying on an inherited default you didn't write is a maintenance trap, not a fix.

## Next Challenge

After completing this, move on to **docker-broken-nginx** to fix the Nginx configuration that routes HTTP requests to PHP-FPM.
