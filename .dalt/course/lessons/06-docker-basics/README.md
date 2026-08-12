# Lesson 06: Docker Basics

## The Problem Docker Solves

You've been running DALT.PHP with `php artisan serve`. That works on your machine. But what happens when a teammate clones the project?

- Their machine has PHP 7.4, yours has PHP 8.2 — code breaks
- You installed the `pdo_pgsql` extension, they didn't — database fails silently
- You're on macOS, they're on Windows — file paths behave differently

The classic phrase: **"it works on my machine"**. Docker makes that phrase obsolete.

With Docker, you describe your exact environment in a file. Docker builds it the same way on every machine — your laptop, your teammate's laptop, a server in Germany. Same PHP version, same extensions, same OS layer, every time.

## Learning Objectives

By the end of this lesson, you will understand:
- What Docker is and why PHP developers need it
- The difference between a container and a virtual machine
- How images, containers, and layers relate
- The core Docker CLI commands you'll use every day
- How Docker will change the way you run DALT.PHP

## Predict before reading

You will run these commands at the end of the lesson. Write down your answers first.

| Command | What do you expect? |
|---|---|
| `docker run --rm php:8.2-cli php -v` on a machine with no PHP installed | ? |
| the same command a second time | ? |
| `docker run -d --name my-php php:8.2-fpm-alpine` then `docker ps` | ? |
| `docker run --rm php:8.2-cli php -m \| grep pdo_pgsql` | ? |
| editing one source file and rebuilding the image | which layers rebuild? |

The fourth is the one worth being wrong about — check it yourself in the task at the end.

## Containers vs Virtual Machines

Both solve the "different environment" problem, but differently.

**Virtual Machine:**
```
┌─────────────────────────────────┐
│         Your Application        │
├─────────────────────────────────┤
│       Guest OS (Ubuntu)         │  ← full operating system
├─────────────────────────────────┤
│          Hypervisor             │  ← VMware, VirtualBox
├─────────────────────────────────┤
│    Host OS (your machine)       │
└─────────────────────────────────┘
```
A VM runs an entire operating system. It's heavy — 10–20 GB per VM, takes minutes to boot.

**Container:**
```
┌──────────┐ ┌──────────┐ ┌──────────┐
│  DALT.PHP│ │ Postgres │ │  Nginx   │  ← your apps
├──────────┴─┴──────────┴─┴──────────┤
│         Docker Engine             │  ← thin layer
├───────────────────────────────────┤
│        Host OS (your machine)     │
└───────────────────────────────────┘
```
Containers share the host OS kernel. They only package what's different: your app, its dependencies, config. This makes them:
- **Lightweight** — megabytes, not gigabytes
- **Fast** — starts in milliseconds, not minutes
- **Portable** — same container runs anywhere Docker is installed

The tradeoff: containers are OS-level isolated, not hardware-level. For PHP web apps, containers are always the right choice.

## The Three Core Concepts

### Images

An image is a read-only template — the recipe. It contains:
- A base OS layer (usually Alpine Linux, ~5MB)
- Runtime (PHP 8.2-FPM)
- Your app code and dependencies

Images are built from a `Dockerfile`. They're stored in registries (Docker Hub, GHCR).

```bash
# Download the official PHP 8.2 FPM image from Docker Hub
docker pull php:8.2-fpm-alpine

# List images on your machine
docker images
```

### Containers

A container is a running instance of an image. You can run many containers from the same image — they're isolated from each other.

```
Image: php:8.2-fpm-alpine (read-only)
    ↓  docker run
Container 1: running DALT.PHP (read/write layer on top)
Container 2: running another PHP app (separate read/write layer)
```

### Layers

Images are built in layers. Each instruction in a `Dockerfile` creates a layer. Layers are cached — if a layer hasn't changed, Docker reuses it from cache. This makes rebuilds fast.

```
Layer 4: COPY . .                (your source code — changes often)
Layer 3: RUN composer install    (dependencies — changes occasionally)
Layer 2: RUN docker-php-ext-install pdo  (extensions — rarely changes)
Layer 1: FROM php:8.2-fpm-alpine (base image — almost never changes)
```

**Key insight:** put things that change less frequently at the bottom. Docker rebuilds from the first changed layer downward.

This sketch is simplified in one way you will fix in Lesson 07: `composer install` needs `composer.json` inside the image before it can run, so the real Dockerfile copies those two files on their own first. That ordering is what keeps the slow dependency layer cached when only your source changes.

## Core CLI Commands

These are the commands you'll use constantly. Learn these and you can do everything else.

### Working with Images

```bash
# Pull an image from Docker Hub
docker pull php:8.2-fpm-alpine

# Build an image from a Dockerfile in the current directory
docker build -t dalt-php .
# -t names the image "dalt-php"
# .  means "use the Dockerfile in the current directory"

# List all images on your machine
docker images

# Remove an image
docker rmi dalt-php
```

### Running Containers

```bash
# Run a container (downloads image if not present)
docker run php:8.2-cli php -v

# Run interactively with a shell
docker run -it php:8.2-cli sh

# Run in background (detached)
docker run -d --name my-php php:8.2-fpm-alpine

# Run and remove container when it stops
docker run --rm php:8.2-cli php -r "echo phpversion();"
```

### Managing Running Containers

