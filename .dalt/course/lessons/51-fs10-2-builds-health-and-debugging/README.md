# FS10.2 — Builds, health and debugging

Lesson ID: FS10.2
Title: Builds, health and debugging
Part: 10 — Docker
Order: 2
Status: Published
Estimated effort: 100–130 minutes
Difficulty: Integration
Prerequisites: FS10.1 — Containers around the application
Project milestone: B10 — Containerized full stack
Primary source dossier: DOCKER_DOCS.md
Last reviewed: 2026-08-15

## Why this matters

A container that starts is not necessarily an application that is ready. It may be building assets, waiting for PostgreSQL, using a wrong environment variable, or serving a process that immediately fails after startup. Docker becomes useful when its build inputs, health signal, dependencies, and diagnostic commands turn those possibilities into evidence. This lesson moves the issue tracker from “it works on my machine” to a repeatable system a new developer can inspect and repair.

## Before you start

Required:
- FS10.1 — Containers around the application

Recommended first:
- Keep the B09 commands and public-versus-runtime configuration note nearby.

Going deeper in DALT Core — optional:
- Core Docker production material is optional reading, not a gate. This lesson supplies the Docker knowledge needed for Fullstack.

Start by inspecting, not deleting, current Docker resources. The final topology should solve the project’s actual serving and API-routing needs.

```sh
docker compose config
docker compose ps --all
docker compose logs --tail=100
```

## By the end

- explain image layers, cache invalidation, build context, and a deliberate copy order;
- use a multi-stage build only where build and runtime needs differ;
- distinguish started, healthy, and ready enough for a dependent service;
- use logs, inspect, exec, and Compose configuration to diagnose a failure; and
- document clean-machine commands, runtime configuration, and a safe non-root posture.

## Predict before reading

A database process has opened its port but migrations have not run. Is the application ready to serve an issue list? If you change application source after `COPY . .`, which Dockerfile steps can Docker reuse? If a health check runs inside the database container, what does its `localhost` name? Predict first; then inspect evidence rather than relying on command order.

## Mental model

```text
dependency manifests ──> install layer ──> application source ──> runtime image
                 cache reused when its input has not changed

created → running → health command reports healthy
                         ↓
               dependent app starts when useful evidence exists

failure → rendered config → container state → logs → network → process → data
```

Docker caches build layers. A Dockerfile is therefore both a recipe and an invalidation graph: changing a layer invalidates it and layers after it. Compose `depends_on` expresses a relationship, but simple start order does not prove readiness. A health check gives Docker a repeated command and status; it must test a meaningful condition, not merely prove that a shell exists.

## 1. Structure a build around changing inputs

Copy dependency manifests before application source when the dependency installation is deterministic. That lets an unchanged lockfile reuse the expensive install layer while a source edit rebuilds only later layers. The exact PHP dependency command belongs to the learner’s real project; never invent a package manager simply to demonstrate caching.

