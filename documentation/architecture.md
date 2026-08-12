# DALT Architecture

DALT keeps the application, framework, and optional learning platform in separate roots. The separation is deliberately visible so a request can be followed from the server entry point to the handler without a hidden application kernel.

## The request path

```text
web server
  -> public/router.php
  -> public/index.php
  -> framework/Core/bootstrap.php
  -> application routes
  -> optional .dalt bootstrap and routes
  -> Core\Request
  -> Core\Router
  -> middleware
  -> controller or closure
  -> Core\Response
  -> response send
```

The sequence is implemented in `public/index.php`:

1. Define `BASE_PATH` and load Composer, framework helpers, and the core bootstrap.
2. Resolve `Config` and `Platform` from the container.
3. Start the configured session.
4. Let the optional platform boot.
5. Construct a `Router`, load `routes/routes.php`, then let the platform register its routes.
6. Capture the request and bind it into the container.
7. Dispatch the method and path through the router.
8. Convert validation failures and uncaught throwables at the outer boundary.
9. Send the resulting `Response` once.

The built-in server uses `public/router.php` as its router script. Static files are served directly when they exist; other requests are forwarded to `public/index.php`.

## Ownership by directory

| Root | Owns | Entry points |
|---|---|---|
| `app/` | learner application controllers and views | `app/Http/controllers/`, `resources/views/` |
| `routes/` | application route registration | `routes/routes.php` |
| `framework/Core/` | framework contracts and runtime | bootstrap, request, router, response, database, session |
| `config/` | PHP configuration arrays | `config/*.php` |
| `database/` | SQL migrations and local database files | `database/migrations/` |
| `public/` | web-facing document root and front controller | `public/index.php`, `public/router.php` |
| `.dalt/` | optional lessons, challenges, and learning UI | `.dalt/bootstrap.php`, `.dalt/routes/routes.php` |

Application code is the first owner of the application surface. The platform is a fallback extension: `Platform::registerRoutes()` runs after `routes/routes.php`, and `Platform` supplies later view and controller roots. Application routes and files therefore take precedence when both roots contain a matching resource.

## Bootstrap and the container

`framework/Core/bootstrap.php` loads environment values, reads every PHP file in `config/`, and registers the core services in a `Core\Container`:

- `Config` is an instance containing the loaded configuration tree.
- `Platform` is an instance representing either the installed `.dalt` directory or no platform.
- `View` is a singleton with application view roots followed by platform view roots.
- `Database` is a singleton factory. Registering it is cheap; resolving it opens the connection.

The container supports three useful forms:

```php
$container->bind(Service::class);                 // new value per resolve
$container->singleton(Cache::class);              // one value after first resolve
$container->instance(Request::class, $request);   // use this exact object
```

Constructor type-hints are resolved recursively. Closure route parameters are resolved by the same container: a class-typed parameter is a dependency, while a scalar or untyped parameter with the same name receives the named URI value.

## Routing and dispatch

`routes/routes.php` receives the global `$router`. Each call adds a `Core\Route` containing an uppercased method, URI pattern, handler, and optional middleware. The router scans routes in registration order and requires both method and URI to match.

Handlers have two forms:

- A string such as `users/show.php` is required from an allowed controller root.
- A closure is called through the container and may return a string, array, `null`, or `Core\Response`.

The router captures route placeholders as strings, binds the `Request`, runs middleware, buffers handler output, and normalizes the result with `Response::fromHandler()`. Printed output is retained as the response body and takes precedence over a non-`Response` return value. `Response::send()` is the only normal boundary that writes the final status, headers, and body.

## Middleware is a ring around the handler

`Router::only()` attaches middleware to the most recently registered route:

```php
$router->post('/posts', 'posts/store.php')->only(['auth', 'csrf']);
```

`Middleware::run()` wraps the destination from the inside out. The first declared layer sees the request first and the response last. A middleware can call `$next($request)` to continue or return a `Response` to short-circuit.

The built-in aliases are `auth`, `guest`, and `csrf`. No route match means no middleware execution; a missing route is resolved at the router boundary before the middleware ring exists.

## Errors cross one boundary

The outer `try` in `public/index.php` catches failures from session start, platform boot, route loading, and dispatch. `ExceptionHandler` reports server errors to `storage/logs/app.log` and renders an HTML response. In debug mode, 500 responses include diagnostic details. In production mode, 500 responses expose only `Internal Server Error`. Explicit `Core\HttpException` statuses such as 404, 403, 419, and 422 retain their status and message.

Validation is the one normal redirect path: the front controller flashes the validation errors and old input, then redirects to the router's safe previous URL. The handler does not need to know how that boundary works.

## Optional platform boundary

When `.dalt/` is absent, `Platform::discover()` returns an uninstalled platform and the application still boots. When it is present, DALT validates the required platform paths before booting it. The platform can:

- run `.dalt/bootstrap.php`;
- add platform view and controller roots;
- register learning routes after application routes.

`php artisan platform:remove` removes the learning layer while preserving application files. The framework core remains the same; only the optional roots and boot hooks disappear.

## Where to trace a behavior

Start at the boundary that owns the symptom:

- route selection: `framework/Core/Router.php` and `framework/Core/Route.php`;
- request values: `framework/Core/Request.php`;
- response shape: `framework/Core/Response.php`;
- dependencies and boot order: `framework/Core/Container.php` and `framework/Core/bootstrap.php`;
- error status and disclosure: `framework/Core/ExceptionHandler.php` and `public/index.php`;
- learning integration: `framework/Core/Platform.php` and `.dalt/`.

That ownership map is also the dependency direction: the front controller composes the pieces, the router selects a handler, and the handler uses services through the container. A lesson or platform feature must not be treated as a core framework contract unless the core code and its tests establish it.
