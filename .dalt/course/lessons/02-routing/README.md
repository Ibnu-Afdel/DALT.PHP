# Lesson 02: Routing — Turning a Request into a Handler Call

## What you will be able to do

By the end of this lesson, you can:

- register routes for each supported HTTP method;
- explain why route order changes which handler runs;
- trace literal URI matching and named parameter extraction;
- distinguish query input from route parameters;
- follow controller resolution from application code to the optional platform;
- diagnose a routing failure before changing the router itself.

## Recommended prerequisite

Complete [Lesson 01: Request Lifecycle](../01-request-lifecycle/README.md) first. Routing is one stage inside the larger request-to-response path:

```text
server adapter → front controller → Request snapshot → Router → middleware → handler → Response
```

The router does not start the session, send headers, or print a response. Its job is to select a route and return the normalized result of that route.

## The route table

Application routes live in `routes/routes.php`:

```php
<?php

global $router;

$router->get('/', 'welcome.php');
$router->get('/posts', 'posts/index.php');
$router->get('/posts/{id}', 'posts/show.php');
$router->post('/posts', 'posts/store.php');
```

`public/index.php` creates the router, loads the application route file, then loads the optional learning-platform routes. Application routes are registered first, so learner-owned routes take priority over platform fallback routes.

Registering a route creates a `Core\Route` value containing:

- an uppercased HTTP method;
- a URI pattern;
- a closure or controller path;
- optional route middleware attached with `only()`.

The route table is ordered. `Router::route()` checks entries from first to last and returns on the first method-and-URI match.

## Predict before reading the source

For these routes:

```php
$router->get('/posts/create', 'posts/create.php');
$router->get('/posts/{id}', 'posts/show.php');
$router->post('/posts', 'posts/store.php');
```

predict the result:

| Request | Matching route | Result |
|---|---|---|
| `GET /posts/create` | `/posts/create` | create controller |
| `GET /posts/42` | `/posts/{id}` with `id = "42"` | show controller |
| `POST /posts` | `POST /posts` | store controller |
| `GET /posts` | no `GET /posts` entry | HTTP 404 |
| `POST /posts/42` | no matching method and URI | HTTP 404 |

The last two cases are 404s from the route boundary. A route that exists for another HTTP method is not a match.

## 1. The router checks the HTTP method first

The public entry point uses the effective method from `Request::method()`:

```php
$response = $router->route($request->path(), $request->method(), $request);
```

The router compares the uppercased method with each route's method. DALT supports `GET`, `POST`, `PUT`, `PATCH`, and `DELETE`.

HTML forms only submit `GET` and `POST`, so a real `POST` may use a `_method` input to reach a `PUT`, `PATCH`, or `DELETE` route:

```html
<form method="POST" action="/posts/42">
    <input type="hidden" name="_method" value="PATCH">
</form>
```

Method overrides are deliberately limited. A `GET` request cannot silently become a destructive method just because its query string contains `_method`.

## 2. URI matching is literal except for placeholders

This route:

```php
$router->get('/users/{user}/posts/{post}', $handler);
```

matches `/users/5/posts/42` and extracts:

```php
[
    'user' => '5',
    'post' => '42',
]
```

Each placeholder must start with a letter or underscore and may then contain letters, numbers, and underscores. A placeholder matches one or more characters other than `/`.

The router builds the matching expression in pieces. Static text is escaped before it is placed in the expression, so characters such as `.`, `+`, `?`, `(`, and `)` in a route pattern remain literal route text rather than becoming regular-expression operators.

For example:

```php
$router->get('/files/{name}.json', $handler);
```

matches `/files/report.json`, but not `/files/reportXjson`.

The current matcher does not add optional trailing slashes or typed constraints. If the application needs another URL shape, register another explicit route.

## 3. Route parameters are request data of their own

A handler can receive route values through the `Request` object:

```php
$router->get('/posts/{id}', function (Request $request): array {
    return [
        'id' => $request->route('id'),
        'query_page' => $request->query('page'),
    ];
});
```

