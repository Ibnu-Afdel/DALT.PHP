# FS10.1 — Containers around the application

Lesson ID: FS10.1
Title: Containers around the application
Part: 10 — Docker
Order: 1
Status: Published
Estimated effort: 90–120 minutes
Difficulty: Integration
Prerequisites: FS09.2 — Build pipeline, configuration and failure boundaries
Project milestone: B10 — Containerized full stack
Primary source dossier: DOCKER_DOCS.md
Last reviewed: 2026-08-15

## Why this matters

A host-installed frontend, PHP server, and PostgreSQL database can work while hiding the assumptions that make it work: a locally installed extension, an unused port, a database on `localhost`, or files from an earlier run. Containers make those assumptions explicit. They are not deployment magic. They package a process and its declared environment; Compose describes the several processes that make one application. The issue tracker is the useful subject because it already has a browser build, API behavior, and persistent data worth preserving.

## Before you start

Required:
- FS09.2 — Build pipeline, configuration and failure boundaries

Recommended first:
- Run the B09 frontend and DALT API locally once; know which command serves each.

Going deeper in DALT Core — optional:
- Docker basics, Dockerfile, and Docker Compose are optional Core reading. They are not prerequisites.

Confirm Docker Engine and the Compose plugin are available before starting this part.

```sh
docker version
docker compose version
docker compose config
```

## By the end

- distinguish an image, container, and Compose service;
- explain host ports, container ports, networks, service DNS, and `localhost` precisely;
- write a small Dockerfile and Compose model for the issue tracker;
- keep PostgreSQL data in a named volume and configuration outside an image; and
- inspect rendered Compose configuration before running it.

## Predict before reading

If PHP in an `app` container uses `localhost:5432`, which process will it contact? If a database container is removed and recreated, which data survives? If Compose publishes `8080:80`, which port does the browser use and which port does the web server listen on? Write answers, then test them.

## Mental model

```text
Dockerfile + build context ──build──> immutable image
image + runtime config ──run──> container: one isolated process environment

browser ─host port──> web/app service ─service DNS──> db service
                                  \                 /
                                   Compose network
db data ─────────────────────── named volume ───────┘
```

An image is a built filesystem and metadata, not a running machine. A container is one running instance of that image with a writable layer and runtime configuration. It is closer to an isolated process than a tiny virtual machine. A Compose service is the declared role from which Compose creates containers. Keep these nouns separate: doing so makes logs, ports, data loss, and rebuilds readable.

## 1. Start with a process, not a box

The application service needs a command, a working directory, source or built assets, and non-secret runtime configuration. A database service needs the PostgreSQL image, initial configuration, and persistent storage. They do not need a second database hidden inside the application image.

```dockerfile
FROM php:8.4-cli
WORKDIR /app
COPY . .
CMD ["php", "artisan", "serve", "0.0.0.0", "8000"]
```

`0.0.0.0` means the process accepts connections on container interfaces. Binding only to `127.0.0.1` inside the container often makes a published port look broken: Docker can forward traffic, but the process refuses it. `artisan serve` takes **positional** arguments (`serve [host] [port]`), not flags — `--host=0.0.0.0` would be read as the host itself, fail validation, and exit before binding anything. Run the exact `CMD` locally first (`php artisan serve 0.0.0.0 8000`) so a typo shows up before it is baked into an image. This teaching Dockerfile is intentionally small; FS10.2 makes the copy strategy and runtime image deliberate.

```sh
docker build -t issue-tracker-app:local .
docker run --rm -p 8000:8000 issue-tracker-app:local
curl -i http://localhost:8000
```

The left side of `-p` is a host port; the right side is the container port. A port declaration does not start a server and does not make it public by itself. It maps traffic only when a process listens.

## 2. Compose describes relationships

Compose replaces a fragile list of terminal commands with one inspectable declaration. Service names are DNS names on the default Compose network. Therefore the PHP service reaches PostgreSQL at `db`, not at the host machine's `localhost`.

```yaml
services:
  app:
    build: .
    ports: ["8000:8000"]
    environment:
      DB_DRIVER: pgsql
      DB_HOST: db
      DB_PORT: "5432"
      DB_NAME: issue_tracker
      DB_USERNAME: issue_tracker
      DB_PASSWORD: development-only-change-me
    depends_on: [db]
  db:
    image: postgres:17.7
    environment:
      POSTGRES_DB: issue_tracker
      POSTGRES_USER: issue_tracker
      POSTGRES_PASSWORD: development-only-change-me
```

`config/database.php` reads `DB_DRIVER`, and it defaults to `sqlite` when nothing sets it.
`DB_HOST` and `DB_PORT` are inert under that default — they simply go unread — so a Compose
file that sets only those two still leaves the application writing to a local SQLite file
inside the container while believing it configured PostgreSQL. Set `DB_DRIVER` explicitly,
in the same block as the connection details it governs, or a later `migrate` can print
success without the `db` service ever having been touched.

