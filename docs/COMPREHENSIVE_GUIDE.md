# DALT.PHP Comprehensive Guide

> **Learn Backend Development by Debugging Real Code**

DALT.PHP is an interactive educational platform that teaches web framework concepts through hands-on debugging. Instead of just reading tutorials, you'll fix intentionally broken code to understand how backend systems really work.

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Project Structure](#project-structure)
4. [Core Concepts](#core-concepts)
5. [Learning Path](#learning-path)
6. [Challenge System](#challenge-system)
7. [Framework Architecture](#framework-architecture)
8. [Verification System](#verification-system)
9. [Development Guide](#development-guide)
10. [Troubleshooting](#troubleshooting)

---

## Overview

### What is DALT.PHP?

DALT.PHP (Debug And Learn Together) is a complete learning environment featuring:

- **5 comprehensive lessons** explaining backend fundamentals
- **5 broken challenges** with realistic bugs to fix
- **Automatic verification** with instant feedback and hints
- **Modern tech stack**: PHP 8+, Vue 3, Tailwind CSS v4, SQLite
- **Interactive UI** for browsing lessons and running verifications
- **CLI tools** for verification and testing

### Learning Philosophy

Traditional tutorials tell you how things work. DALT.PHP makes you discover it by:
1. Reading a concept explanation
2. Encountering a broken implementation
3. Debugging and fixing the issue
4. Understanding through hands-on practice

This "learn by debugging" approach creates deeper understanding than passive reading.


### Key Features

- **Zero Configuration**: SQLite database, no complex setup
- **Instant Feedback**: Run verification and see results immediately
- **Progressive Difficulty**: Start easy, gradually increase complexity
- **Real-World Bugs**: Encounter issues you'll face in production
- **Safe Environment**: Break things without consequences
- **Complete Framework**: Full MVC architecture to explore

---

## Quick Start

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- Git

### Installation

```bash
# Clone the repository
git clone https://github.com/Ibnu-Afdel/DALT.PHP.git
cd DALT.PHP

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Setup environment
cp .env.example .env

# Create database and run migrations
touch database/database.sqlite
php artisan migrate
```

### Running the Application

You need two servers running simultaneously:

**Terminal 1 - PHP Backend:**
```bash
php artisan serve
# Runs on http://localhost:8888
```

**Terminal 2 - Vite Frontend:**
```bash
npm run dev
# Runs on http://localhost:5173
```

**Access the application:**
```
http://localhost:8888
```


---

## Project Structure

### Directory Layout

```
DALT.PHP/
├── public/                    # Web server document root
│   ├── index.php             # Front controller (entry point)
│   └── router.php            # PHP built-in server router
│
├── framework/Core/           # Framework core classes
│   ├── Router.php            # HTTP routing system
│   ├── Database.php          # PDO database wrapper
│   ├── Authenticator.php     # User authentication
│   ├── Session.php           # Session management
│   ├── Validator.php         # Input validation
│   ├── ChallengeVerifier.php # Challenge verification engine
│   ├── Middleware/           # Middleware classes
│   │   ├── Auth.php          # Authentication guard
│   │   ├── Guest.php         # Guest-only guard
│   │   └── Csrf.php          # CSRF protection
│   ├── bootstrap.php         # Framework initialization
│   └── functions.php         # Global helper functions
│
├── Http/controllers/         # Application controllers
│   ├── welcome.php           # Welcome page
│   ├── learn/                # Learning interface
│   │   ├── index.php         # Lesson/challenge browser
│   │   ├── lesson.php        # Lesson viewer
│   │   └── challenge.php     # Challenge viewer
│   └── api/                  # API endpoints
│       └── verify.php        # Verification endpoint
│
├── routes/                   # Route definitions
│   └── routes.php            # Application routes
│
├── resources/                # Frontend resources
│   ├── views/                # PHP view templates
│   ├── js/                   # JavaScript source
│   │   ├── app.js            # Vue 3 entry point
│   │   └── components/       # Vue components
│   └── css/                  # CSS source
│       └── input.css         # Tailwind CSS v4
│
├── lessons/                  # Educational content
│   ├── lesson-01-request-lifecycle/
│   ├── lesson-02-routing/
│   ├── lesson-03-middleware/
│   ├── lesson-04-authentication/
│   └── lesson-05-database/
│
├── challenges/               # Broken code scenarios
│   ├── broken-routing/
│   ├── broken-middleware/
│   ├── broken-auth/
│   ├── broken-database/
│   └── broken-session/
│
├── config/                   # Configuration files
│   ├── app.php               # Application config
│   └── database.php          # Database config
│
├── database/                 # Database files
│   ├── database.sqlite       # SQLite database
│   └── migrations/           # Migration files
│
├── storage/                  # Application storage
│   └── logs/                 # Log files
│       └── challenges.log    # Verification logs
│
└── docs/                     # Documentation
    ├── PROJECT_ARCHITECTURE.md
    ├── FRAMEWORK_ARCHITECTURE.md
    ├── VERIFICATION_SYSTEM.md
    └── COMPREHENSIVE_GUIDE.md (this file)
```


---

## Core Concepts

### HTTP Request Lifecycle

Every HTTP request follows this path:

```
1. Browser Request
   ↓
2. public/index.php (Front Controller)
   ↓
3. Framework Bootstrap
   - Load environment variables
   - Initialize container
   - Connect to database
   ↓
4. Router
   - Match URI to route
   - Extract parameters
   ↓
5. Middleware Pipeline
   - Auth check
   - CSRF validation
   - Custom middleware
   ↓
6. Controller
   - Business logic
   - Database queries
   - Validation
   ↓
7. View Rendering
   - PHP templates
   - Vue components
   ↓
8. HTTP Response
```

### Routing System

Routes map URLs to controllers:

```php
// Define a route
$router->get('/posts/{id}', 'posts/show.php');

// When user visits /posts/123:
// 1. Router matches pattern
// 2. Extracts id=123
// 3. Injects into $_GET['id']
// 4. Executes posts/show.php
```

**Key Principle**: Specific routes must come before generic routes.

```php
// ✅ CORRECT
$router->get('/posts/create', 'posts/create.php');  // Specific
$router->get('/posts/{id}', 'posts/show.php');      // Generic

// ❌ WRONG
$router->get('/posts/{id}', 'posts/show.php');      // Generic first
$router->get('/posts/create', 'posts/create.php');  // Never matches!
```


### Middleware System

Middleware filters requests before they reach controllers:

```php
// Protect route with auth middleware
$router->get('/dashboard', 'dashboard.php')->only('auth');

// Multiple middleware
$router->post('/update', 'update.php')->only(['auth', 'csrf']);
```

**Execution Order**: Middleware runs in the order specified.

**Built-in Middleware**:
- `auth` - Requires authenticated user
- `guest` - Requires unauthenticated user
- `csrf` - Validates CSRF token

### Authentication Flow

**Registration**:
```php
1. User submits email/password
2. Password hashed: password_hash($password, PASSWORD_BCRYPT)
3. User stored in database
```

**Login**:
```php
1. User submits credentials
2. Find user by email
3. Verify password: password_verify($password, $hash)
4. Store user in session: $_SESSION['user'] = $user
5. Regenerate session ID for security
```

**Session Check**:
```php
// Auth middleware checks
if (!isset($_SESSION['user'])) {
    redirect('/login');
}
```

### Database Queries

Simple PDO wrapper with prepared statements:

```php
$db = App::resolve(Database::class);

// Select one
$user = $db->query('SELECT * FROM users WHERE id = :id', ['id' => 1])->find();

// Select all
$posts = $db->query('SELECT * FROM posts')->get();

// Insert
$db->query('INSERT INTO posts (title, body) VALUES (:title, :body)', [
    'title' => 'Hello',
    'body' => 'World'
]);
```

**Security**: Always use prepared statements to prevent SQL injection.


### Session Management

Sessions persist data across requests:

```php
// Store data
$_SESSION['user'] = ['email' => 'user@example.com'];

// Flash data (available only on next request)
Session::flash('success', 'Post created!');

// Retrieve flash data
$message = Session::get('success'); // Available once, then removed
```

**Flash Data Lifecycle**:
```
Request 1: Session::flash('key', 'value')
          → Stored in $_SESSION['_flash']['key']
Request 2: Session::get('key')
          → Moved to $_SESSION['key']
Request 3: Session::get('key')
          → Removed from session
```

---

## Learning Path

### Complete Curriculum

| Step | Lesson | Challenge | Difficulty | Time |
|------|--------|-----------|------------|------|
| 1 | Request Lifecycle | - | Beginner | 30 min |
| 2 | Routing System | Broken Routing | Beginner | 45 min |
| 3 | Middleware Pipeline | Broken Middleware | Medium | 60 min |
| 4 | Authentication | Broken Auth | Easy | 45 min |
| 5 | Database Queries | Broken Database | Medium | 60 min |
| 6 | Session Management | Broken Session | Medium | 60 min |

### How to Use This Platform

**Step 1: Read the Lesson**
```bash
# Visit /learn in browser or read markdown
cat lessons/lesson-02-routing/README.md
```

**Step 2: Understand the Concept**
- Study the explanation
- Review code examples
- Understand the theory

**Step 3: Try the Challenge**
```bash
# Copy broken files
cp challenges/broken-routing/routes/routes.php routes/
```

**Step 4: Debug the Code**
- Test the broken behavior
- Trace the code
- Identify the bug

**Step 5: Fix the Bug**
- Edit the files
- Apply the fix
- Test manually

**Step 6: Verify Your Fix**
```bash
# Run verification
php artisan verify broken-routing
```

**Step 7: Review the Solution**
- Compare with provided solution
- Understand why it works
- Learn from the explanation


---

## Challenge System

### Available Challenges

#### 1. Broken Routing (Beginner)

**Bugs**: 2 routing issues
**Concepts**: Route order, route registration
**Time**: 45 minutes

**Symptoms**:
- `/posts/create` shows post detail instead of create form
- `/posts/1/edit` returns 404

**Learning Objectives**:
- Understand route matching order
- Learn how route parameters work
- Debug 404 errors

**Files to Fix**:
- `routes/routes.php`

#### 2. Broken Middleware (Medium)

**Bugs**: 2 middleware issues
**Concepts**: Auth checks, CSRF validation
**Time**: 60 minutes

**Symptoms**:
- Auth middleware checks wrong session key
- CSRF validation logic is inverted

**Learning Objectives**:
- Understand middleware execution
- Learn authentication flow
- Implement secure CSRF protection

**Files to Fix**:
- `framework/Core/Middleware/Auth.php`
- `framework/Core/Middleware/Csrf.php`

#### 3. Broken Authentication (Easy)

**Bugs**: 1 password verification issue
**Concepts**: Password hashing, secure comparison
**Time**: 45 minutes

**Symptoms**:
- Login always fails even with correct credentials
- Plain text comparison used instead of password_verify()

**Learning Objectives**:
- Understand password hashing
- Learn why password_verify() is necessary
- Implement secure authentication

**Files to Fix**:
- `framework/Core/Authenticator.php`


#### 4. Broken Database (Medium)

**Bugs**: 2 SQL injection vulnerabilities
**Concepts**: Prepared statements, parameter binding
**Time**: 60 minutes

**Symptoms**:
- SQL injection possible through string concatenation
- Parameters not passed to execute()

**Learning Objectives**:
- Understand SQL injection attacks
- Learn prepared statement usage
- Implement secure database queries

**Files to Fix**:
- `framework/Core/Database.php`

#### 5. Broken Session (Medium)

**Bugs**: 2 flash data issues
**Concepts**: Session lifecycle, flash data
**Time**: 60 minutes

**Symptoms**:
- Flash data checked in wrong order
- Flash data cleanup commented out

**Learning Objectives**:
- Understand session management
- Learn flash data lifecycle
- Debug session persistence issues

**Files to Fix**:
- `framework/Core/Session.php`

### Challenge Workflow

```
┌─────────────────────────────────────────┐
│ 1. Backup Current Files                 │
│    cp file.php file.php.backup          │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 2. Copy Broken Files                    │
│    cp challenges/*/file.php .           │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 3. Test Broken Behavior                 │
│    Visit app, see the bug               │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 4. Read Challenge README                │
│    Understand what's broken             │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 5. Debug and Fix                        │
│    Edit files, apply fixes              │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 6. Run Verification                     │
│    php artisan verify challenge-name    │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│ 7. Review Results                       │
│    ✅ Pass → Next challenge             │
│    ❌ Fail → Get hints, keep debugging  │
└─────────────────────────────────────────┘
```


---

## Framework Architecture

### Component Overview

```
┌─────────────────────────────────────────────────────────┐
│                    HTTP Request                         │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────┐
│              Front Controller (index.php)               │
│  - Start session                                        │
│  - Load helpers                                         │
│  - Bootstrap framework                                  │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────┐
│                   Bootstrap                             │
│  - Load .env                                            │
│  - Load config                                          │
│  - Create container                                     │
│  - Connect database                                     │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────┐
│                     Router                              │
│  - Load routes                                          │
│  - Match URI pattern                                    │
│  - Extract parameters                                   │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────┐
│                   Middleware                            │
│  - Auth check                                           │
│  - CSRF validation                                      │
│  - Custom filters                                       │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────┐
│                   Controller                            │
│  - Business logic                                       │
│  - Database queries                                     │
│  - Validation                                           │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────┐
│                      View                               │
│  - PHP templates                                        │
│  - Vue components                                       │
│  - Tailwind CSS                                         │
└────────────────────┬────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────┐
│                  HTTP Response                          │
└─────────────────────────────────────────────────────────┘
```


### Core Classes

#### Router (`framework/Core/Router.php`)

Handles HTTP routing and parameter extraction.

**Key Methods**:
- `get($uri, $controller)` - Register GET route
- `post($uri, $controller)` - Register POST route
- `route($uri, $method)` - Match and execute route
- `matchUri($pattern, $uri)` - Pattern matching with regex

**Example**:
```php
$router = new Router();
$router->get('/posts/{id}', 'posts/show.php');
$router->route('/posts/123', 'GET');
// Executes posts/show.php with $_GET['id'] = 123
```

#### Database (`framework/Core/Database.php`)

PDO wrapper with query builder.

**Key Methods**:
- `query($sql, $params)` - Execute query with parameters
- `find()` - Fetch single result
- `get()` - Fetch all results
- `findOrFail()` - Fetch or abort with 404

**Example**:
```php
$db = App::resolve(Database::class);
$user = $db->query('SELECT * FROM users WHERE id = :id', ['id' => 1])->find();
```

#### Authenticator (`framework/Core/Authenticator.php`)

Handles user authentication.

**Key Methods**:
- `attempt($email, $password)` - Try to login
- `login($user)` - Store user in session
- `logout()` - Destroy session

**Example**:
```php
$auth = new Authenticator();
if ($auth->attempt($email, $password)) {
    redirect('/dashboard');
}
```

#### Session (`framework/Core/Session.php`)

Session management with flash data support.

**Key Methods**:
- `put($key, $value)` - Store in session
- `get($key, $default)` - Retrieve from session
- `flash($key, $value)` - Store flash data
- `unflash()` - Clean up old flash data

**Example**:
```php
Session::flash('success', 'Post created!');
// Next request:
$message = Session::get('success'); // Available once
```


#### Validator (`framework/Core/Validator.php`)

Input validation with common rules.

**Key Methods**:
- `string($value, $min, $max)` - Validate string length
- `email($value)` - Validate email format

**Example**:
```php
$errors = [];

if (!Validator::string($_POST['title'], 1, 255)) {
    $errors['title'] = 'Title must be between 1 and 255 characters';
}

if (!Validator::email($_POST['email'])) {
    $errors['email'] = 'Invalid email format';
}

if (!empty($errors)) {
    throw new ValidationException($errors);
}
```

#### Container (`framework/Core/Container.php`)

Dependency injection container.

**Key Methods**:
- `bind($key, $resolver)` - Register dependency
- `resolve($key)` - Retrieve dependency

**Example**:
```php
$container = new Container();
$container->bind(Database::class, function() {
    return new Database($config);
});

$db = $container->resolve(Database::class);
```

### Helper Functions

Located in `framework/Core/functions.php`:

```php
// Dump and die (debugging)
dd($variable);

// Render view
view('posts/show.view.php', ['post' => $post]);

// Redirect
redirect('/dashboard');

// Abort with status code
abort(404);

// Get base path
base_path('routes/routes.php');

// Check authorization
authorize($condition, $status = Response::FORBIDDEN);
```


---

## Verification System

### How Verification Works

The verification system automatically checks if you've correctly fixed challenges.

**Architecture**:
```
Challenge Directory
├── tests.php          # Test specifications
├── README.md          # Challenge description
└── broken files       # Files to copy

ChallengeVerifier
├── Load tests.php
├── Run each test
├── Collect results
└── Return pass/fail with hints
```

### Test Types

#### 1. route_exists

Checks if a route is registered:

```php
[
    'type' => 'route_exists',
    'route' => '/posts/create',
    'method' => 'get',
    'hint' => 'Register the route in routes/routes.php'
]
```

#### 2. route_order

Verifies route order:

```php
[
    'type' => 'route_order',
    'specific' => '/posts/create',
    'generic' => '/posts/{id}',
    'hint' => 'Move specific route before generic'
]
```

#### 3. file_contains

Checks if file contains expected code:

```php
[
    'type' => 'file_contains',
    'file' => 'framework/Core/Authenticator.php',
    'search' => 'password_verify',
    'hint' => 'Use password_verify() function'
]
```

#### 4. file_not_contains

Checks if problematic code is removed:

```php
[
    'type' => 'file_not_contains',
    'file' => 'framework/Core/Database.php',
    'search' => '->execute();',
    'hint' => 'Pass $params to execute()'
]
```


### Running Verification

**CLI Command**:
```bash
php artisan verify broken-routing
```

**Output Example**:
```
╔══════════════════════════════════════════════════════════════╗
║           DALT Challenge Verification System                ║
╚══════════════════════════════════════════════════════════════╝

Verifying: broken-routing
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✓ Route get /posts/create exists
✗ Route get /posts/{id}/edit not found
  💡 Hint: Uncomment the /posts/{id}/edit route
✓ Route order correct
✓ No problematic code found

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Results: 3/4 tests passed
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

❌ 1 of 4 tests failed. Keep debugging!

💡 Next step: Uncomment the /posts/{id}/edit route
```

**Web Interface**:
```
Visit: http://localhost:8888/learn
Click: Challenge name
Click: "Run Verification" button
See: Results displayed in browser
```

### Progress Tracking

Results are logged to `storage/logs/challenges.log`:

```
[2026-03-14 10:30:15] broken-routing - fail (2/4)
[2026-03-14 10:35:22] broken-routing - pass (4/4)
[2026-03-14 11:00:45] broken-middleware - fail (1/4)
```

**View logs**:
```bash
cat storage/logs/challenges.log
tail -20 storage/logs/challenges.log
```


---

## Development Guide

### Tech Stack

**Backend**:
- PHP 8.2+
- Custom micro-framework
- SQLite database
- Composer for dependencies

**Frontend**:
- Vue 3 (Composition API)
- Tailwind CSS v4
- Vite build tool
- Auto-registered components

**Development Tools**:
- PHP built-in server
- Vite dev server with HMR
- Artisan CLI commands

### Available Commands

**PHP/Composer**:
```bash
# Start PHP server
php artisan serve

# Run migrations
php artisan migrate

# Fresh migrations (drop all tables)
php artisan migrate:fresh

# Verify challenge
php artisan verify broken-routing

# Run tests (if using Pest)
composer test
```

**NPM**:
```bash
# Start Vite dev server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

### Creating New Challenges

**Step 1: Create Directory Structure**
```bash
mkdir -p challenges/my-challenge/{Http/controllers,framework/Core,routes}
```

**Step 2: Create README**
```markdown
# Challenge: My Challenge

## Difficulty: Medium

## The Bug
[Describe what's broken]

## Learning Objectives
[What learners will understand]

## How to Fix
[Solution explanation]
```


**Step 3: Create Test Specification**
```php
// challenges/my-challenge/tests.php
return [
    'test_name' => [
        'type' => 'file_contains',
        'file' => 'path/to/file.php',
        'search' => 'expected code',
        'hint' => 'What to do if test fails'
    ],
    'another_test' => [
        'type' => 'route_exists',
        'route' => '/my-route',
        'method' => 'get',
        'hint' => 'Register the route'
    ]
];
```

**Step 4: Create Broken Files**
```bash
# Copy working files and introduce bugs
cp framework/Core/SomeClass.php challenges/my-challenge/framework/Core/
# Edit and break it intentionally
```

**Step 5: Test Verification**
```bash
php artisan verify my-challenge
```

### Adding New Lessons

**Step 1: Create Lesson Directory**
```bash
mkdir -p lessons/lesson-06-my-topic
```

**Step 2: Write Lesson Content**
```markdown
# Lesson 06: My Topic

## Introduction
[Explain the concept]

## How It Works
[Technical details]

## Code Examples
[Show examples]

## Common Pitfalls
[What to watch out for]

## Next Steps
[Link to challenge]
```

**Step 3: Add to Learning Interface**
```php
// Update Http/controllers/learn/index.php
$lessons[] = [
    'id' => 'lesson-06-my-topic',
    'title' => 'My Topic',
    'description' => 'Learn about...'
];
```


### Vue Component Development

**Auto-Registration**:
All components in `resources/js/components/` are auto-registered globally.

**Example Component**:
```vue
<!-- resources/js/components/MyComponent.vue -->
<script setup>
import { ref } from 'vue';

const count = ref(0);
</script>

<template>
  <div>
    <button @click="count++">Count: {{ count }}</button>
  </div>
</template>
```

**Usage in Views**:
```php
<!-- resources/views/my-page.view.php -->
<div id="app">
    <my-component></my-component>
</div>
```

### Styling with Tailwind CSS v4

**Configuration**: `resources/css/input.css`

```css
@import "tailwindcss";

/* Custom styles */
.btn-primary {
    @apply bg-blue-500 text-white px-4 py-2 rounded;
}
```

**Usage**:
```html
<button class="btn-primary hover:bg-blue-600">
    Click Me
</button>
```

### Database Migrations

**Create Migration**:
```bash
php artisan make:migration create_posts_table
```

**Migration File**:
```php
// database/migrations/002_create_posts_table.php
return [
    'up' => "
        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ",
    'down' => "DROP TABLE posts"
];
```

**Run Migration**:
```bash
php artisan migrate
```


---

## Troubleshooting

### Common Issues

#### Port Already in Use

**Problem**: `Port 5173 is already in use`

**Solution**:
```bash
# Kill the process
lsof -ti:5173 | xargs kill -9

# Or use different port
# Edit vite.config.mjs
server: {
    port: 5174
}
```

#### PHP Server Won't Start

**Problem**: `Port 8888 is already in use`

**Solution**:
```bash
# Check what's using the port
lsof -ti:8888

# Use different port
php artisan serve --port=8889
```

#### Database Not Found

**Problem**: `SQLSTATE[HY000] [14] unable to open database file`

**Solution**:
```bash
# Create database file
touch database/database.sqlite

# Run migrations
php artisan migrate
```

#### Vite Assets Not Loading

**Problem**: Vue components don't work, styles missing

**Solution**:
1. Check Vite dev server is running: `npm run dev`
2. Check browser console for errors
3. Verify `resources/views/partials/head.view.php` includes:
```php
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/resources/js/app.js"></script>
```

#### Verification Tests Fail Unexpectedly

**Problem**: Tests fail after fixing the code

**Solution**:
1. Check exact error message
2. Verify you edited the correct file (challenge folder vs main framework)
3. Check for syntax errors: `php -l path/to/file.php`
4. Review test specification in `challenges/*/tests.php`
5. Read the hint provided by verification


#### Session Not Persisting

**Problem**: User logged out after refresh

**Solution**:
1. Check session configuration in `public/index.php`
2. Verify session name is set: `session_name('DALT_SESSION')`
3. Check session cookie in browser dev tools
4. Ensure `session_start()` is called before any output

#### Routes Not Matching

**Problem**: 404 errors on valid routes

**Solution**:
1. Check route is registered in `routes/routes.php`
2. Verify HTTP method matches (GET vs POST)
3. Check route order (specific before generic)
4. Test with `dd($this->routes)` in Router
5. Check for typos in URI pattern

### Debugging Tools

**Dump and Die**:
```php
dd($variable); // Dumps variable and stops execution
```

**Error Logging**:
```php
error_log('Debug message: ' . print_r($data, true));
// Check storage/logs/app.log
```

**Browser DevTools**:
- Network tab: Check request/response
- Console: Check JavaScript errors
- Application tab: Check session/cookies

**PHP Error Display**:
```php
// In .env
APP_DEBUG=true

// Shows detailed error messages
```

### Getting Help

**Resources**:
- Challenge READMEs: Detailed bug descriptions
- Lesson content: Concept explanations
- Framework docs: `docs/FRAMEWORK_ARCHITECTURE.md`
- Verification docs: `docs/VERIFICATION_SYSTEM.md`
- Testing guide: `TESTING_GUIDE.md`

**Community**:
- GitHub Issues: Report bugs or ask questions
- Telegram: https://t.me/daltphp
- Discussions: Share solutions and tips


---

## Best Practices

### For Learners

**1. Read Before Debugging**
- Study the lesson first
- Understand the concept
- Then tackle the challenge

**2. Test Manually First**
- Visit the broken routes
- See the bug in action
- Understand the symptoms

**3. Trace the Code**
- Follow the request lifecycle
- Use `dd()` to inspect values
- Read the framework code

**4. Fix Incrementally**
- Fix one bug at a time
- Test after each fix
- Run verification frequently

**5. Compare Solutions**
- Try to fix it yourself first
- Then compare with provided solution
- Understand why it works

### For Contributors

**1. Keep Framework Simple**
- One concept per file
- No magic or hidden abstractions
- Clear, readable code

**2. Create Realistic Bugs**
- Based on real-world issues
- Not too obvious, not too obscure
- Educational value

**3. Provide Good Hints**
- Progressive disclosure
- Don't give away the answer
- Guide toward discovery

**4. Test Thoroughly**
- Verify tests work correctly
- Check both pass and fail cases
- Ensure hints are helpful

**5. Document Well**
- Clear challenge descriptions
- Detailed solution explanations
- Link to related concepts


---

## Advanced Topics

### Custom Middleware

**Create Middleware**:
```php
// framework/Core/Middleware/Admin.php
namespace Core\Middleware;

class Admin
{
    public function handle()
    {
        if (!isset($_SESSION['user']['is_admin'])) {
            abort(403);
        }
    }
}
```

**Register Middleware**:
```php
// framework/Core/Middleware/Middleware.php
const MAP = [
    'auth' => Auth::class,
    'guest' => Guest::class,
    'csrf' => Csrf::class,
    'admin' => Admin::class, // Add here
];
```

**Use Middleware**:
```php
$router->get('/admin', 'admin/dashboard.php')->only('admin');
```

### Database Transactions

```php
$db = App::resolve(Database::class);

try {
    $db->connection->beginTransaction();
    
    // Multiple queries
    $db->query('INSERT INTO posts ...', $data);
    $db->query('UPDATE users ...', $userData);
    
    $db->connection->commit();
} catch (Exception $e) {
    $db->connection->rollBack();
    throw $e;
}
```

### Custom Validation Rules

```php
// framework/Core/Validator.php
public static function unique($table, $column, $value)
{
    $db = App::resolve(Database::class);
    $result = $db->query(
        "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value",
        ['value' => $value]
    )->find();
    
    return $result['count'] === 0;
}
```

**Usage**:
```php
if (!Validator::unique('users', 'email', $_POST['email'])) {
    $errors['email'] = 'Email already exists';
}
```


### API Endpoints

**Create API Controller**:
```php
// Http/controllers/api/posts.php
header('Content-Type: application/json');

$db = App::resolve(Database::class);
$posts = $db->query('SELECT * FROM posts')->get();

echo json_encode([
    'success' => true,
    'data' => $posts
]);
```

**Register Route**:
```php
$router->get('/api/posts', 'api/posts.php');
```

**Consume with Vue**:
```vue
<script setup>
import { ref, onMounted } from 'vue';

const posts = ref([]);

onMounted(async () => {
    const response = await fetch('/api/posts');
    const data = await response.json();
    posts.value = data.data;
});
</script>

<template>
    <div v-for="post in posts" :key="post.id">
        {{ post.title }}
    </div>
</template>
```

### File Uploads

**HTML Form**:
```html
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="avatar">
    <button type="submit">Upload</button>
</form>
```

**Controller**:
```php
if (isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $uploadDir = base_path('storage/uploads/');
    $filename = uniqid() . '_' . $file['name'];
    
    move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
}
```


---

## Deployment

### Production Checklist

**1. Environment Configuration**:
```bash
# .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

**2. Database Setup**:
```bash
# For production, use PostgreSQL or MySQL
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=daltphp
DB_USERNAME=user
DB_PASSWORD=password
```

**3. Build Frontend**:
```bash
npm run build
# Outputs to public/build/
```

**4. Optimize Autoloader**:
```bash
composer install --no-dev --optimize-autoloader
```

**5. Set Permissions**:
```bash
chmod -R 755 storage/
chmod -R 755 database/
```

### Docker Deployment

**Dockerfile**:
```dockerfile
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy application
COPY . /var/www/html/
WORKDIR /var/www/html

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data storage/ database/

EXPOSE 80
```

**docker-compose.yml**:
```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./storage:/var/www/html/storage
      - ./database:/var/www/html/database
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
```

**Run**:
```bash
docker-compose up -d
```


---

## Roadmap

### Current Features (v1.0)

- ✅ 5 comprehensive lessons
- ✅ 5 broken challenges
- ✅ Automatic verification system
- ✅ CLI verification tool
- ✅ Progress tracking
- ✅ Vue 3 + Tailwind CSS v4
- ✅ SQLite database
- ✅ Complete documentation

### Planned Features (v2.0)

- 🔄 Web-based code editor
- 🔄 Interactive tutorials
- 🔄 Video walkthroughs
- 🔄 Achievement system
- 🔄 Leaderboards (optional)
- 🔄 More challenges (10+ total)
- 🔄 Advanced topics (caching, queues, etc.)
- 🔄 Multi-language support

### Future Enhancements (v3.0)

- 📋 Performance debugging challenges
- 📋 Security vulnerability challenges
- 📋 API development lessons
- 📋 Testing and TDD challenges
- 📋 Deployment scenarios
- 📋 Real-time collaboration
- 📋 Community challenge submissions

---

## Contributing

### How to Contribute

**1. Fork the Repository**
```bash
git clone https://github.com/Ibnu-Afdel/DALT.PHP.git
cd DALT.PHP
```

**2. Create a Branch**
```bash
git checkout -b feature/my-feature
```

**3. Make Changes**
- Add new challenges
- Improve lessons
- Fix bugs
- Enhance UI

**4. Test Your Changes**
```bash
php artisan verify your-challenge
composer test
```

**5. Submit Pull Request**
- Clear description
- Link to related issues
- Include tests if applicable


### Contribution Guidelines

**Code Style**:
- Follow PSR-12 coding standards
- Use meaningful variable names
- Add comments for complex logic
- Keep functions small and focused

**Challenge Creation**:
- Bugs must be realistic
- Provide clear descriptions
- Include helpful hints
- Write comprehensive tests

**Documentation**:
- Update relevant docs
- Add code examples
- Keep language simple
- Proofread for clarity

**Testing**:
- Test all changes manually
- Verify tests pass/fail correctly
- Check edge cases
- Ensure backward compatibility

---

## FAQ

**Q: Do I need prior PHP experience?**
A: Basic PHP knowledge helps, but the lessons explain concepts from scratch.

**Q: Can I use this to learn Laravel?**
A: Yes! DALT.PHP uses similar patterns to Laravel, making it a great stepping stone.

**Q: Is this suitable for production?**
A: DALT.PHP is educational. For production, use established frameworks like Laravel or Symfony.

**Q: Can I add my own challenges?**
A: Absolutely! Follow the "Creating New Challenges" guide and submit a PR.

**Q: How long does it take to complete?**
A: Approximately 4-6 hours to complete all lessons and challenges.

**Q: Can I use a different database?**
A: Yes! Configure PostgreSQL or MySQL in `config/database.php`.

**Q: Is there a video course?**
A: Not yet, but it's planned for v2.0. Check the roadmap.

**Q: Can I use this in my classroom?**
A: Yes! DALT.PHP is perfect for teaching backend concepts.


---

## Resources

### Official Documentation

- [README.md](../README.md) - Project overview
- [TESTING_GUIDE.md](../TESTING_GUIDE.md) - Complete testing instructions
- [PROJECT_ARCHITECTURE.md](PROJECT_ARCHITECTURE.md) - System design
- [FRAMEWORK_ARCHITECTURE.md](FRAMEWORK_ARCHITECTURE.md) - Framework internals
- [VERIFICATION_SYSTEM.md](VERIFICATION_SYSTEM.md) - How verification works

### Learning Resources

**PHP**:
- [PHP Official Documentation](https://www.php.net/docs.php)
- [PHP: The Right Way](https://phptherightway.com/)
- [Laracasts PHP for Beginners](https://laracasts.com/series/php-for-beginners)

**Web Frameworks**:
- [Laravel Documentation](https://laravel.com/docs)
- [Symfony Documentation](https://symfony.com/doc)
- [How Web Frameworks Work](https://www.freecodecamp.org/news/how-web-frameworks-work/)

**Security**:
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://phpsecurity.readthedocs.io/)
- [SQL Injection Prevention](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)

**Vue 3**:
- [Vue 3 Documentation](https://vuejs.org/)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)

**Tailwind CSS**:
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Tailwind CSS v4 Beta](https://tailwindcss.com/blog/tailwindcss-v4-beta)

### Community

- **GitHub**: [https://github.com/Ibnu-Afdel/DALT.PHP](https://github.com/Ibnu-Afdel/DALT.PHP)
- **Telegram**: [https://t.me/daltphp](https://t.me/daltphp)
- **Issues**: Report bugs or request features
- **Discussions**: Share solutions and tips


---

## Appendix

### A. Complete Challenge List

| Challenge | Files to Fix | Bugs | Tests | Difficulty |
|-----------|-------------|------|-------|------------|
| broken-routing | routes/routes.php | 2 | 4 | Beginner |
| broken-middleware | Middleware/Auth.php, Middleware/Csrf.php | 2 | 4 | Medium |
| broken-auth | Authenticator.php | 1 | 3 | Easy |
| broken-database | Database.php | 2 | 4 | Medium |
| broken-session | Session.php | 2 | 4 | Medium |

### B. Framework Class Reference

| Class | Location | Purpose |
|-------|----------|---------|
| Router | framework/Core/Router.php | HTTP routing |
| Database | framework/Core/Database.php | Database queries |
| Authenticator | framework/Core/Authenticator.php | User authentication |
| Session | framework/Core/Session.php | Session management |
| Validator | framework/Core/Validator.php | Input validation |
| Container | framework/Core/Container.php | Dependency injection |
| Middleware | framework/Core/Middleware/Middleware.php | Middleware resolver |
| Auth | framework/Core/Middleware/Auth.php | Auth middleware |
| Guest | framework/Core/Middleware/Guest.php | Guest middleware |
| Csrf | framework/Core/Middleware/Csrf.php | CSRF middleware |

### C. Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan serve` | Start PHP development server |
| `php artisan migrate` | Run database migrations |
| `php artisan migrate:fresh` | Drop all tables and re-migrate |
| `php artisan verify <challenge>` | Verify challenge solution |

### D. Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| APP_ENV | local | Application environment |
| APP_DEBUG | true | Enable debug mode |
| APP_URL | http://localhost:8888 | Application URL |
| DB_CONNECTION | sqlite | Database driver |
| DB_DATABASE | database/database.sqlite | Database path |


### E. Quick Reference

**Debugging**:
```php
dd($variable);                    // Dump and die
error_log(print_r($data, true)); // Log to file
var_dump($value);                // Display variable
```

**Routing**:
```php
$router->get('/path', 'controller.php');
$router->post('/path', 'controller.php');
$router->get('/posts/{id}', 'posts/show.php');
$router->get('/path', 'controller.php')->only('auth');
```

**Database**:
```php
$db = App::resolve(Database::class);
$db->query($sql, $params)->find();
$db->query($sql, $params)->get();
$db->query($sql, $params)->findOrFail();
```

**Session**:
```php
Session::put('key', 'value');
Session::get('key', 'default');
Session::flash('key', 'value');
Session::has('key');
```

**Validation**:
```php
Validator::string($value, $min, $max);
Validator::email($value);
```

**Views**:
```php
view('name.view.php', ['data' => $value]);
```

**Redirects**:
```php
redirect('/path');
abort(404);
```

---

## Conclusion

DALT.PHP transforms backend learning from passive reading to active debugging. By fixing real bugs in a complete framework, you'll gain practical understanding that sticks.

**What You've Learned**:
- HTTP request lifecycle
- Routing and URL mapping
- Middleware and request filtering
- Authentication and security
- Database queries and SQL injection prevention
- Session management and flash data
- Common backend bugs and solutions

**Next Steps**:
1. Complete all 5 challenges
2. Explore the framework code
3. Build your own features
4. Contribute new challenges
5. Share with others learning backend development

**Remember**: The best way to learn is by doing. Break things, fix them, and understand why they work.

Happy debugging! 🐛🔧

---

**DALT.PHP** - Debug And Learn Together
Version 1.0 | March 2026
[GitHub](https://github.com/Ibnu-Afdel/DALT.PHP) | [Telegram](https://t.me/daltphp)

