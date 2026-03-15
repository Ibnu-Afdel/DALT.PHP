# DALT.PHP Architecture V2 Migration Guide

## Why the Change?

In V1 of DALT.PHP, the root workspace was becoming cluttered. Code directly responsible for handling the interactive "learning modules" (Vue, Vite configs, internal Markdown viewers, and verifiers) was mixed directly into the user codebase.

To reduce **cognitive overload** for beginners, we physically separated the "course engine" from the "user application". 

## Key Changes

### 1. The `.dalt/` Directory
All code responsible for rendering the visually rich tutorial screens (`/learn` and `/api/verify` routes) has been moved into the `.dalt/` directory.

Users **do not** need to look inside `.dalt/`. That folder functions as a black box engine that safely runs the interactive UI next to their PHP app. The core `public/index.php` gracefully hooks into these routes unseen.

**What Moved to .dalt:**
*   **The Learning UI:** `resources/views/learn` -> `.dalt/resources/views/learn`
*   **Status & Error UI:** `resources/views/status` -> `.dalt/resources/views/status`
*   **Vue Frontend Architecture:** `frontend/` and `resources/js/components/` -> `.dalt/frontend/` and `.dalt/resources/js/components`
*   **Internal API Endpoints:** `Http/controllers/learn/` and `/api/verify` -> `.dalt/Http/controllers/`
*   **Code Templates:** `framework/examples/` -> `.dalt/stubs/`
*   **Build Configuration:** `package.json`, `vite.config.mjs` -> `.dalt/`

### 2. The `app/` Directory
The `Http` directory (which holds user-created controllers) has been placed under standard MVC packaging: `app/`.

**What Moved:**
*   `Http/` -> `app/Http/`

### 3. The `course/` Directory
Learning content has been consolidated into a single `course/` directory:
*   `lessons/` -> `course/lessons/`
*   `challenges/` -> `course/challenges/`

This groups all educational material together and reduces root directory clutter.

### 4. Clearer Root Setup
Everything else at the root now accurately reflects the app you are building:
*   `.dalt/`: Hidden platform internals (learning UI, verification system)
*   `framework/`: The transparent custom core framework designed to be reviewed and studied.
*   `course/`: Where active user coding exercises and lessons are located.
*   `app/Http/`: Where standard user logic is maintained.
*   `routes/routes.php`: An empty, standard entry file strictly for matching user code endpoints. 