```text
host browser: localhost:8000
app container: 0.0.0.0:8000
app's localhost: the app container itself
app to database: db:5432
database container: 5432, normally not published to host
```

The browser may use a published host port. Containers do not need host ports to communicate with each other. Avoid publishing PostgreSQL unless a host tool genuinely needs it; publishing is an exposure decision, not a requirement for service-to-service traffic.

## 3. Persistent data needs an explicit owner

Container writable layers are disposable. PostgreSQL stores its cluster below its data directory, so the database service needs a named volume. A named volume outlives a replaced database container until you deliberately remove it. It is not a backup and it is not source control.

```yaml
services:
  db:
    image: postgres:17.7
    volumes:
      - postgres-data:/var/lib/postgresql/data
volumes:
  postgres-data:
```

```sh
docker compose up -d db
docker compose exec db psql -U issue_tracker -d issue_tracker -c 'select 1'
docker compose down
docker compose up -d db
docker volume ls
```

Running `docker compose down -v` deliberately removes named volumes. Read that `-v` before using it: it is appropriate for a disposable local reset and destructive for data you meant to keep. Migrations describe schema evolution; a volume only preserves current bytes.

## 4. Build context is an input boundary

`docker build .` sends the directory represented by `.` as the build context. A `COPY . .` instruction can only copy from that context. The context can accidentally include `node_modules`, `.git`, local build output, or `.env`; large contexts slow builds and can leak material into an image.

```dockerignore
.git
node_modules
public/build
.env
.dalt/workspace
```

```sh
docker build --progress=plain -t issue-tracker-app:local .
docker image ls issue-tracker-app
docker compose config
```

`.dockerignore` is not access control for a secret copied elsewhere, and an environment file is not automatically secret because its name begins with a dot. Keep private runtime values outside browser bundles and outside committed configuration. Use a documented local environment file that the learner creates; do not bake its values into the image.

## 5. One topology, several valid implementations

The project may serve built React assets from the PHP-facing web service, or it may use a small dedicated web server only when that truly simplifies static files and API routing. Do not create a service merely to demonstrate Docker vocabulary. The non-negotiable model is a browser-visible application boundary, a DALT/PHP runtime boundary, PostgreSQL persistence, and documented request routing.

```text
browser → published web endpoint → built frontend assets
                              └── /api → DALT/PHP → db:5432
```

```sh
docker compose config --format json
docker compose up --build
docker compose ps
```

Use the rendered configuration to notice defaults, interpolation, port mappings, and volume names. YAML is input; the resolved Compose model is the thing Docker receives. Do not infer a service address from the project directory name or a remembered default.



## 6. Filesystems are separate until you connect them

Each container has its own writable filesystem layer. A file generated in the application container is not automatically present on the host or in a sibling web container. An image layer is also distinct from a running container's writable layer: rebuilding creates a new image; recreating a container starts from that image and its declared mounts. These differences explain why a manual edit inside a container disappears after recreation, while a database row survives recreation when its directory is mounted from a named volume.

```text
image layer: immutable build output
container layer: disposable writes for one container
named volume: Docker-managed persistent data
bind mount: a named host path made visible to a container
```

For development, a bind mount can make host source edits visible in a service. It also imports host operating-system and permission behavior, and it can hide files installed into the image at the mounted path. A named volume is the simpler first choice for database data because Docker owns its location and lifecycle. Choose a bind mount only when the learner can state why live host editing is worth that coupling.

```yaml
services:
  app:
    volumes:
      - ./:/app
      - app-vendor:/app/vendor
volumes:
  app-vendor:
```

Mounting the project over `/app` can hide vendor files installed during the image build, so a separate volume can own them. Before adopting this shape, inspect whether the project benefits more from a fast development loop or a production-shaped immutable runtime. Do not use a mount as a workaround for a Dockerfile you cannot explain.

```sh
docker compose exec app sh -lc 'pwd; ls -la; mount | head'
docker inspect issue-tracker-app-1 --format '{{json .Mounts}}'
docker volume inspect postgres-data
```

## 7. Networks are private roads, published ports are gates

Compose normally creates an application-specific network. Every service on it can address another service by its service name. That private network is enough for `app → db`; publishing a database port creates an additional host-facing gate. It may help a local SQL client, but it is not a requirement and should be an explicit development choice. Do not expose an internal service merely because a tutorial did.

```yaml
services:
  app:
    networks: [issue-network]
  db:
    networks: [issue-network]
networks:
  issue-network:
```

