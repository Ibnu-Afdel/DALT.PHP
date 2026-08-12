# Lesson 08: Docker Compose

## The Problem With Running Containers Manually

In Lesson 07 you built a Dockerfile for DALT.PHP. To run it alongside Postgres and Nginx you'd need three separate `docker run` commands, each with the right flags, volumes, ports, and environment variables — and you'd have to run them in the right order every time.

Docker Compose solves this. You describe your entire stack in one file (`docker-compose.yml`), and a single command brings everything up:

```bash
docker compose up
```

One command. Three containers. Everything wired together.

## Learning Objectives

By the end of this lesson, you will:
- Understand the structure of a `docker-compose.yml` file
- Know the purpose of every common key: `image`, `build`, `ports`, `volumes`, `environment`, `depends_on`, `env_file`
- Know the difference between bind mounts and named volumes
- Understand how containers talk to each other by service name
- Know the daily Compose commands you'll use
- Be able to set up the full DALT.PHP stack: app + Postgres + Nginx

## The Full `docker-compose.yml` for DALT.PHP

Here is the complete file you're working toward. Every key is explained below.

```yaml
services:
  app:
    build: .
    volumes:
      - .:/var/www/html
    env_file:
      - .env
    depends_on:
      - db

  db:
    image: postgres:16-alpine
    env_file:
      - .env
    volumes:
      - pgdata:/var/lib/postgresql/data

  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

volumes:
  pgdata:
```

Note that **nginx gets the application code as well as the config**. That surprises people: PHP runs in the `app` container, so why does the web server need the files?

Because nginx serves static files itself. Its config sets `root /var/www/html/public` and uses `try_files $uri $uri/ /index.php?$query_string` — both of which are evaluated against nginx's **own** filesystem. Only when no file matches does the request become a PHP request forwarded to `app:9000`.

Mount the config alone and the stack looks like it works: pages render, because PHP is reached through the fallback. But `/build/assets/app-*.css`, `/build/assets/app-*.js`, and `/favicon.svg` all return 404, and you get an unstyled page with no obvious error in the PHP logs.

## Anatomy of the File

### `services:`

The top-level `services:` key is where you declare every container in your stack. Each entry under it is one container. The key name (`app`, `db`, `nginx`) becomes both the container name and the DNS hostname other containers use to talk to it.

### `build:` vs `image:`

```yaml
app:
  build: .        # Build from Dockerfile in the current directory
```

```yaml
db:
  image: postgres:16-alpine   # Pull this image from Docker Hub
```

`build:` — Docker builds a custom image from your Dockerfile. Use this for services you own (your PHP app).

`image:` — Docker pulls a pre-built image. Use this for off-the-shelf services (Postgres, Nginx, Redis).

### `ports:`

```yaml
nginx:
  ports:
    - "8080:80"
```

Format: `"HOST_PORT:CONTAINER_PORT"`.

This maps port 8080 on your machine to port 80 inside the nginx container. Visit `http://localhost:8080` to reach it.

Containers don't need `ports:` to talk to each other — they already share an internal network. `ports:` is only for exposing a container to your host machine.

### `volumes:`

Two kinds of volume entries:

**Bind mount** — links a path on your host to a path inside the container. Changes on either side are immediately visible on both sides.

```yaml
app:
  volumes:
    - .:/var/www/html   # host path : container path
```

This mounts your entire project directory into the container. Edit a file on your host — the container sees it instantly. No rebuild needed during development.

**Named volume** — Docker manages the storage. Data persists when the container is stopped or replaced. Use for databases.

```yaml
db:
  volumes:
    - pgdata:/var/lib/postgresql/data
```

```yaml
volumes:     # Declare at the top level — Docker creates and manages this
  pgdata:
```

Postgres stores all its data at `/var/lib/postgresql/data`. Without a volume, that data is inside the container and disappears when the container is removed.

### `env_file:`

```yaml
app:
  env_file:
    - .env
```

Loads every `KEY=VALUE` line from `.env` as an environment variable inside the container. This is how DALT reads `DB_HOST`, `DB_PASSWORD`, etc. — and how the Postgres image reads `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`.

One `.env` file can serve both. Just include all the variables both services need:

```env
# DALT.PHP database config
DB_DRIVER=pgsql
DB_HOST=db
DB_PORT=5432
DB_NAME=dalt
DB_USERNAME=postgres
DB_PASSWORD=secret

# Postgres container init config
POSTGRES_DB=dalt
POSTGRES_USER=postgres
POSTGRES_PASSWORD=secret
```

`DB_HOST=db` — not `localhost`. Inside the Compose network, the database container is reachable at the service name `db`. This is service DNS, explained next.

### `depends_on:`

```yaml
app:
  depends_on:
    - db
```