```dockerfile
FROM php:8.4-cli AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist

FROM php:8.4-cli AS runtime
WORKDIR /app
COPY --from=dependencies /app/vendor ./vendor
COPY . .
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

```sh
docker build --progress=plain -t issue-tracker-app:local .
docker build --progress=plain -t issue-tracker-app:local .
docker image history issue-tracker-app:local
```

A second identical build may show cached steps. Change one application file and observe which steps rerun. Change the lockfile only when a dependency actually changes. Do not copy credentials into a dependency layer: layers can be inspected and reused, so a later `rm` does not make an earlier copied secret absent from image history.

## 2. Separate build and runtime when it buys something

React source requires Node tooling to produce browser assets; serving those built assets does not necessarily require Node. A multi-stage build can use a Node build stage and copy only `dist` or the project’s chosen output into a runtime image. It is appropriate when it reduces runtime tools or makes the output boundary explicit. It is not mandatory if the chosen architecture already has a justified asset-serving path.

```dockerfile
FROM node:22.21.1-alpine AS frontend-build
WORKDIR /frontend
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build
```

```dockerfile
FROM nginx:1.29.4-alpine
COPY --from=frontend-build /frontend/dist /usr/share/nginx/html
EXPOSE 80
```

```sh
docker build -f Dockerfile.web -t issue-tracker-web:local .
docker run --rm -p 8080:80 issue-tracker-web:local
curl -I http://localhost:8080
```

The output path must match the learner’s actual Vite configuration. A containerized production web service cannot rely on the development proxy. If a web server routes `/api`, make that rule visible and test it. If PHP serves assets itself, document that instead. Both can be honest; an unexplained proxy is neither.

## 3. Health is a question with a useful answer

A process can be running while unable to serve the behavior the next service requires. PostgreSQL’s health check should execute a database-aware command such as `pg_isready`; the application’s check should exercise a small route or command that has the necessary dependencies. Avoid an arbitrary sleep: it encodes timing rather than observable readiness.

```yaml
services:
  db:
    image: postgres:17.7
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U $$POSTGRES_USER -d $$POSTGRES_DB"]
      interval: 5s
      timeout: 3s
      retries: 10
  app:
    depends_on:
      db:
        condition: service_healthy
```

```sh
docker compose up -d
docker compose ps
docker inspect --format '{{.State.Health.Status}}' issue-tracker-db-1
docker compose logs db
```

Compose implementations and application bootstrap needs determine whether a health condition is appropriate; check the supported Compose version and rendered configuration. Even a healthy database does not automatically mean migrations or seeds have happened. Treat migrations as an explicit documented step or an intentionally designed startup operation, never a silent race.

## 4. Configuration has a lifetime and an audience

An image should be reusable across environments. Runtime environment variables choose its database address, public base URL, and other deployment-specific values. Browser configuration is different: `VITE_*` values are embedded at build time and visible to everyone who downloads assets. Runtime secrets belong outside the Dockerfile, image, Git history, and browser bundle.

```yaml
services:
  app:
    environment:
      APP_ENV: production
      DB_HOST: db
      DB_PORT: "5432"
    env_file:
      - .env.compose.local
```

```env
# .env.compose.local — created locally, never committed
POSTGRES_PASSWORD=replace-this-local-value
SESSION_SECRET=replace-this-local-value
```

```sh
docker compose --env-file .env.compose.local config
git check-ignore .env.compose.local
docker compose exec app env | sort
```

Do not show secrets in screenshots, copied terminal output, issue descriptions, or logs. A development password is still a password and should have an obvious replacement path. Production secret delivery is an operations concern with a different mechanism; do not claim an `env_file` is a production secrets vault.

## 5. Debug from evidence, in order

When the system fails, first render the Compose model. Then ask whether the container exists, whether its process remains running, what its logs say, whether health is failing, whether it can resolve and connect to dependencies, and whether expected data exists. Changing Dockerfile lines before knowing which layer failed makes debugging slower and hides the original evidence.

```sh
docker compose config
docker compose ps --all
docker compose logs --tail=200 app db
docker compose exec app sh
docker compose exec app getent hosts db
docker compose exec db psql -U issue_tracker -d issue_tracker -c 'select now()'
```

```text
port conflict       → host binding error before service starts
bad environment     → rendered config differs from expected runtime value
database failure    → DNS, port, credentials, health, then query evidence
failed build        → build output identifies instruction and context input
missing volume      → inspect declared mounts and deliberate reset history
```

`docker compose exec` requires a running container; use `docker compose run --rm` for an intentional one-off command when appropriate. Prefer a narrow command that answers one question over opening an interactive shell and making invisible repairs.



## 6. Read cache output as an explanation of inputs

Docker's cache is reliable only when a layer's instruction and inputs are unchanged. The useful consequence is not “make builds fast at any cost”; it is that the Dockerfile documents what dependency installation depends on. Copying `composer.json` and `composer.lock` before source says package installation depends on those manifests, not on every component edit. Copying the whole project first says the opposite, so every source edit invalidates the expensive install step.

```dockerfile
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist
COPY app ./app
COPY routes ./routes
```

```text
change README.md      → ideally no relevant runtime layer changes
change PHP source     → source copy and later layers rebuild
change composer.lock  → install layer and later layers rebuild
change .dockerignore  → context contents may change; inspect the build
```

Do not make an over-specific copy list only to impress a cache benchmark: a new source directory that is not copied becomes a runtime failure. The right structure reflects stable project boundaries and has a testable result. Run the same build twice, then alter a source input and a dependency input separately. If cache behavior surprises you, inspect the instruction and context rather than deleting Docker's cache as a first reflex.

```sh
docker builder du
docker build --no-cache --progress=plain -t issue-tracker-app:clean .
docker image inspect issue-tracker-app:clean
```

## 7. Health checks must represent the next useful action

A health check has an observer, a command, timing policy, and a meaning. `pg_isready` is a useful database process/readiness signal, but it does not prove the application's migrations have run. An HTTP health endpoint may prove the web process can respond, but if it bypasses the database it does not prove an issue list can load. State what each health check proves and keep a behavior test for the stronger claim. The purpose is an honest dependency boundary, not a green badge in `docker compose ps`.

```yaml
services:
  app:
    healthcheck:
      test: ["CMD", "php", "artisan", "health:check"]
      start_period: 15s
      interval: 10s
      timeout: 3s
      retries: 5