```bash
# List running containers
docker ps

# List all containers (including stopped)
docker ps -a

# View logs from a container
docker logs my-php

# Follow logs in real time
docker logs -f my-php

# Execute a command inside a running container
docker exec -it my-php sh

# Stop a container
docker stop my-php

# Remove a stopped container
docker rm my-php
```

### Cleaning Up

```bash
# Remove all stopped containers
docker container prune

# Remove unused images
docker image prune

# Remove everything unused (careful!)
docker system prune
```

## How Docker Will Change Your DALT.PHP Workflow

**Before Docker:**
```bash
php artisan serve
# → App runs on your machine with your PHP
# → You need php, composer, sqlite installed locally
```

**After Docker:**
```bash
docker compose up
# → App runs in a container with PHP 8.2-FPM
# → Postgres runs in a separate container
# → Nginx handles HTTP routing
# → Zero local PHP/Postgres installation required
```

The goal by the end of Phase 2 is to have DALT.PHP running entirely inside Docker with:
- An `app` container (PHP 8.2-FPM + your code)
- A `db` container (PostgreSQL 16)
- An `nginx` container (handles HTTP, proxies PHP requests to app)

## Registries

Docker Hub (`hub.docker.com`) is the default registry — like npm for Docker images.

When you run `docker pull php:8.2-fpm-alpine`, Docker:
1. Looks for `php:8.2-fpm-alpine` locally
2. If not found, downloads from Docker Hub
3. Caches it locally for future use

Images are named as `owner/name:tag`. Official images (like `php`, `postgres`, `nginx`) have no owner prefix.

```bash
# These are all the same registry, different images/tags
docker pull php:8.2-fpm-alpine    # PHP 8.2 FPM on Alpine Linux
docker pull php:8.2-fpm           # PHP 8.2 FPM on Debian
docker pull postgres:16-alpine    # PostgreSQL 16 on Alpine
docker pull nginx:alpine          # Nginx on Alpine
```

## Your First Docker Command

Before writing any Dockerfiles, verify Docker is installed and working:

```bash
# Should print Docker version
docker --version

# Run PHP inside a container — no local PHP needed
docker run --rm php:8.2-cli php -v
```

The second command:
1. Downloads `php:8.2-cli` from Docker Hub (first time only)
2. Starts a container
3. Runs `php -v` inside it
4. Outputs the PHP version
5. Removes the container (`--rm`)

**Expected output:**
```
PHP 8.2.x (cli) (built: ...)
Copyright (c) The PHP Group
```

If you see this, Docker is working correctly.

## Your Task for This Lesson

1. Install Docker Desktop if you haven't yet: https://docs.docker.com/get-docker/
2. Run `docker --version` to confirm it's installed
3. Run `docker run --rm php:8.2-cli php -v` — confirm PHP 8.2 responds from inside a container
4. Run `docker run --rm php:8.2-cli php -r "echo phpversion();"` — you're running PHP code in a container
5. Run `docker run -it --rm php:8.2-cli sh` — you're now inside a container shell. Type `exit` to leave.

6. Run `docker run --rm php:8.2-cli php -m | grep -i pdo` — note which PDO drivers the official image ships. You will find `pdo_sqlite` but **not** `pdo_pgsql`. That absence is exactly why Lesson 07 installs it.

You haven't written a single Dockerfile yet. But you've run PHP in an isolated container. That's the foundation.

## Checkpoint

Answer from memory:

1. Explain why a container is smaller and starts faster than a virtual machine, in terms of what each one packages.
2. State the difference between an image and a container, and how many containers one image can produce.
3. You change one line in a controller and rebuild. Explain which layers Docker reuses and which it rebuilds.
4. Explain why `composer install` is placed before `COPY . .` rather than after.
5. `docker ps` shows nothing but `docker ps -a` shows your container. What happened?
6. Name what `--rm` does and when you would not want it.

## Key Files Going Forward

- `Dockerfile` — recipe for building your app image (you'll write this in Lesson 07)
- `docker-compose.yml` — orchestrates multiple containers (Lesson 08)
- `.dockerignore` — tells Docker what to exclude from the build context (Lesson 07)

## Common Issues

### "Cannot connect to Docker daemon"
The daemon is not running, or your user cannot reach its socket. On macOS and Windows, start Docker Desktop. On Linux, start the service with `sudo systemctl start docker`; if it is already running, check that your user is in the `docker` group (`groups | grep docker`) and log out and back in after adding it.

### "Port already in use"
Another process is using the port. Stop it or map to a different port with `-p 8081:80`.

### "Image not found"
Check the image name and tag at hub.docker.com. Tags are case-sensitive.

## Summary

- Docker solves the "works on my machine" problem
- **Container**: a running isolated process — lightweight, fast, portable
- **VM**: a full OS emulation — heavy, slow, overkill for web apps
- **Image**: a read-only template built from a Dockerfile
- **Layer**: one instruction in a Dockerfile; cached for fast rebuilds
- **Registry**: where images are stored (Docker Hub is the default)
- Core commands: `docker run`, `docker ps`, `docker logs`, `docker exec`, `docker stop`, `docker rm`

## Next Step

**Lesson 07: Writing Dockerfiles** — Now that you understand what Docker is, you'll write the `Dockerfile` that packages DALT.PHP into an image. Then you'll complete it yourself in the first challenge.
