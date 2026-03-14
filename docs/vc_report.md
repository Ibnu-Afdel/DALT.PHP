# DALT.PHP Architecture & DX Critique Report
**Date:** March 14, 2026  
**Focus:** Developer Experience (DX), Cognitive Load Reduction, and Intentional Architectural Design  

## Executive Summary
DALT.PHP employs a highly effective "learn-by-doing" mechanic. The separation of the "user workspace" from the "lesson engine" (via the recently created `internals/` directory) is a massive leap forward. However, to achieve maximum elegance and zero-friction DX for pure backend learning, several structural elements still leak cognitive overhead. 

The primary recommendation is to aggressive push non-backend "noise" entirely out of sight, enforce stricter naming conventions, and isolate "framework engine" code from "framework scaffolding" code.

---

## Strategic Critiques & Recommendations

### 1. The Visibility of the "Internals" Engine
**Critique:** 
Currently, the `internals/` directory sits dead center in the root folder alphabetically. A user opening the project sees: `framework/`, `internals/`, `lessons/`. It implicitly suggests that they are supposed to interact with it, even if told otherwise.

**Recommendation:**
Rename `internals/` to `_internals/` or `.dalt/`. 
*   An underscore (`_internals/`) forces the directory to the very top of IDE file trees, conceptually separating it from the standard A-Z app structure.
*   A dot (`.dalt/` or `.internals/`) completely hides it in many standard terminal views and minimizes its visual weight in VS Code. It signals "this is tooling, not your application."

### 2. Frontend Build Configuration Bleed
**Critique:**
DALT.PHP is explicitly a backend learning tool. Yet, a user's root directory contains `package.json`, `package-lock.json`, `node_modules/`, and `vite.config.mjs`. This immediately spikes cognitive load. The user asks: *"Wait, do I need to know Node and NPM to learn PHP headers and routing?"*

**Recommendation:**
Relocate all Node/Vite ecosystem files inside the `_internals/` directory. 
If the user wants to write front-end code in `resources/`, you can provide a simplified backend-rendered view or offer a distinct `artisan mix` or `artisan dev` wrapper command that secretly runs standard Vite processes inside the `_internals` folder. The root should look purely like a standard PHP backend application.

### 3. Consolidation of Root Level Curriculum
**Critique:**
The root directory has both a `lessons/` folder (markdown content) and a `challenges/` folder (broken code exercises). This splits the curriculum across two top-level directories.

**Recommendation:**
Consolidate these into a single top-level `curriculum/` or `course/` folder:
```
course/
  ├── lessons/
  └── challenges/
```
This removes another folder from the root and logically links the reading material with the practical application.

### 4. Framework Engine vs. Framework Scaffolding (Stubs)
**Critique:**
Inside `framework/`, there is a mix of core runtime logic (`framework/Core/Router.php`) and scaffolding (`framework/examples/auth/`). The `framework/` folder should be an untouchable, readable engine. Storing user-publishable "examples" inside it blurs the line between HTTP lifecycle concepts and feature templates.

**Recommendation:**
Move `framework/examples/` to `_internals/stubs/` or `_internals/blueprints/`. When the user runs `php artisan example:install auth`, the console command should copy the files from the hidden `_internals/stubs/auth` to the `app/` and `resources/` directories. This keeps the `framework/` folder absolutely pristine and specifically scoped to routing, requests, and core logic.

### 5. Standardizing the Database Layer
**Critique:**
As noted in previous discussions, using Laravel's Illuminate database schema builder inside `database/migrations` abstracts away RAW SQL, which is detrimental to learning backend fundamentals like DDL and database constraints.

**Recommendation:**
Remove the Illuminate schema builder dependency. Utilize raw `.sql` files in the `database/` folder and implement a simple 20-line PHP script in `framework/Core/Migration.php` that loops through these files and executes them via native PDO.

---

## Proposed Optimal Root Directory State

If the above recommendations are implemented, the ideal root directory visually encountered by a new student would shrink to this pristine, highly focused structure:

```text
/DALT.PHP
├── .dalt/             (Hidden: Holds Vue UI, stubs, vite config, verifiers)
├── app/               (User's workspace: Controllers, Logic)
├── course/            (Combined lessons and challenges)
├── config/            (Environment bindings)
├── database/          (Raw .sql files)
├── framework/         (The readable PHP HTTP Engine: Router, Request)
├── public/            (index.php entry point)
├── resources/         (User's views and assets, empty by default)
├── routes/            (routes.php)
├── storage/           (Logs, file uploads)
├── tests/             (Pest testing suite)
├── artisan            (CLI tool)
└── composer.json      (PHP dependencies ONLY)
```

## Conclusion
The current progression is fantastic. By undertaking these final organizational refactors—specifically burying the JS build steps and grouping the course content—DALT.PHP will achieve a near-perfect balance of "authentic PHP framework architecture" and "distraction-free interactive learning."
