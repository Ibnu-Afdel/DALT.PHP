# DALT.PHP Framework Architecture

This document explains the internal architecture of the DALT.PHP backend framework.
Understanding this architecture is essential for debugging the system.

## Repository Structure

```
DALT.PHP/
├── public/                 # Web server document root
│   ├── index.php          # Front controller (entry point)
│   ├── router.php         # PHP built-in server router
│   └── build/             # Compiled frontend assets
├── framework/
│   └── Core/              # Framework core classes
│       ├── App.php        # Service container facade
│       ├── Container.php  # Dependency injection container
│       ├── Router.php     # HTTP routing system
│       ├── Request.php    # HTTP request abstraction
│       ├── Database.php   # PDO database wrapper
│       ├── DatabaseManager.php  # Database setup & migrations
│       ├── Session.php    # Session management
│       ├── Authenticator.php    # User authentication
│       ├── Validator.php  # Input validation
│       ├── Migration.php  # Database migration runner
│       ├── Middleware/    # Middleware classes
│       │   ├── Middleware.php   # Middleware resolver
│       │   ├── Auth.php         # Authentication guard
│       │   ├── Guest.php        # Guest-only guard
│       │   └── Csrf.php         # CSRF protection
│       ├── bootstrap.php  # Framework initialization
│       └── functions.php  # Global helper functions
├── Http/
│   └── controllers/       # Application controllers
├── routes/
│   └── routes.php         # Route definitions
├── resources/
│   ├── views/             # PHP view templates
│   ├── js/                # JavaScript source
│   └── css/               # CSS source
├── config/
│   ├── app.php            # Application configuration
│   └── database.php       # Database configuration
├── database/
│   └── migrations/        # Database migration files
└── storage/
    └── logs/              # Application logs
```

## HTTP Request Lifecycle

Understanding the request lifecycle is crucial for debugging backend issues.

### 1. Entry Point (`public/index.php`)

Every HTTP request enters through `public/index.php`:

```
HTTP Request → public/index.php
```

**What happens:**
1. Define `BASE_PATH` constant
2. Load Composer autoloader
3. Start PHP session with unique name
4. Load helper functions (`framework/Core/functions.php`)
5. Bootstrap the framework (`framework/Core/bootstrap.php`)
6. Create Router instance
7. Load route definitions (`routes/routes.php`)
8. Capture HTTP request into Request object
9. Extract URI and HTTP method
10. Route the request
11. Handle exceptions (ValidationException, general errors)
12. Clean up flash session data

### 2. Framework Bootstrap (`framework/Core/bootstrap.php`)

The bootstrap process initializes the framework:

```
1. Load Composer autoloader
2. Load environment variables from `.env` file
3. Load configuration files (`config/app.php`, `config/database.php`)
4. Create dependency injection Container
5. Bind Database instance to container
6. Set container in App facade

**Key Point:** The bootstrap creates a Database connection that's available throughout the application via `App::resolve(Database::class)`.

### 3. Routing (`framework/Core/Router.php`)

The Router matches the incoming URI and HTTP method to a controller:

**Route Definition:**
```php
$router->get('/posts/{id}', 'posts/show.php');
```

**Route Matching Process:**
1. Loop through all registered routes
2. Check if HTTP method matches
3. Use regex to match URI pattern (e.g., `/posts/{id}` → `/posts/123`)
4. Extract route parameters (e.g., `id => 123`)
5. Resolve middleware (if any)
6. Inject parameters into `$_GET` superglobal
7. Require the controller file

**Route Parameters:**
- Pattern: `/posts/{id}/edit`
- Actual: `/posts/42/edit`
- Result: `$_GET['id'] = 42`

### 4. Middleware Flow (`framework/Core/Middleware/`)

Middleware runs before the controller executes.

**Middleware Resolution:**
```php
$router->post('/login', 'session/store.php')->only(['guest', 'csrf']);
```

**Process:**
1. Router calls `Middleware::resolve(['guest', 'csrf'])`
2. Middleware::MAP translates keys to class names
3. Each middleware's `handle()` method is called in order
4. If middleware fails, execution stops (redirect or abort)
5. If all pass, controller executes

**Built-in Middleware:**
- `auth`: Requires authenticated user (checks `$_SESSION['user']`)
- `guest`: Requires unauthenticated user (no session)
- `csrf`: Validates CSRF token from form submission

### 5. Controller Structure

Controllers are simple PHP files that execute logic and render views.

**Example Controller (`Http/controllers/welcome.php`):**
```php
<?php
view('welcome.view.php');
```

**Controllers can:**
- Access route parameters via `$_GET['id']`
- Query the database via `App::resolve(Database::class)`
- Validate input via `Validator::string()`, `Validator::email()`
- Redirect via `redirect('/path')`
- Render views via `view('name.view.php', ['data' => $value])`

**No classes required** — controllers are procedural PHP files for simplicity.


### 6. View Rendering Process

Views are PHP templates with embedded HTML.

**Rendering Flow:**
```
Controller → view() helper → extract($attributes) → require template
```

**Example:**
```php
// Controller
view('posts/show.view.php', ['post' => $post]);

