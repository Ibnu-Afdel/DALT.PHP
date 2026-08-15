# FS05.1 — Designing the application API

Lesson ID: FS05.1  
Title: Designing the application API  
Part: 05 — DALT API and PostgreSQL  
Order: 1  
Status: Published  
Estimated effort: 100–130 minutes  
Difficulty: Integration  
Prerequisites: FS04.3 — Separating transport from UI  
Project milestone: B05 — Persistent application  
Primary source dossier: `FSO_PART_03.md`  
Last reviewed: 2026-08-14

## Why this matters

In B04, the fixture supplied an API shape. That was useful: it let React learn waiting,
failures, and mutations without asking you to debug a database at the same time. It is
also a temporary dependency. The fixture resets, its data is not your application's
truth, and its behaviour exists only because course material says so. An API you own
starts with an agreement, not with controller code.

That agreement lets two independently changing sides cooperate. The client needs to know
what it may request, what it might receive, and how to distinguish a rejected input from
a missing resource. The server needs to know which client fields it will accept and which
facts it promises to return. Neither side should infer those answers from the other side's
implementation. Without a contract, a successful-looking UI can quietly invent fields or
render an error object as an issue.

The aim is not REST purity. It is understandable behaviour: an issue list means one
thing, a missing issue means another, and an invalid create request carries enough
evidence for the form to recover. That is a contract a person can debug at 2am.

## Before you start

Required: FS04.3 and the B04 typed API client. Read its public functions before renaming
endpoints. You will preserve the component-facing operations where possible and replace
only their fixture implementation, once the server can honour the contract.

Read three framework files before writing a handler — not as background, but because the
next section depends on what they do and do not provide:

```sh
less framework/Core/Router.php     # method + path matching, {placeholders}
less framework/Core/Request.php    # query(), input(), route()
less framework/Core/Response.php   # json(), status codes, header validation
```

Two facts from that reading matter immediately. DALT routes support GET, POST, PATCH, PUT
and DELETE, and path parameters arrive through `Request::route('id')`. And **`Request::input()`
does not parse a JSON body** — it reads `$_POST`, which PHP populates only for form
encodings. A JSON request body is invisible to it. You will implement that parsing boundary
yourself, deliberately, in §3. Do not assume a method exists because another framework has
one.

Going deeper in DALT Core — optional:

- [Routing](/learn/lessons/02-routing) and [request lifecycle](/learn/lessons/01-request-lifecycle) give more framework background. They are not prerequisites.

## By the end

- describe a resource contract in requests, responses, and failure cases;
- choose routes and methods for projects and issues without a REST checklist;
- return intentional status codes and one consistent error shape;
- distinguish a path identifier, query parameter, and request body;
- allowlist input before it reaches application code;
- trace a request from React client function to DALT response.

## Predict before reading

1. Is `GET /api/issues` with zero rows a failure? What response proves your answer?
2. If a browser sends `{ "status": "done", "is_admin": true }`, which fields should a create-issue handler accept?
3. Which outcome should be 404: no issues in a project, or an issue ID that does not exist?
4. If a handler returns `['issue' => $issue]`, what guarantees that a component receives that same shape?

## Mental model

```text
typed client intent → HTTP request → route → handler → validation → response contract
                         method/path/body          ↑                    ↓
                                             database later        React parser
```

HTTP is the seam, not the application. A route identifies an operation: method plus path.
A handler maps untrusted request data into a narrow application action. The response is an
observable claim about that action. React still treats JSON as unknown and parses it; the
server still treats request JSON as untrusted. Two runtime boundaries exist because neither
process controls the other, and removing either one means trusting a program you do not run.

## 1. Start from resources and decisions

For this first backend slice, name only the resources the project needs now:

```text
GET    /api/projects                 list projects
GET    /api/projects/{id}            show one project
GET    /api/issues?project_id=...    list issues, optionally narrowed
GET    /api/issues/{id}              show one issue
POST   /api/issues                   create an issue
PATCH  /api/issues/{id}              partially change an issue
DELETE /api/issues/{id}              remove an issue
```