```

This is a shape, not a command the learner should add blindly; inspect whether DALT actually has such a command or implement a deliberately small, safe endpoint as part of their project. Never make a health route disclose configuration, credentials, user records, or stack traces. A failing health check must be diagnosable through logs and command output, otherwise it only creates a new opaque failure.

```sh
docker inspect --format '{{json .State.Health}}' issue-tracker-app-1
docker compose exec app php artisan health:check
docker compose logs app
```

## 8. Non-root is a boundary, not a slogan

Root inside a container is not the same as root on every host, but it is still broader authority than a typical application process needs. If the image and runtime permit it, create or select an unprivileged user, make application files readable and writable only as required, then use `USER` for the runtime command. Binding to privileged ports and package installation are common reasons build steps need root; they do not automatically justify the long-running process remaining root.

```dockerfile
RUN addgroup --system app && adduser --system --ingroup app app
COPY --chown=app:app . /app
USER app
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

```sh
docker compose exec app id
docker compose exec app sh -lc 'touch /app/permission-check'
docker compose exec app sh -lc 'rm /app/permission-check'
```

Some base images supply an existing user or need a different permission strategy. Record the actual choice, test that the process can write only what it must, and avoid turning a permission failure into `chmod -R 777`. Container security also includes narrow exposed ports, small build contexts, pinned base images, and no secrets in layers; no single `USER` line is a complete security model.

## 9. Treat the clean-machine run as an integration test

A clean-machine run is the most valuable B10 evidence because it rejects accidental host dependencies. It must begin with documented prerequisites, then use the stated configuration file, build, start, migration, and test commands. Images and volumes from a previous experiment can hide a missing copy instruction or bootstrap step, so repeat this evidence from a separate project copy or after an intentional local reset whose destructive scope you understand.

```text
clone → create ignored local configuration → compose config
      → compose up --build → wait for stated health
      → migrate → test API/frontend behavior → open browser
      → collect logs/state only if a step fails
```

A green `docker compose up` only proves that Compose started containers. A successful migration proves a database path and credentials. A passing direct API test proves application behavior that a browser shell cannot. A browser check proves the selected public routing. Keep these facts separate in the README so the next developer knows what each command establishes.

```sh
docker compose up --build -d
docker compose exec app php artisan migrate
docker compose exec app php artisan test
curl -i http://localhost:8000/api/issues
docker compose down
```