The explicit network above is useful only if you have a boundary to name; the default Compose network is valid for a small stack. A web service may publish `8080:80` while the app is un-published and reachable only through the web service's internal proxy. Or the application may publish `8000:8000` directly. In both designs, write the request path in the README and test it with a browser or `curl`.

```sh
docker network ls
docker network inspect issue-tracker_default
docker compose exec app getent hosts db
docker compose exec app sh -lc 'nc -zv db 5432'
```

DNS proves that a name resolves; a TCP check proves a port accepts a connection; neither proves credentials, schema, authorization, or issue-list behavior. Keep evidence scoped to the claim being made. This protects the course from the false green check of treating any open port as a working application.

## 8. Configuration chooses a deployment; it does not redesign it

Compose can interpolate values from the shell or an environment file, while `environment:` passes a resolved value into a container. These are separate moments. `docker compose config` shows the resolved model before a container exists. It can display values, so use a safe local environment while learning and do not paste real secret output into course notes.

```yaml
services:
  app:
    environment:
      APP_PORT: ${APP_PORT:-8000}
      DB_HOST: ${DB_HOST:-db}
```

```sh
APP_PORT=8080 docker compose config
docker compose config --environment
docker compose exec app printenv DB_HOST
```

An application should default only values that have a safe, documented local meaning. It should fail clearly for required credentials rather than silently connecting somewhere surprising. The same image can run in another environment with different runtime configuration, but a browser bundle with a different `VITE_*` value must be rebuilt. Do not blur those lifetimes just because both values appear near Docker commands.



## 9. Container lifecycle is part of the operational contract

A Compose command has a scope. `up` creates or starts services; `stop` stops their processes while retaining containers; `down` removes the project containers and network; `down -v` additionally removes named volumes. A rebuild changes an image, but a running container does not become the new image until it is recreated. These are lifecycle facts, not implementation trivia: they let a learner explain why a code change is absent, why a test database row remained, or why it vanished.

```text
source change → build a new image → recreate service → new process sees new image
database write → named volume → survives container replacement
docker compose down -v → named volume removed → data intentionally gone
```

Use the narrowest command that matches the intent. A local reset should be documented as destructive before it is run. A migration failure should not trigger volume removal, because schema and data diagnosis need the data that produced the failure. Conversely, tests that promise isolation must say whether they reset a database schema, a dedicated database, or a disposable volume.

```sh
docker compose up -d --build app
docker compose restart app
docker compose rm -sf app
docker compose up -d app
docker compose down --remove-orphans
```

Image names and generated container names are implementation details that can vary with the Compose project name. Prefer service-oriented commands such as `docker compose logs app` and `docker compose exec app` in learner documentation. When an inspection command requires an exact container name, first obtain it from `docker compose ps`; do not teach a guess as a stable address.

## 10. Compose configuration is reviewable infrastructure

Treat the Compose file as code with inputs, output, and behavior. Review whether every service has one ownership reason, whether ports are minimally exposed, whether volumes have an explicit lifecycle, whether configuration comes from a documented source, and whether health checks tell the truth. `docker compose config` normalizes the declaration; it is a fast review surface before the slower and stronger evidence of a real build and running system.

```sh
docker compose config --format json
docker compose images
docker compose top
docker compose logs --timestamps --tail=50
```

A healthy review asks questions rather than checking fashionable features. Does a separate `web` service actually serve built assets or route API requests? Does a volume protect state the project needs? Is a database port published solely for an intentional host workflow? Is an environment variable consumed by the correct service? Does a build context omit artifacts that cannot affect runtime? Record the answer close to the Compose file or README so the next maintainer can revise the system without rediscovering its reasons.

This is also why Part 10 has no automatic structural verifier for the learner's final Compose file. A file can contain strings named `healthcheck`, `volume`, and `network` without providing a working system. The honest evidence is configuration output, real containers, direct database/API behavior, and the learner's ability to repair a controlled failure.



## 11. Keep operational commands intentional

Compose command options carry operational meaning. `--build` asks Compose to build images before starting; `-d` detaches and therefore requires a later state/log inspection; `exec` runs in an existing service; `run --rm` makes a temporary one-off container. A learner who can choose among these commands can diagnose without turning every change into an uncontrolled reset.

```sh
docker compose up --build -d
docker compose exec app php artisan migrate
docker compose run --rm app php artisan test
docker compose logs --tail=100 app
docker compose down
```

Use the service name from the Compose file rather than a generated container name. Make destructive commands conspicuous in the README. The goal is a calm loop: inspect the resolved model, build declared inputs, start services, establish health and data, prove behavior, then stop with known persistence consequences.

## 12. Explain one failure before you fix it

Before applying a repair, state the claim that failed and the evidence that supports it: “the host port is already occupied,” “the rendered database hostname differs,” or “the named volume was deliberately removed.” This practice prevents a plausible command from being mistaken for a diagnosis.