Three ways of carrying data appear there, and mixing them up is the most common design
mistake in a first API:

| Carrier | Answers | Example |
|---|---|---|
| Path parameter | *which resource* | `/api/issues/42` |
| Query parameter | *which subset, how presented* | `?project_id=7&status=todo` |
| Request body | *what the new state should be* | `{"title":"Fix login"}` |

Do not put a creation payload in query text because it is easier to see in a browser. Do
not invent `/api/issues/done` and `/api/issues/todo` before a simple filter has felt any
pressure. And do not put an identifier in the body when the URL already names the
resource — two sources of "which issue" is one too many, and they will disagree.

PATCH says a subset can change. It stops the client pretending it knows every current
field just to mark an issue done. It does not remove validation: *absent* and *invalid*
are different, so validate only the allowed fields that are actually present. PUT is not
required merely to complete a verb collection.

## 2. Make status and body say the same thing

Use a compact envelope and write it down beside the client functions:

```json
{ "data": { "id": "42", "title": "Fix login", "status": "todo" } }
```

For an error, keep the envelope recognisable, and include the field detail a form needs:

```json
{ "error": { "code": "validation_failed", "message": "Title is required", "fields": { "title": "Required" } } }
```

The exact property names are a project decision; consistency is the requirement. A list
can use `{ "data": [...], "meta": { "page": 1 } }`. A 204 DELETE has no body at all. What
you must not do is make the same 422 sometimes a string and sometimes an object — a parser
cannot protect the UI from a contract that changes meaning per controller.

Status is part of the claim, not decoration on top of it:

| Status | Claim | Typical cause |
|---|---|---|
| 200 | Here is the current state | read, or update that returns the resource |
| 201 | I created a resource | POST |
| 204 | Done, and there is nothing to say | DELETE |
| 400 | I could not understand the request at all | malformed JSON |
| 404 | That resource does not exist | unknown id |
| 422 | I understood it and refuse it | title blank, status not in vocabulary |

400 and 422 are worth separating carefully. `{"title":` is 400 — the bytes are not JSON, so
no field-level message is even possible. `{"title":"  "}` is 422 — perfectly good JSON that
breaks an application rule, and the client can be told which rule. A server that answers 400
for both leaves the form with nothing to display. Part 06 adds 401 and 403.

A 200 with an empty array answers prediction 1: the collection exists and currently has no
members. That is not a missing resource, and it is not a failure. Prediction 3 follows from
the same reasoning — `/api/issues?project_id=7` returning nothing is 200 with `[]`;
`/api/issues/9999` is 404.

## 3. Parse and allowlist the request body

`Request::input()` will not help you here, so write the boundary explicitly. This is the
function FS04.3's client is talking to, and it is about thirty lines:

```php
<?php
// app/Http/support/json-request.php

declare(strict_types=1);

use Core\Request;

final class InvalidJsonBody extends RuntimeException
{
}

/**
 * Decode a JSON request body, or refuse it in a way the caller can turn into 400.
 *
 * @return array<string, mixed>
 */
function decodeJsonBody(Request $request, ?string $raw = null): array
{
    // The raw body is a parameter with a default, not a hidden call. `php://input`
    // cannot be populated from a test process, so a function that reads it directly
    // is a function no test can reach. FS06.1 depends on this seam existing.
    $raw ??= file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    try {
        $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        // Not "invalid title" — we could not read the request at all.
        throw new InvalidJsonBody('Request body is not valid JSON.');
    }

    // A JSON body of `7` or `"hello"` is valid JSON and still not an object.
    if (!is_array($decoded) || array_is_list($decoded)) {
        throw new InvalidJsonBody('Request body must be a JSON object.');
    }

    return $decoded;
}
```

Two things there are easy to skip. `php://input` can only be read once per request in some
SAPIs, so read it in one place rather than sprinkling the call through handlers. And the
`array_is_list` check catches a JSON array where an object was expected — without it,
`[1,2,3]` reaches your validation code and produces a confusing message about a missing
title.