Tells Compose to start the `db` container before `app`. **Important caveat:** this only waits for the container to start, not for Postgres to actually be ready to accept connections. In Phase 5 you'll add health checks to close this gap. For now, `depends_on` is good enough for development.

## Service DNS — How Containers Talk to Each Other

Inside a Compose stack, every service gets a hostname equal to its name. The `app` container can reach the database at `db:5432`. The `nginx` container can reach PHP-FPM at `app:9000`.

No IP addresses. No `/etc/hosts` editing. Just service names.

```
Browser → localhost:8080
    ↓
Nginx container (hostname: nginx)
    ↓ fastcgi_pass app:9000
App container (hostname: app)
    ↓ pgsql:host=db;port=5432
Postgres container (hostname: db)
```

This is why your `.env` has `DB_HOST=db` and your `nginx/default.conf` has `fastcgi_pass app:9000`. The service names are the hostnames.

## Bind Mounts vs Named Volumes

| | Bind Mount | Named Volume |
|---|---|---|
| Storage location | Your host filesystem | Docker-managed |
| Survives `docker compose down` | Yes | Yes |
| Survives `docker compose down -v` | Yes | No (deleted) |
| Use case | Source code, config files | Database data |
| Real-time sync | Yes | N/A |

For source code: use a bind mount so edits appear instantly without rebuilding.  
For Postgres data: use a named volume so data persists between restarts.

## Daily Compose Commands

```bash
# Start the stack (foreground, shows all logs)
docker compose up

# Start the stack in background
docker compose up -d

# Stop everything (containers removed, volumes kept)
docker compose down

# Stop and delete all volumes (fresh database)
docker compose down -v

# View logs from all services
docker compose logs

# Follow logs from a specific service
docker compose logs -f app

# Run a command inside a running container
docker compose exec app sh
docker compose exec db psql -U postgres -d dalt_php_app

# Rebuild the app image after Dockerfile changes
docker compose build app

# Rebuild and restart
docker compose up --build
```

## How DALT.PHP Connects to Postgres in Docker

When you run `docker compose up`, Compose:

1. Starts `db` first (Postgres)
2. Starts `app` (PHP-FPM) — reads `.env`, builds a `pgsql:host=db;port=5432;dbname=dalt_php_app` DSN
3. Starts `nginx` (web server) — forwards HTTP to `app:9000`

**Migrations do not run by themselves.** Nothing in the boot path applies them: the container registration only builds a `Database`, and the first request that queries a missing table fails with a `PDOException` naming it. After a fresh stack — or any time you have run `docker compose down -v` and thrown the volume away — run them yourself:

```bash
docker compose exec app php artisan migrate
```

This is the single most common "the stack is up but the site is broken" cause. The symptom is an exception about a relation that does not exist, not a connection error.

## Checkpoint

Answer from memory:

1. Explain how the `app` container finds the database without any IP address or `ports:` entry.
2. State which of your three services needs a `ports:` entry and why the other two do not.
3. Explain why the source code is a bind mount but the Postgres data is a named volume.
4. The nginx service mounts the application code even though PHP runs elsewhere. Explain what breaks without it, and why pages still appear to load.
5. The stack is up and the site returns an exception naming a missing relation. State the cause and the command that fixes it.
6. Explain the difference between `docker compose down` and `docker compose down -v`, and when the second is what you want.

## Your Task

Load the incomplete `docker-compose.yml` into your project:

```bash
php artisan challenge:start docker-compose-missing-services
```

The file has the `app` service but is missing `db` and `nginx`. Add them using what you learned above, then verify:

```bash
php artisan challenge:verify
```

After verifying, try the full stack:

```bash
docker compose up
```

Visit `http://localhost:8080`. You should see the DALT welcome page served by Nginx, run by PHP-FPM, with Postgres running in the background.

## Common Issues

### "Can't connect to Postgres" from the app
Check `DB_HOST=db` in `.env` — not `localhost`. Inside Docker, the hostname is the service name.

### Database data disappears on every restart
You're not mounting a named volume. Add `pgdata:/var/lib/postgresql/data` to the `db` service.

### Nginx returns 502 Bad Gateway
PHP-FPM isn't running or `fastcgi_pass app:9000` points to the wrong service name.

### Port 8080 is already in use
Change the host port: `"8081:80"`.

## Summary

- `docker-compose.yml` describes your entire stack in one file
- `build:` builds from Dockerfile; `image:` pulls from Docker Hub
- `ports:` exposes containers to your host; containers talk to each other by service name without it
- `volumes:` — bind mounts sync host files; named volumes persist database data
- `env_file:` injects `.env` into a container's environment
- `depends_on:` controls startup order (not readiness)
- `docker compose up` starts everything; `docker compose down` stops it

## Next Step

**Lesson 09: PostgreSQL First Contact** — your Compose stack is running. Now connect to Postgres directly, explore it with `psql`, and write your first raw SQL queries.