## 10. Logs are a timeline, not a substitute for a model

Logs are evidence emitted by a process at a time. Use timestamps and a bounded tail to correlate the database becoming ready, an application retrying a connection, a migration running, and the first successful request. But logs do not replace configuration inspection: an application can log the address it attempted without proving that Compose supplied the intended variable. Pair a log question with a state or configuration question.

```sh
docker compose logs --timestamps --tail=100 db app
docker compose ps --all
docker compose config
docker compose top app
```

A good application log line identifies the event and safe operational context: service action, route category, status, or error class. It does not print passwords, session identifiers, CSRF material, request bodies, or database connection strings containing credentials. If the container exits before logs can be followed, ask Docker for its exit state and then rebuild the smallest reproduction. Do not restart repeatedly until the evidence scrolls away.

```sh
docker inspect --format 'exit={{.State.ExitCode}} error={{.State.Error}}' issue-tracker-app-1
docker compose logs --no-log-prefix --tail=200 app
docker compose run --rm app php artisan --help
```

## 11. Development and production images answer different questions

A development service may mount source and run a watcher so an edit is visible quickly. A production-shaped service should consume built output, have a deterministic command, and avoid source mounts that hide image contents. These are intentionally different workflows. Do not use a development image as proof of production routing, and do not force a production image to mimic hot reload when that makes its runtime ambiguous.

```text
development: host edit → bind mount/watcher → quick feedback
production-shaped: commit inputs → build artifact → immutable runtime → explicit configuration
```

Keep configuration examples explicit about their audience. A local Compose override can support a developer workflow without changing the production-shaped base model, but it should not replace the clean-machine instructions. When the two configurations differ, document the reason: source mounting, debugging port, or local tool access are plausible; different application behavior is a warning sign.

```yaml
services:
  app:
    profiles: ["development"]
    volumes:
      - ./:/app
```

Profiles and override files are optional tools, not requirements for this milestone. Choose the smallest configuration set a new learner can run and understand. The durable outcome is a reproducible system and a clear explanation of why each variant exists.

Before changing a runtime variant, record the evidence it is meant to improve: edit feedback, asset serving, or a controlled diagnostic workflow. If it changes authorization, database ownership, or API behavior, it is no longer merely a development configuration and needs its own behavior evidence.

## Try it

Rebuild after changing one source file, then after changing one dependency manifest. Observe the cache difference in plain build output. Add a database health check, stop the database, and use `docker compose ps` plus logs to explain the application’s behavior. Restore the service rather than adding a sleep. Finally, use `docker compose config` to prove a deliberately changed non-secret variable reaches the intended service.

```sh
docker compose build --no-cache
docker compose up -d
docker compose logs --follow app
docker compose down
```

## Common mistakes

- Treating `depends_on` as proof that the database is ready.
- Using `sleep 10` as a startup strategy.
- Copying all source before dependency manifests and then wondering why every build reinstalls.
- Using a multi-stage build solely to satisfy a checklist.
- Putting a secret in a Dockerfile argument, image layer, or `VITE_*` variable.
- Fixing a running container manually instead of changing the Dockerfile or Compose inputs.
- Declaring a health check that can succeed while the required behavior remains unavailable.

## When this goes wrong

A failed build is build evidence, not a database problem: read the named Dockerfile instruction and check its context. An exited app is process evidence: read its logs before running `exec`. An unhealthy database is health evidence: inspect the health log and try the same narrow command manually inside the service. A browser connection refusal often starts at the host port mapping, while an API 500 after the browser connects starts at application logs and runtime configuration.

```sh
docker compose events --json
docker inspect issue-tracker-app-1
docker compose logs --tail=200 --timestamps
docker compose exec db pg_isready -U issue_tracker -d issue_tracker
```

## Exercise

### Goal

Make the B10 Compose environment reproducible, health-aware, and diagnosable from a clean checkout.