The custom exception deserves an explanation, because DALT already gives you `abort(400)`
and it looks like the obvious choice. Read what `abort` actually produces. It throws
`Core\HttpException`, and `framework/Core/ExceptionHandler.php` renders that through
`Response::html()`:

```php
private function errorResponse(int $status, string $message): Response
{
    return Response::html(sprintf('<h1>%d</h1><p>%s</p>', $status, $this->escape($message)));
}
```

That is correct for a page and wrong for your API. A client that asked for JSON would receive
`<h1>400</h1>`, and FS04.3's `errorFromResponse` would fall back to a generic status message
because there is no envelope to read. So the JSON boundary catches its own failure and emits
its own contract:

```php
try {
    $input = decodeJsonBody($request);
} catch (InvalidJsonBody $exception) {
    return Response::json(['error' => [
        'code' => 'invalid_json',
        'message' => $exception->getMessage(),
    ]], 400);
}
```

This is the first of several places where "the framework has a helper for that" and "the
helper suits this context" turn out to be different questions. Check what a helper returns
before adopting it, particularly around content types.

The optional `$raw` parameter deserves the same scrutiny, because it looks like an
unnecessary complication and is not. `php://input` is a PHP stream populated by the web
server from the actual request. A test process has no request, so there is nothing to
populate it with — and `ApplicationTestClient` in this repository can set `$_GET` and
`$_POST` but has no way to supply a raw body. A `decodeJsonBody` that calls
`file_get_contents('php://input')` unconditionally is therefore a function that can only ever
be exercised by hand in a browser.

Making the input a parameter with a sensible default costs one line and turns an untestable
boundary into a testable one:

```php
$input = decodeJsonBody($request);                       // production: reads the request
$input = decodeJsonBody($request, '{"title":"Test"}');   // test: reads what you gave it
```

Watch for this shape generally. A function that reaches out to a global, a clock, a filesystem
or a network is a function whose behaviour you cannot pin down; passing the dependency in makes
it ordinary. FS06.1 is where this decision is collected.

Now the allowlist. Never save "whatever the client sent":

```php
$input = decodeJsonBody($request);

$errors = [];

$title = is_string($input['title'] ?? null) ? trim($input['title']) : '';
if ($title === '') {
    $errors['title'] = 'Required';
} elseif (mb_strlen($title) > 200) {
    $errors['title'] = 'Must be 200 characters or fewer';
}

$projectId = is_string($input['project_id'] ?? null) ? $input['project_id'] : null;
if ($projectId === null) {
    $errors['project_id'] = 'Required';
}

// Optional field: absent is fine, present-and-wrong is not.
$status = $input['status'] ?? 'todo';
if (!in_array($status, ['todo', 'in_progress', 'done'], true)) {
    $errors['status'] = 'Must be todo, in_progress, or done';
}

if ($errors !== []) {
    return Response::json(['error' => [
        'code' => 'validation_failed',
        'message' => 'The issue could not be created.',
        'fields' => $errors,
    ]], 422);
}
```

Read what that code does *not* do. It never iterates the client's keys. `is_admin`,
`creator_id`, `created_at` and any field someone invents next year are not present in the
allowlist, so they cannot reach the database — and crucially, no future change to the
`issues` table can make them writable by accident. That is prediction 2's answer, and it is
the difference between a validator and a filter: a filter removes what you thought to
forbid, an allowlist admits only what you decided to accept.

Collecting every error before returning also matters. Returning on the first failure makes
a user fix one field, resubmit, and discover a second — three round trips for one form.

Validation here helps a person correct a request. It does not replace PostgreSQL
constraints; FS05.2 makes invalid states hard even when this handler is bypassed entirely.

## 4. Register and prove one route at a time

Routes live in `routes/routes.php`; file handlers resolve under `app/Http/controllers/`.
Register the smallest real thing first:

```php
// routes/routes.php
$router->get('/api/issues/{id}', 'api/issues/show.php');
```

