> **What exists when you finish:** a reproducible Docker Compose environment for the issue tracker, with an intentional application/web topology, DALT/PHP, PostgreSQL persistence, documented configuration, meaningful health evidence, and commands a fresh developer can use to build, run, migrate, test, and debug the system.

## What you are building

Turn your passing B09 application into a declared multi-service environment. The finished result has the smallest honest topology for your project: a browser-visible web/application endpoint, DALT/PHP behavior, and PostgreSQL with a named persistent volume. A separate frontend build or web-server service is allowed only when it serves a real purpose in the asset/API routing design; do not add services as Docker vocabulary demonstrations.

The Compose file is a model, not a replacement application. Preserve B06’s direct API behavior tests and B09’s deliberate frontend boundaries. Keep learner source outside `.dalt`, `framework/`, `config/`, and `public/`; deleting `.dalt` must still leave the framework and learner app working.

## Why this milestone exists

A local application often works because the author’s machine supplies unrecorded facts: database address, PHP extensions, Node version, port availability, installed packages, old database rows, and an already-running process. B10 makes the important facts inspectable. An image captures build inputs; a container runs a process from those inputs; Compose makes service relationship, configuration, network, storage, and health visible.

This is not “put everything in Docker” as an end in itself. The learner should be able to explain why the application connects to `db` rather than `localhost`, why a database volume survives a replaced container, why a process being started is weaker than a meaningful health check, and which values may enter a browser build. Part 11 needs exactly this reproducible PostgreSQL environment to study data and queries honestly.

## Before you start

Complete FS10.1 and FS10.2. Begin from a B09 project whose backend and frontend checks pass. Identify what currently serves assets, what serves API requests, how PostgreSQL is reached, and which command runs migrations.

```sh
php artisan test
npm run typecheck
npm run lint
npm run test
npm run build
docker version
docker compose version
```

Create configuration documentation and a local ignored environment file before putting values in Compose. Use exact image tags. Do not commit real credentials, do not put secrets under `VITE_*`, and do not claim `vite preview` is production routing.

## Stage 1 — Draw the runtime before writing Dockerfiles

Write a compact request-and-data path in the project README or architecture note. Name the browser-facing endpoint, asset server, API endpoint, DALT/PHP runtime, database service, port mappings, Compose DNS name, and named volume. Decide whether built React assets are served through the application-facing server or a justified small web service. State exactly how `/api` reaches DALT.

**Working looks like:** another developer can read the note and distinguish host port, container port, service DNS, and persisted data without inspecting source code.

**Check it yourself:** from inside the application service, resolve the database service name. From the host, open only the documented browser-facing port. Confirm that an application-service `localhost` is not being used as the database host.

```sh
docker compose config
docker compose exec app getent hosts db
docker compose ps
```

## Stage 2 — Build the application intentionally

Add at least one application Dockerfile. Give it a clear work directory, deterministic dependency installation from the lockfile, an intentional copy order, and only non-secret build inputs. Add a `.dockerignore` for private files, generated directories, Git metadata, and irrelevant workspace output. Make any frontend build/runtime split answer a real need: Node can build static assets while a smaller runtime serves them, but a multi-stage build is not a merit badge.

**Working looks like:** a repeated build reuses dependency work when its manifests have not changed, a source-only change rebuilds later work, and the image does not contain a copied local environment file.

**Check it yourself:** build twice with plain progress, then change one source file and compare cache behavior. Inspect the image history and confirm that configuration is supplied at runtime rather than copied into an image layer.

```sh
docker compose build --progress=plain
docker compose build --progress=plain
docker image history issue-tracker-app:local
```

## Stage 3 — Compose the services and persistence

Declare the actual services, their exact images/builds, network relationships, runtime environment, and PostgreSQL named volume. Publish only the browser-facing port unless host database access is intentionally needed. Set DALT’s database hostname to the Compose service name. Document the difference between stopping services and deliberately removing the named volume; a volume is durable runtime data, not a backup.

**Working looks like:** the database container can be replaced without losing a deliberately inserted test row, while the documented reset command can intentionally remove it.

**Check it yourself:** run a database query through `docker compose exec`, stop services without `-v`, start again, and query the row. Read the output of `docker compose config` rather than assuming interpolation is correct.

```sh
docker compose up -d db
docker compose exec db psql -U issue_tracker -d issue_tracker -c 'select now()'
docker compose down
docker compose up -d db
```

## Stage 4 — Make startup and health observable

Add a useful PostgreSQL health check, such as `pg_isready` with the configured user and database. Use health-aware dependency conditions only where the supported Compose version and actual startup design make them appropriate. Do not solve startup ordering with an arbitrary sleep. Decide explicitly how migrations run: a documented one-off command, or an intentional bootstrap mechanism whose retries and failures are visible.