**Mode: Manual, tool-backed evidence.** The learner runs real Docker builds and containers, records actual configuration and health output, and performs controlled failure-and-repair checks. This lesson does not present a static YAML inspection as runtime proof.

### Starting state

Use your FS10.1 Compose model and passing B09 application. Preserve the existing tests and source boundaries; Docker packages known behavior rather than replacing it.

### Requirements

- Arrange one application Dockerfile with deterministic dependency installation and an intentional copy order.
- Use a build/runtime split only if the frontend or runtime actually benefits from it.
- Add a meaningful database health check and a health-aware dependency where supported.
- Document configuration values, which are public, which are runtime-only, and how local secrets are supplied safely.
- Write an evidence-first debugging guide for a port conflict, failed health check, database connection failure, wrong variable, missing volume, and build failure.
- Ensure the runtime user is non-root where the selected base image and process permit it; document any exception.

### Verification

From a clean project copy, run the documented setup, `docker compose up --build`, migration command, test command, and a browser/API check. Deliberately use a wrong database host or port, observe the specific failure in rendered configuration/logs, restore it, and prove the healthy system. Record actual output, including `docker compose ps`.

### Hints

<details><summary>Hint 1</summary>Copy lockfiles before source so Docker can reuse the install layer.</details>

<details><summary>Hint 2</summary>A database health command must test database readiness, not merely container existence.</details>

<details><summary>Hint 3</summary>Begin every incident with <code>docker compose config</code>; it prevents debugging an imagined configuration.</details>

## In the project

B10 makes the learner’s full stack runnable as a declared system. Part 11 can now use the same PostgreSQL service for realistic data, query plans, transactions, and isolation rather than a personal host setup.

## Closed-book checkpoint

1. Why does Dockerfile instruction order affect rebuild time?
2. When does a multi-stage build improve the issue tracker, and when is it unnecessary?
3. Why can a running service be unready?
4. What is the difference between a Compose environment value and a Vite client value?
5. In what order would you investigate an app that cannot connect to PostgreSQL?
6. Why is a startup sleep weaker evidence than a health check?

## Resources

### Read

- [Docker: Understanding image layers](https://docs.docker.com/get-started/docker-concepts/building-images/understanding-image-layers/)
- [Docker: Multi-stage builds](https://docs.docker.com/build/building/multi-stage/)
- [Docker Compose startup order](https://docs.docker.com/compose/how-tos/startup-order/)

### Reference

- [Dockerfile best practices](https://docs.docker.com/build/building/best-practices/)
- [Docker Compose CLI reference](https://docs.docker.com/reference/cli/docker/compose/)

## You are done when

- [ ] A clean copy can follow documented configuration, build, start, migration, and test commands.
- [ ] Your Dockerfile’s cache and copy order are explainable from its inputs.
- [ ] Health and dependency behavior are backed by real output, not a startup sleep.
- [ ] You can diagnose all six named failures with rendered configuration, state, logs, network, process, or data evidence.
- [ ] Runtime configuration and browser-visible configuration are separate, and private values are not baked into images.

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/DOCKER_DOCS.md` §§2–16 and `FSO_CONTAINERS.md`.
- Official sources: Docker image layers, multi-stage builds, Compose startup order, Dockerfile best practices, and Compose CLI pages linked above.
- Versions: examples use Compose plugin syntax, Node image `22.21.1-alpine`, nginx `1.29.4-alpine`, and PostgreSQL `17.7`; verify supported Docker Engine/Compose versions before freeze.
- Consulted: 2026-08-15.
- Curriculum authority: `docs/dalt-fullstack/CURRICULUM.md` §21, FS10.2.
- DALT files inspected: root `package.json`, `vite.config.mjs`, `framework/Core/functions.php`, and B09 specification.
- Laravel source: not applicable; the portable model is Docker/Compose behavior, not a Laravel-specific runtime.