```php
<?php
// app/Http/controllers/api/issues/show.php

declare(strict_types=1);

use Core\App;
use Core\Request;
use Core\Response;

$request = App::resolve(Request::class);
$id = $request->route('id');

// Persistence arrives in FS05.3. This proves routing and the envelope, nothing more.
return Response::json(['data' => ['id' => $id, 'title' => 'placeholder', 'status' => 'todo']]);
```

A handler may also return a plain array and DALT will encode it as JSON with a 200, which
is convenient for the happy path — but return `Response::json(...)` explicitly whenever the
status matters, because that is most of the contract.

Now prove it with curl before React ever touches it:

```sh
php artisan serve &
curl -i http://127.0.0.1:8000/api/issues/42
```

```text
HTTP/1.1 200 OK
Content-Type: application/json; charset=UTF-8

{"data":{"id":"42","title":"placeholder","status":"todo"}}
```

Then check the negative case, which is the one that tells you the route matcher works:

```sh
curl -i -X POST http://127.0.0.1:8000/api/issues/42    # 404: no POST route for this path
curl -i http://127.0.0.1:8000/api/issues               # 404: collection route not registered yet
```

Testing only through React merges a route mistake, a CORS problem, a parser bug and a
rendering error into one vague failure. curl separates them. Every minute spent here is
returned with interest the first time something breaks.

### Answer the browser before React does

curl just proved the route. It did not prove the route works from a browser, and it never
will — curl has no origin policy, so it cannot reproduce the one failure mode that is
guaranteed to appear the moment FS05.3 points React at this server instead of the fixture.
A cross-origin request (Vite on `5173`, DALT on `8000`) is subject to two browser rules that
curl simply does not implement:

1. A "non-simple" request — `POST`/`PATCH`/`PUT`/`DELETE` with a JSON body, which is every
   mutation this API has — is preceded by an automatic `OPTIONS` preflight. The browser
   sends it; your code never asked for it.
2. **Every** cross-origin response, preflighted or not, must carry
   `Access-Control-Allow-Origin` naming the requesting origin (or `*`). Without it the
   browser discards the response before JavaScript ever sees it — the request still
   completes and still shows in your server logs, which is exactly why this looks like a
   frontend bug and is not one.

`Core\Router` has `get`/`post`/`patch`/`put`/`delete`/`options`, and none of your five
routes answers `OPTIONS`, so a preflight 404s before your handler runs. Registering
`OPTIONS` for every resource path by hand would work but does not scale past the second
resource, and an ordinary `{id}` placeholder cannot help — it matches one path segment, not
a whole subtree. This is the one place in Part 05 you need a route that matches *any* path
under a prefix, which is what `{*}` as the final path segment means: it matches the prefix
itself and everything after it, slashes included.

```php
// routes/routes.php
require base_path('app/Http/support/api-response.php');

$router->add('OPTIONS', '/api/{*}', fn () => new Response('', 204, corsHeaders()));
```

That answers rule 1 for every current and future `/api/*` route in one line. Rule 2 still
needs every *real* response — not just the preflight — to carry the same origin header, so
wrap the envelope you already agreed on instead of calling `Response::json()` directly:

```php
<?php
// app/Http/support/api-response.php

declare(strict_types=1);

use Core\Response;

/**
 * The allowed origin is configuration, not a constant: it is your Vite dev
 * server today and a deployed frontend origin later. `*` is tempting and
 * wrong here — a wildcard origin cannot be combined with credentialed
 * requests, and it hides which origins you actually intend to serve.
 *
 * @return array<string, string>
 */
function corsHeaders(): array
{
    return [
        'Access-Control-Allow-Origin' => env('CORS_ALLOWED_ORIGIN', 'http://localhost:5173'),
        'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type',
    ];
}

/**
 * Every handler in Part 05 onward returns through this instead of
 * `Response::json()` directly, so no response can ship without the header
 * its own preflight promised.
 *
 * @param array<array-key, mixed> $data
 * @param array<string, string> $headers
 */
function apiJson(array $data, int $status = 200, array $headers = []): Response
{
    return Response::json($data, $status, corsHeaders() + $headers);
}
```