```text
claim → evidence command → smallest corrective input → repeat the original check
```

This sequence leaves a useful record for teammates and prevents unrelated configuration changes from obscuring the boundary that actually failed.

## Try it

Create a temporary Compose file with only `db`, its named volume, and an explicit image tag. Start it, run the `psql` query, stop it, and start it again. Then run `docker compose config` and compare that rendered model with the YAML you wrote. Add a one-line note answering why an application container must use `db` rather than `localhost` for this database.

```sh
docker compose up -d db
docker compose ps
docker compose logs db
docker compose down
```

## Common mistakes

- Treating a container as a VM and installing fixes interactively instead of changing inputs.
- Using `localhost` from one service to reach another service.
- Publishing every port “just in case,” making boundaries less clear.
- Storing database data only in the container writable layer.
- Copying `.env` or an entire working tree into a build context without inspection.
- Calling a named volume a backup; it is persistent runtime state, not recovery strategy.

## When this goes wrong

First ask which namespace the failing name or port belongs to: browser host, app container, or database container. Then inspect the declared model before editing application code. A connection refusal to `localhost:5432` from the app usually says the address names the wrong machine; a failure after `down -v` says the data owner was deliberately removed. Use logs for process evidence and `docker compose config` for configuration evidence.

```sh
docker compose config
docker compose ps
docker compose logs --tail=100 app
docker compose exec app getent hosts db
```

## Exercise

### Goal

Model the issue tracker’s application and PostgreSQL as two services whose relationship is explicit.

**Mode: Manual, tool-backed evidence.** You run Compose, inspect normalized configuration, prove database DNS from the application container, and prove that a named volume survives a container reset.

### Starting state

Use the passing B09 project. Keep its existing frontend/API decisions; this exercise models the runtime around them and does not require a new feature or frontend framework.

### Requirements

- Declare an application service and PostgreSQL service with an exact PostgreSQL image version.
- Set the application database host to the Compose service name, not `localhost`.
- Give PostgreSQL a named volume and state the deliberate reset command.
- Publish only the port a host user needs and document both sides of the mapping.
- Add a `.dockerignore` that excludes generated, private, and irrelevant local inputs.

### Verification

Run `docker compose config`, `docker compose up -d`, `docker compose ps`, and a database query through `docker compose exec`. Stop and recreate the database service without `-v`, then show that test data remains. Record commands and actual output in project notes.

### Hints

<details><summary>Hint 1</summary>Within the default Compose network, a service name is a hostname.</details>

<details><summary>Hint 2</summary>Use a named volume, not a host-path mount, for the first database model.</details>

<details><summary>Hint 3</summary>Use <code>docker compose config</code> before debugging a value you expected Compose to interpolate.</details>

## In the project

This establishes B10’s first invariant: the issue tracker can name its application, database, network, configuration, and durable data boundaries. FS10.2 will make that model reproducible and health-aware.

## Closed-book checkpoint

1. What is the difference between an image, a container, and a Compose service?
2. Why is `localhost` usually wrong for app-to-database traffic in Compose?
3. Which side of `8000:8000` is the host port?
4. What survives `docker compose down`, and what extra flag changes that answer?
5. Why does the build context deserve an ignore file?

## Resources

### Read

- [Docker: What is a container?](https://docs.docker.com/get-started/docker-concepts/the-basics/what-is-a-container/)
- [Docker: Multi-container applications](https://docs.docker.com/get-started/workshop/08_using_compose/)
- [Docker Compose networking](https://docs.docker.com/compose/how-tos/networking/)

### Reference

- [Docker volumes](https://docs.docker.com/engine/storage/volumes/)
- [Docker build context](https://docs.docker.com/build/concepts/context/)

## You are done when

- [ ] You can name every process and network boundary in the Compose model.
- [ ] The application reaches PostgreSQL by service DNS and the database has durable named storage.
- [ ] You can explain the published host port without confusing it with the container port.
- [ ] `docker compose config` and a real database query provide evidence for your model.

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/DOCKER_DOCS.md` §§1–7 and `FSO_CONTAINERS.md`.
- Official sources: Docker container, Compose networking, volumes, build-context pages linked above.
- Versions: examples use Docker Compose plugin syntax and PostgreSQL image `17.7`; verify supported Engine/Compose versions before freeze.
- Consulted: 2026-08-15.
- Curriculum authority: `docs/dalt-fullstack/CURRICULUM.md` §21, FS10.1.
- DALT files inspected: `package.json`, `vite.config.mjs`, `framework/Core/functions.php`, and B09 specification.
- Laravel source: not applicable; Laravel may run in a container but does not alter Docker networking semantics.
