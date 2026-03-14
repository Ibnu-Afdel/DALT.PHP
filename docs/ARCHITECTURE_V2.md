# DALT.PHP Architecture V2 Migration Guide

## Why the Change?

In V1 of DALT.PHP, the root workspace was becoming cluttered. Code directly responsible for handling the interactive "learning modules" (Vue, Vite configs, internal Markdown viewers, and verifiers) was mixed directly into the user codebase.

To reduce **cognitive overload** for beginners, we physically separated the "course engine" from the "user application". 

## Key Changes

### 1. The `internals/` Directory
All code responsible for rendering the visually rich tutorial screens (`/learn` and `/api/verify` routes) has been moved into the `internals/` directory.

Users **do not** need to look inside `internals/`. That folder functions as a black box engine that safely runs the interactive UI next to their PHP app. The core `public/index.php` gracefully hooks into these routes unseen.

**What Moved to Internals:**
*   **The Learning UI:** `resources/views/learn` -> `internals/resources/views/learn`
*   **Status & Error UI:** `resources/views/status` -> `internals/resources/views/status`
*   **Vue Frontend Architecture:** `frontend/` and `resources/js/components/` -> `internals/frontend/` and `internals/resources/js/components`
*   **Internal API Endpoints:** `Http/controllers/learn/` and `/api/verify` -> `internals/Http/controllers/`

### 2. The `app/` Directory
The `Http` directory (which holds user-created controllers) has been placed under standard MVC packaging: `app/`.

**What Moved:**
*   `Http/` -> `app/Http/`

### 3. Clearer Root Setup
Everything else at the root now accurately reflects the app you are building:
*   `framework/`: The transparent custom core framework designed to be reviewed and studied.
*   `challenges/`: Where active user coding exercises take place.
*   `app/Http/`: Where standard user logic is maintained.
*   `routes/routes.php`: An empty, standard entry file strictly for matching user code endpoints. 