These are different sources:

| API | Reads |
|---|---|
| `$request->route('id')` | `{id}` captured from the URI |
| `$request->query('id')` | `?id=...` from the query string |
| `$request->input('title')` | submitted form data |
| `$request->all()` | query data merged with form data, with form data winning |

The router stores route parameters on the captured request before dispatching the handler. It also copies them into `$_GET` as a compatibility bridge for older controller lessons. New code should use `Request::route()` so a URI parameter cannot be confused with query input.

## 4. Route order is behavior

The matcher is intentionally first-match-wins. A placeholder accepts ordinary text, so this order is wrong:

```php
$router->get('/posts/{id}', 'posts/show.php');
$router->get('/posts/create', 'posts/create.php');
```

`GET /posts/create` matches `/posts/{id}` first, with `id = "create"`. The create route is never reached.

Put the more specific route first:

```php
$router->get('/posts/create', 'posts/create.php');
$router->get('/posts/{id}', 'posts/show.php');
```

This is not a special case in the router. It is a property of any ordered route table with overlapping patterns. When debugging an unexpected handler, inspect earlier routes before changing parameter extraction.

## 5. A matched route goes through middleware

Attach middleware to the route immediately after registering it:

```php
$router->post('/posts', 'posts/store.php')->only(['auth', 'csrf']);
```

The router sets the route parameters and then asks the middleware pipeline to run. Middleware can stop dispatch and return a response, or call the next layer and transform the response on the way back out:

```text
route match
  ↓
auth before → csrf before → handler → csrf after → auth after
  ↓
Response
```

If no route matches, middleware is not invoked. A 404 caused by a missing route cannot be fixed inside an authentication or CSRF middleware.

## 6. Controller resolution is constrained and predictable

String handlers are relative controller paths such as `posts/show.php`. The router checks:

1. `app/Http/controllers/posts/show.php`;
2. each optional platform controller root, such as `.dalt/Http/controllers/posts/show.php`.

The application root wins. The router resolves the real path and verifies that it remains below the selected root, so a route cannot use `../` to include an arbitrary file. Unsafe controller paths are rejected when the `Route` is created.

Closures are dispatched through the container. The container can provide the current `Request`, named route parameters, and registered services:

```php
$router->get('/users/{id}', function (Request $request, string $id): array {
    return ['route_id' => $id, 'same_request' => $request->route('id') === $id];
});
```

The route boundary still controls the final value. A closure or controller may return a `Response`, string, array, or `null`; the router normalizes that result through `Response::fromHandler()`.

## 7. Routing ends at a response boundary

The router itself does not call `header()`, `echo`, or `exit`. Handler results become responses:

| Handler result | Normalized response |
|---|---|
| `Response` | returned unchanged |
| string | HTML response, status 200 |
| array | JSON response, status 200 |
| `null` | empty response, status 200 |
| anything else | `UnexpectedValueException` |

Echoed output is not another row in that table; it overrides it. `Response::fromHandler()` buffers the handler, and if anything was printed, that output becomes the body:

- printed output plus a returned `Response` keeps the response's status and headers but replaces its body;
- printed output plus any other return value produces an HTML response, discarding the return value.

This is why a controller that `echo`es a debug line and returns an array sends HTML instead of JSON. Remove the stray output rather than changing the return type.

`redirect('/login')` creates a redirect response. It does not terminate PHP, which allows middleware and the front controller to finish their outward path.

## A source trace for `GET /posts/42`

Use this sequence when reading the code:

1. `Request::capture()` snapshots the superglobals.
2. `Request::path()` returns `/posts/42`, excluding any query string.
3. `Router::route()` compares the effective method with each route.
4. `Router::matchUri()` captures `42` as `id`.
5. The request receives `['id' => '42']` as route parameters.
6. Route middleware runs, if configured.
7. The controller path is resolved below an allowed controller root.
8. The handler runs and its result becomes a `Response`.
9. `public/index.php` sends that response once.