`app/Http/support/` holds plain functions, not `App\Http\`-namespaced classes, so
Composer's autoloader never sees it — the `require` above is not boilerplate, it is the
only reason `apiJson` and `corsHeaders` exist by the time a route resolves. Miss it and
every symptom in this section returns despite the code above being correct.

From here on, replace `Response::json(...)` with `apiJson(...)` in every handler this
lesson and FS05.3 write — same arguments, same envelope, now with the header a browser
actually requires. Prove both rules with curl, which can inspect headers even though it
does not enforce them:

```sh
php artisan serve &
curl -i -X OPTIONS http://127.0.0.1:8000/api/issues \
  -H 'Origin: http://localhost:5173' \
  -H 'Access-Control-Request-Method: POST'
curl -i http://127.0.0.1:8000/api/issues/42 -H 'Origin: http://localhost:5173'
```

```text
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: http://localhost:5173
Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS

HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://localhost:5173
Content-Type: application/json; charset=UTF-8
```

If the second response is missing its `Access-Control-Allow-Origin` line, a handler is
still calling `Response::json()` directly — that is the whole defect class this section
exists to close before React can rediscover it the hard way.

## 5. Decide what a response is allowed to contain

A contract constrains the response as much as the request. The shortest path from a database
row to JSON is to send the row, and it is the wrong one:

```php
// Do not do this. The response is now "whatever columns exist today".
$issue = $db->query('SELECT * FROM issues WHERE id = ?', [$id])->find();
return Response::json(['data' => $issue]);
```

Two failures follow from that single line, and both arrive later, when nobody is looking at
this file. The first is exposure: add an `internal_notes` column in Part 08 and it is
published to every client, because nothing here says which fields are public. The second is
coupling: `SELECT *` makes your JSON contract a mirror of your schema, so renaming a column
becomes a breaking API change and the frontend team finds out in production.

Map explicitly instead, in one function per resource:

```php
/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function issueResource(array $row): array
{
    return [
        'id' => (string) $row['id'],
        'title' => $row['title'],
        'status' => $row['status'],
        'priority' => $row['priority'],
        'project_id' => (string) $row['project_id'],
        'created_at' => $row['created_at'],
    ];
}
```

Now the contract is a list you can read, and adding a column to the table changes nothing
about the API until someone edits this function on purpose. The `(string)` casts are
deliberate too: PostgreSQL returns a `BIGSERIAL` id as an integer, JSON has one numeric type
with no integer guarantee above 2^53, and JavaScript will happily lose precision on a large
id. Sending ids as strings costs nothing and removes a class of bug that is extremely
unpleasant to diagnose.

That is prediction 4's answer, incidentally. Nothing *guarantees* that a component receives
the shape the handler returned — not a PHP return type, not a TypeScript interface. The
guarantee is manufactured by two cooperating pieces: this mapping function on the way out,
and FS04.3's parser on the way in. Either one alone is a hope.

## Try it

Write a one-page API contract for the seven operations above, with an example success, 404,
422 and malformed-JSON response for each that can produce one. Then add only
`GET /api/issues/{id}` as above, run it under curl, and confirm the body matches what you
wrote down.

Change the route pattern to `/api/issues/{issue}` without changing the handler, and observe
`$request->route('id')` return null — the placeholder name is the parameter name. Restore the
version your contract names. Connect no database in this experiment.

## Common mistakes

- Choosing routes by copying a framework tutorial instead of naming current domain actions.
- Returning 200 for a missing item because the handler did not throw.
- Treating `Request::input()` as parsed JSON. It reads `$_POST` and will be empty.
- Answering 400 for a validation failure, leaving the form with no field to highlight.
- Returning database rows directly, including internal columns nobody agreed to expose.
- Letting a client submit fields the endpoint never agreed to accept.
- Returning on the first validation error, so a three-field form takes three submissions.
- Treating frontend TypeScript or a client parser as server-side validation.

## When this goes wrong

If a route 404s, compare method and path before touching the controller — PATCH and POST to
the same path are different routes, and a missing one falls through to the 404 at the end of
the matcher. If a path value is null, check the placeholder name against the argument you
passed to `Request::route()`; they must match exactly.

If your handler receives an empty body, confirm you are reading `php://input` and not
`Request::input()`, and confirm curl actually sent the body — `-d` without `-X POST` on some
versions changes the method for you, which is convenient until it is not. If React reports a
malformed response, save the raw bytes and compare them with the written envelope, then fix
whichever side broke the agreement. If curl works and the browser does not, the difference is
the browser: check origin and preflight, and do not weaken the contract to hide a policy you
have not read.