**Working looks like:** `docker compose ps` reports health information, the application starts only after the database has meaningful readiness evidence where that dependency exists, and migrations do not silently race the database.

**Check it yourself:** stop the database; inspect app and database state/logs; restore the database; run migrations; then make one request or direct API test that reads real persisted data. Explain why “running” and “ready for this behavior” are different claims.

```sh
docker compose ps
docker compose logs --tail=150 app db
docker compose exec db pg_isready -U issue_tracker -d issue_tracker
docker compose exec app php artisan migrate
```

## Stage 5 — Prove the complete system from a clean copy

In a new copy of the learner project, follow the README exactly: configure local values, build/start Compose, migrate, open the browser flow, run backend tests, run frontend checks where their tooling belongs, and stop cleanly. Keep the expected commands short and ordered. A containerized system is not proven by `docker compose up` alone; test the behavior users and maintainers actually need.

**Working looks like:** the documented path succeeds without relying on files, images, or a database from the original working directory.

**Check it yourself:** compare command output with README claims. If a command requires an image name, service name, port, environment value, or migration precondition that was not documented, fix the documentation rather than asking a future reader to infer it.

```sh
docker compose up --build -d
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose logs --tail=100
docker compose down
```

## Stage 6 — Practice six evidence-led repairs

Perform controlled failures one at a time and restore the correct model. Use a temporary port conflict, a wrong database host or port, a changed non-secret environment value, a stopped database health dependency, a deliberately removed local volume only when the data is disposable, and one build-input failure. Start each diagnosis with rendered Compose configuration, then container state and logs. Do not “repair” a running container interactively.

**Working looks like:** each failure has evidence that points to a boundary: host port, configuration, health, DNS/connection, volume lifecycle, or build context/instruction.

**Check it yourself:** write one sentence per failure naming the first command that produced evidence and the change that restored the system. Remove temporary broken values and verify the healthy path again.

```sh
docker compose config
docker compose ps --all
docker compose logs --tail=200
docker compose exec app getent hosts db
docker compose down
```

## Decisions you have to make

- Does the project need a separate web service, or can the chosen application-facing server serve built assets honestly?
- Which host ports must be published, and which services should remain private to the Compose network?
- What exact condition means PostgreSQL is ready for your application?
- How do migrations run reproducibly without a timing sleep?
- Which values are public build-time browser values, which are non-secret runtime configuration, and how are local secrets supplied?
- Does a multi-stage image reduce a real runtime need, or would it add indirection?
- Which process can run as a non-root user, and what documented base-image constraint applies if one cannot?

## Acceptance criteria

Nothing here is checked automatically. Read every item against software you actually built and ran.

- [ ] Compose declares the smallest honest set of app/web and PostgreSQL services.
- [ ] Every image tag is exact and every service, port, network relationship, and volume has a stated purpose.
- [ ] Browser-to-assets/API and DALT-to-database request paths are documented.
- [ ] Application-to-database traffic uses Compose service DNS, not container `localhost`.
- [ ] PostgreSQL data uses a named volume and its deliberate reset behavior is documented.
- [ ] An application Dockerfile has deterministic dependency installation, a clear workdir, intentional copy order, and non-secret inputs.
- [ ] `.dockerignore` excludes private and irrelevant build context material.
- [ ] A multi-stage build exists only if it improves the actual frontend/runtime boundary.
- [ ] A meaningful health check and supported health-aware dependency strategy replace arbitrary sleeps.
- [ ] Migrations are explicitly documented and succeed against the Compose database.
- [ ] Client-visible `VITE_*` configuration remains harmless; runtime/private values are not in images or committed environment files.
- [ ] The runtime runs non-root where practical, or the documented constraint is specific and reviewed.
- [ ] From a clean copy, documented setup, build, start, migration, test, and browser/API commands succeed.
- [ ] You diagnosed and restored a port conflict, failed health check, database failure, wrong variable, missing-volume case, and build failure from evidence.
- [ ] B06 direct API tests still prove authentication, CSRF, membership, and ownership independently of the UI.
- [ ] Deleting `.dalt` would leave framework and learner application working.

## Prove it to yourself

Close the lessons. Draw the path of an issue-list request from browser host port through the selected web/application service, API route, DALT process, Compose DNS, and PostgreSQL volume. Mark which layer owns each value: browser build-time configuration, Compose runtime configuration, and secret delivery. Then draw the debugging path for a connection failure: rendered configuration, state, logs, DNS, process, and data. Finally explain why removing a container is not necessarily deleting data, and why a healthy database is necessary but may not be sufficient for a migrated application.

## What this unlocks

Part 11 has a repeatable PostgreSQL environment with real application data and a known connection path. You can now study query plans, indexes, search, concurrency, and tenant isolation without assuming every learner’s personal machine behaves the same way.