// View can access $post directly
<h1><?= $post['title'] ?></h1>
```

**View Helpers:**
- `<?= vite('resources/js/app.js') ?>` — Include Vite assets
- `<?= csrf_field() ?>` — Generate CSRF token input
- `<?= old('email') ?>` — Retrieve old form input after validation error

### 7. Database Usage

The framework uses PDO with a simple query builder.

**Database Connection:**
```php
$db = App::resolve(Database::class);
```

**Query Examples:**
```php
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

**Database Drivers:**
- SQLite (default, zero setup)
- PostgreSQL
- MySQL

**Configuration:** `config/database.php`

### 8. Authentication Flow

Authentication is handled by the `Authenticator` class.

**Login Process:**
1. User submits email/password
2. Controller calls `$auth->attempt($email, $password)`
3. Authenticator queries database for user by email
4. Verifies password using `password_verify()`
5. If valid, stores user in session via `$auth->login()`
6. Session is regenerated for security

**Session Storage:**
```php
$_SESSION['user'] = ['email' => 'user@example.com'];
```

**Logout Process:**
1. Controller calls `$auth->logout()`
2. Session is destroyed
3. Session cookie is deleted

**Middleware Protection:**
```php
$router->get('/dashboard', 'dashboard.php')->only('auth');
```

## Framework Stability Guidelines

### Parts That MUST Remain Stable

These are the core systems that learners will debug. They should NOT be refactored:

1. **Router.php** — Route matching and parameter extraction logic
2. **Middleware system** — Middleware resolution and execution order
3. **Database.php** — Query execution and result fetching
4. **Session.php** — Session management and flash data
5. **Authenticator.php** — Login/logout flow
6. **Request lifecycle** — The flow from index.php through routing to controller

**Why?** These are the debugging targets. If we change them, existing challenges break.


### Parts That Can Be Modified Later

These can be enhanced without breaking the debugging experience:

1. **Helper functions** — Can add new helpers in `functions.php`
2. **Validator.php** — Can add new validation rules
3. **View templates** — Can improve UI/UX
4. **Frontend stack** — Vue components and styling
5. **Error pages** — Can enhance 403/404/500 pages
6. **Configuration files** — Can add new config options
7. **Migration system** — Can add new migrations

**Why?** These are supporting systems that don't affect the core debugging scenarios.

## Common Debugging Scenarios

Understanding these patterns helps identify where bugs might be:

### Routing Issues
- Route not matching → Check `Router::matchUri()` regex
- Wrong controller executed → Check route order in `routes.php`
- Parameters not available → Check `$_GET` injection in `Router::route()`

### Middleware Issues
- Middleware not running → Check `Middleware::MAP` registration
- Wrong execution order → Check array order in `only()`
- Middleware blocking valid requests → Check logic in `handle()` method

### Authentication Issues
- Login fails with correct credentials → Check password hashing
- Session not persisting → Check session configuration
- User logged out unexpectedly → Check session lifetime

### Database Issues
- Query fails → Check SQL syntax and parameter binding
- No results returned → Check `find()` vs `get()` usage
- Connection fails → Check `config/database.php` settings

## Request Lifecycle Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ 1. HTTP Request arrives at public/index.php                 │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Session started, functions loaded                        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. Bootstrap: Load env, config, create container            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. Router created, routes loaded from routes/routes.php     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. Request captured (URI + Method)                          │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 6. Router matches URI to route                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 7. Middleware executed (auth, guest, csrf)                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 8. Controller file required and executed                    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 9. View rendered (if view() called)                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 10. Response sent to browser                                │
└─────────────────────────────────────────────────────────────┘
```

## Key Takeaways for Debuggers

1. **Follow the flow** — Start at `public/index.php` and trace execution
2. **Check the Router** — Most issues stem from routing problems
3. **Verify middleware** — Middleware can silently block requests
4. **Inspect sessions** — Authentication relies on `$_SESSION`
5. **Read error messages** — The framework provides helpful error output in debug mode
6. **Use `dd()`** — The `dd()` helper dumps variables and stops execution

This architecture is intentionally simple and traceable. Every component can be understood by reading a single file.