For `GET /posts/create`, inspect the order at step 3. If `/posts/{id}` appears before `/posts/create`, the router is behaving correctly according to its contract; the route table is wrong.

## Debugging checklist

- **404 on a valid-looking URL:** confirm the path, effective method, and exact route registration.
- **The wrong controller runs:** inspect earlier routes for a generic placeholder pattern.
- **A route parameter is missing:** confirm the placeholder name and read it with `Request::route()`.
- **Query and route values are confused:** compare `route()`, `query()`, and `input()` instead of dumping only `$_GET`.
- **A controller cannot be found:** check the relative path and whether the file is below `app/Http/controllers` or an installed platform controller root.
- **Middleware never runs:** prove that the route matched before debugging the middleware alias or class.
- **A route works in a browser but not a form:** inspect the submitted method and any allowed `_method` override.
- **JSON arrives as HTML:** look for output printed before the return value; buffered output replaces the normalized body.
- **`UnexpectedValueException` from the route boundary:** the handler returned something other than `Response`, string, array, or `null`.

## Practice exercise

Add an application-owned `/about` page:

1. Register `$router->get('/about', 'about.php');` in `routes/routes.php`.
2. Create `app/Http/controllers/about.php`.
3. Return a small `Response` or render a view from the controller.
4. Verify `GET /about` returns 200 and an unknown path returns 404.
5. Add a parameter route such as `/team/{member}` and compare `Request::route('member')` with a query value of the same name.

Keep the route and controller in application-owned directories. The optional `.dalt` platform is a fallback, not the place for application features.

## Complete trace exercise

Read these files in order and write down what each contributes:

1. `routes/routes.php`
2. `public/index.php`
3. `framework/Core/Request.php`
4. `framework/Core/Router.php`
5. `framework/Core/Route.php`
6. `framework/Core/Middleware/Middleware.php`
7. `framework/Core/Response.php`

Then run:

```bash
composer test -- --filter='Router|Request|Response'
```

## Checkpoint

Close the source files and answer these from memory. If any answer needs the code, reread that part.

1. A request arrives for a URI that appears in the route table, and the response is 404. Name two different causes and the check that distinguishes them.
2. `/posts/{id}` is registered before `/posts/create`, and `GET /posts/create` reaches the show controller. Explain why this is the router working correctly.
3. A route pattern contains `.`, `(`, or `+`. Explain why those stay literal instead of behaving as regular-expression operators.
4. A handler prints one debug line and returns an array. Describe the response the client receives, and why.
5. Explain why a missing route cannot be repaired inside authentication middleware.

## Challenge: Broken Routing

After this lesson, start the linked challenge:

```bash
php artisan challenge:start broken-routing
php artisan challenge:verify
```

The challenge deliberately places `/posts/{id}` before `/posts/create` and comments out `/posts/{id}/edit`. Fix the route table, verify the behavior, then stop the challenge to restore the original files:

```bash
php artisan challenge:stop
```

The two bugs are route-table bugs. The matching algorithm is already doing what it was designed to do: checking routes in registration order and selecting the first valid method-and-URI match.

## Laravel bridge

Compared against Laravel 13.x ([laravel.com/docs/13.x/routing](https://laravel.com/docs/13.x/routing), consulted 2026-08-12).

Laravel also separates route registration, matching, middleware, controller dispatch, and response sending. Its router adds features DALT deliberately omits:

| Laravel 13.x | DALT |
|---|---|
| `->name('profile')` and the `route()` URL generator | no named routes; write the URI |
| `Route::group()` prefixes and shared middleware | repeat the prefix on each route |
| `->where('id', '[0-9]+')` parameter constraints | every placeholder matches one or more non-`/` characters |
| implicit model binding (`function (User $user)`) | parameters arrive as strings; load the record yourself |
| `Route::resource()` shortcuts | register each of the five methods explicitly |

DALT keeps only the visible core: an ordered list of method/pattern/handler entries, named string parameters, a small middleware pipeline, and one response boundary. Each omission is a feature you can now recognize as a convenience layer over this mechanism rather than as magic.