## Exercise

**Goal:** Turn the B04 fixture behaviour into an explicit DALT API contract.

**Starting state:** Your typed React client names list, create, update, and delete operations
against the resettable fixture.

**Requirements:** Document project and issue routes, response envelopes, accepted input, and
200/201/204/400/404/422 outcomes. Register one real DALT GET route returning only the
documented shape. Implement `decodeJsonBody` and one allowlisted create validation that
collects all field errors. Make the client parser accept the success response and reject a
deliberately changed field.

**Verification:** Run curl for success, a missing path, malformed JSON, and an invalid field.
Inspect status and JSON for each. Then use Network to verify the client invokes the same
contract.

**Mode: manual HTTP evidence, browser evidence, and code review.** This is a design boundary;
it is not automatically graded.

**Hints:** Begin with issue detail — one identifier, one response. Keep SQL out of this first
handler entirely. Write the error envelope before the happy path, so the form has a usable
recovery shape from the first commit.

## In the project

Your B04 API client stays the React boundary. This lesson replaces its assumption that a
course fixture owns the URL and the behaviour. FS05.2 gives the contract durable facts, and
FS05.3 maps those facts through parameterized queries and transactions. Because FS04.3 put
the base URL and error parsing in one module, pointing React at your own server should be a
one-line change — and if it is not, that is worth knowing now rather than in Part 07.

## Closed-book checkpoint

1. What is the difference between a path parameter, query parameter, and request body?
2. Why is an empty collection normally 200 while a missing item is 404?
3. When is 400 correct and when is 422 correct?
4. What must a consistent error envelope preserve for a form?
5. Why must the server allowlist fields even when React has a typed form?
6. Why does `Request::input()` not contain a JSON request body?

## Resources

### Read

- [MDN: HTTP response status codes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status)
- [MDN: Using the Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch)
- [Full Stack Open Part 3](https://fullstackopen.com/en/part3)

### Go deeper

- [MDN: 422 Unprocessable Content](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/422)
- [Laravel: responses](https://laravel.com/docs/12.x/responses)
- [Laravel: validation](https://laravel.com/docs/12.x/validation)

## You are done when

- [ ] I can name each initial route, method, input, success body, and failure body.
- [ ] A missing item, empty list, invalid input, and malformed JSON make four different claims.
- [ ] One DALT route is proven with curl before React uses it.
- [ ] `decodeJsonBody` exists, is mine, and returns 400 for unreadable bodies.
- [ ] The server accepts only an explicit subset of input fields.
- [ ] A create request with three bad fields reports three field errors, not one.
- [ ] React parses the documented response rather than trusting its TypeScript type.
- [ ] `OPTIONS /api/{*}` answers a preflight, and a real response carries
      `Access-Control-Allow-Origin` because it went through `apiJson()`, not `Response::json()`.
- [ ] I know that persistence, SQL, and database constraints arrive in the next lessons.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_03.md`
- Official sources: MDN HTTP status and Fetch references; Laravel responses and validation comparison documentation
- Versions: PHP 8.4; DALT current `Router`, `Request`, and `Response` APIs; React 19.2.3
- Consulted: 2026-08-14
- DALT files inspected: `framework/Core/Router.php`; `framework/Core/Request.php`; `framework/Core/Response.php`; `framework/Core/HttpException.php`; `routes/routes.php`
- Curriculum authority: `CURRICULUM.md` §15 FS05.1 — practical API agreement, not REST purity
- Laravel bridge: Laravel route responses and validation provide the production-framework comparison; DALT uses explicit route handlers and `Response::json()` here
