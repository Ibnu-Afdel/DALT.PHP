# FS04.3 — Separating transport from UI

Lesson ID: FS04.3  
Title: Separating transport from UI  
Part: 04 — React and server  
Order: 3  
Status: Published  
Estimated effort: 90–120 minutes  
Difficulty: Integration  
Prerequisites: FS04.2 — Mutating server data  
Project milestone: B04 — First full-stack loop  
Primary source dossier: `FSO_PART_02.md`  
Last reviewed: 2026-08-14

## Why this matters

Putting the first `fetch` in a component was useful: it made the request lifecycle visible. Repeating headers, status checks, URL strings, JSON parsing, and error wording in every component is not useful. It makes a component answer two unrelated questions at once: "what should this screen do?" and "how are bytes transported over HTTP?"

The answer is a small client module, introduced only now that you have felt the duplication. It is not an architecture astronaut's layer cake. It is one boundary with typed functions whose names describe application operations: list issues, create issue, change status, remove issue. Components call those operations and render their result. One place owns URL construction, request conventions, response validation, and normalized errors.

There is a concrete payoff waiting in Part 05. When the fixture is replaced by your own DALT routes, the base URL changes, the error envelope may change, and the ids stop looking like `ISS-41`. If those facts live in one module, that is a morning's work. If they live in nine components, it is a rewrite.

## Before you start

Required:

- FS04.2 — Mutating server data.
- A project with the fixture's GET, POST, PATCH, and DELETE flows working.

Recommended first:

- FS02.4 — Functions, generics and reusable types.
- FS02.5 — Runtime boundaries.

Going deeper in DALT Core — optional:

- [02-routing](/learn/lessons/02-routing) — how the later DALT route layer chooses a handler. It is background, not a prerequisite.

## By the end

- separate domain types from HTTP details;
- create a typed client module with one public function per operation;
- normalize network, HTTP, parse, and shape failures into useful errors;
- validate an unknown response at the runtime boundary;
- keep components responsible for UI state and API functions responsible for transport;
- explain why this is not yet a generic data-fetching framework.

## Predict before reading

1. If every component calls `fetch`, where will you change the base URL in six months?
2. Can a TypeScript return type prove that a malicious or broken server sent that shape?
3. Which layer should decide that a 422 means "show the form error": the HTTP helper or the form component?
4. Would an API client that knows about buttons, JSX, or `setState` be a cleaner boundary?

## Mental model

```text
component → application action → issueApi function → HTTP → unknown JSON
    ↑              ↑                    ↓                        ↓
UI state       domain intent         typed Issue             runtime parser
```

The client module is the translation boundary. Its input is a domain value such as `{ title: string }`; its output is a domain value such as `Issue`. Internally it deals with URLs, methods, headers, status codes, and bytes. The component owns user intent, draft state, pending state, selection, and rendering. Neither layer should borrow the other's vocabulary.

A quick test for whether the boundary is real: could you swap `fetch` for a WebSocket, or for an in-memory fake in a test, without touching a single component? If yes, the boundary holds. If a component would notice, transport has leaked upward.

## 1. Name operations, not endpoints

Create `src/api/issues.ts`. The public surface is four functions named after application actions:

```ts
import type { Issue, IssueStatus } from '../issue';

const BASE_URL = 'http://127.0.0.1:8034';

export async function listIssues(signal?: AbortSignal): Promise<Issue[]> {
  const body = await requestJson('GET', '/api/issues', undefined, signal);
  return parseIssueList(body);
}

export async function createIssue(input: { title: string }): Promise<Issue> {
  return parseIssue(await requestJson('POST', '/api/issues', input));
}

export async function updateIssueStatus(id: string, status: IssueStatus): Promise<Issue> {
  return parseIssue(await requestJson('PATCH', `/api/issues/${encodeURIComponent(id)}`, { status }));
}

export async function deleteIssue(id: string): Promise<void> {
  await requestJson('DELETE', `/api/issues/${encodeURIComponent(id)}`);
}
```

The component now says `await createIssue({ title })`, which is the application action. It does not assemble a URL or decide a header. Do not export a vague `request(method, path, body)` as the only public API: that just moves `fetch` behind a thinner name while endpoint strings keep spreading through the UI. Public operations make the permitted behaviour readable — you can see everything this application can do to an issue by reading four export lines.

`encodeURIComponent` is not decoration. An id containing `/` or `?` would otherwise change which URL you requested, and ids come from the server, not from you. The fixture's ids happen to be safe today; relying on that is relying on a property nobody promised, and Part 05 is where you start generating ids yourself.

The `signal` parameter appears on `listIssues` and nowhere else, which is a deliberate asymmetry rather than an oversight. Reads are abandoned all the time — a user navigates, a filter changes, a component unmounts — and abandoning them is free. Writes are different: aborting a POST does not un-create the issue, because the request may already have reached the server. Cancelling a write cancels your knowledge of the outcome, not the outcome itself. Add a signal to a mutation only when you have decided what an abandoned write means.

The shared internals sit below, unexported:

```ts
async function requestJson(
  method: string,
  path: string,
  body?: unknown,
  signal?: AbortSignal,
): Promise<unknown> {
  let response: Response;

  try {
    response = await fetch(BASE_URL + path, {
      method,
      headers: body === undefined ? undefined : { 'Content-Type': 'application/json' },
      body: body === undefined ? undefined : JSON.stringify(body),
      signal,
    });
  } catch (cause) {
    if (cause instanceof DOMException && cause.name === 'AbortError') throw cause;
    throw new ApiError('Could not reach the server.', { cause });
  }

  if (response.status === 204) return undefined;      // nothing to parse
  if (response.ok) return response.json();

  throw await errorFromResponse(response);
}
```

Note that `deleteIssue` reuses `requestJson` rather than getting its own special path. The 204 branch lives in one place, which is the argument for the helper: the rule "a 204 has no body" is now stated once, and cannot be forgotten in the fifth operation someone adds next year.

## 2. Make the runtime boundary real

The server response is `unknown` no matter what a TypeScript annotation says. A return type is a promise you make to the compiler; it is not evidence about bytes that arrived over a network. That is prediction 2, and the answer is no — a type annotation on network data is a claim, and the parser is what turns it into a fact.

```ts
const STATUSES = ['todo', 'in_progress', 'done'] as const;
const PRIORITIES = ['low', 'medium', 'high'] as const;

function isIssue(value: unknown): value is Issue {
  if (typeof value !== 'object' || value === null) return false;
  const record = value as Record<string, unknown>;

  return typeof record.id === 'string'
    && record.id !== ''
    && typeof record.projectId === 'string'
    && typeof record.title === 'string'
    && STATUSES.includes(record.status as IssueStatus)
    && PRIORITIES.includes(record.priority as Issue['priority']);
}

function parseIssue(value: unknown): Issue {
  if (!isIssue(value)) {
    throw new ApiError('The server returned an issue this application cannot read.');
  }
  return value;
}

function parseIssueList(value: unknown): Issue[] {
  if (!Array.isArray(value) || !value.every(isIssue)) {
    throw new ApiError('The server returned an issue list this application cannot read.');
  }
  return value;
}
```

`parseIssueList` is what finally pays off the debt FS04.1 left behind. There, `Array.isArray(data)` followed by `data as Issue[]` established array-ness and asserted everything else. `value.every(isIssue)` checks each member, so a response of `[1, 2, 3]` is now rejected at the boundary instead of producing `undefined` somewhere in your JSX three renders later.

This is deliberately repetitive rather than magical. You are making the trust decision visible: these are exactly the fields this application requires, and anything else is a server it does not understand. A schema library such as Zod or Valibot reduces the syntax considerably, and adopting one later is reasonable — but it cannot remove the obligation to decide what data is acceptable. It only changes where you write the decision down.

## 3. Normalize errors without erasing information

Errors crossing this boundary need a shape, or every caller invents its own guesswork:

```ts
export class ApiError extends Error {
  readonly status?: number;
  readonly code?: string;

  constructor(message: string, options: { status?: number; code?: string; cause?: unknown } = {}) {
    super(message, { cause: options.cause });
    this.name = 'ApiError';
    this.status = options.status;
    this.code = options.code;
  }
}

async function errorFromResponse(response: Response): Promise<ApiError> {
  let code: string | undefined;
  let message = `The server returned ${response.status}.`;

  try {
    const body: unknown = await response.json();
    if (typeof body === 'object' && body !== null && 'error' in body) {
      const error = body.error as Record<string, unknown>;
      if (typeof error.code === 'string') code = error.code;
      if (typeof error.message === 'string') message = error.message;
    }
  } catch {
    // A non-JSON error body is normal — proxies emit HTML. Keep the status message.
  }

  return new ApiError(message, { status: response.status, code });
}
```

Three pieces of information survive: a status, a machine-readable code, and a human message. That is what a caller needs in order to behave differently, and it is exactly what a `catch (e) { setError('Something went wrong') }` throws away.

The `try` around the error body matters more than it looks. When a proxy or the dev server returns a 502 with an HTML page, `response.json()` rejects — and if that rejection escapes, your user sees a JSON parsing error instead of "the server returned 502". An error path that can itself fail is where debugging sessions go to die.

Crucially, the client does not decide presentation:

```tsx
try {
  const issue = await createIssue({ title: draft });
  setIssues((current) => [...current, issue]);
  setDraft('');
} catch (error) {
  if (error instanceof ApiError && error.status === 422) {
    setFormError(error.message);          // recoverable: keep the draft
  } else {
    setBannerError('Could not create the issue. Try again.');
  }
}
```

The client says "validation failed, status 422, here is the server's message." The form decides that this message belongs beside the input and that the draft survives. The client says "request failed with no response." The screen decides whether a retry button is appropriate. That is prediction 3's answer: translation belongs to transport, recovery presentation belongs to the UI.

## 4. Refactor one flow at a time

Move GET first and confirm the same loading, empty, and failure behaviours still exist. Then POST, then PATCH, then DELETE. After each move, run the application and inspect Network. A refactor that changes both endpoint mechanics and UI state at once gives you no useful clue when it breaks — you will not know whether you moved the code wrongly or changed behaviour, and you will end up reverting both.

Your effect becomes legible, and keeps its cancellation:

```tsx
useEffect(() => {
  const controller = new AbortController();

  listIssues(controller.signal)
    .then((issues) => setState({ kind: 'ready', issues }))
    .catch((error: unknown) => {
      if (controller.signal.aborted) return;
      setState({ kind: 'failed', message: messageFor(error) });
    });

  return () => controller.abort();
}, []);
```

This is not a recommendation to prefer `.then` over `async`/`await`; either is fine. The point is that the component no longer contains a URL, a header, a status check, or a parser — and it still aborts. A clean module is not permission to lose race protection, which is why `listIssues` takes a signal and passes it straight through to `fetch`. That parameter is the one piece of transport vocabulary the component is allowed to know, and it is there because ownership of a request genuinely is the component's business.

## 5. The base URL is configuration, not a constant

`const BASE_URL = 'http://127.0.0.1:8034'` was fine while the fixture was the only server.
It stops being fine the moment there is more than one place your application runs, which for
this project is Part 05 — your own DALT server on a different port — and Part 10, where it
runs inside Docker under a name that does not exist on your laptop.

Vite reads environment variables at build time and exposes those prefixed with `VITE_`:

```ts
// src/api/config.ts
const configured = import.meta.env.VITE_API_BASE_URL;

if (typeof configured !== 'string' || configured === '') {
  // Fail at startup with a sentence, rather than at the first request with a 404.
  throw new Error('VITE_API_BASE_URL is not set. Copy .env.example to .env.local.');
}

export const BASE_URL = configured.replace(/\/$/, '');
```

```sh
# .env.local — not committed
VITE_API_BASE_URL=http://127.0.0.1:8034
```

Three small decisions there are worth copying. Checking the value at module load turns a
missing variable into one clear message instead of a series of confusing request failures.
Trimming a trailing slash prevents `//api/issues`, which some servers route and some do not.
And the prefix is not optional: Vite deliberately refuses to expose unprefixed variables to
client code, because everything in this file ships to the browser. That constraint is a
feature — it means you cannot accidentally put a database password here, and it is worth
internalising now, because the temptation arrives in Part 06.

Add `.env.local` to `.gitignore` and commit a `.env.example` showing the variable with a
placeholder value. The next person to clone the repository then learns what they must set,
without receiving your machine's configuration.

## 6. What this module must not become

The pressure now is to keep generalising, and it should be resisted:

```ts
// Do not do this. Four operations do not need a framework.
export class Repository<T> {
  constructor(private resource: string, private schema: Schema<T>) {}
  findAll(query?: QueryOptions): Promise<Paginated<T>> { /* ... */ }
  findOne(id: Id): Promise<T | null> { /* ... */ }
}
```

That class has more concepts in its signature than the application has features. It also quietly invents requirements — pagination, nullable lookups, a query DSL — that no screen has asked for, and each one has to be maintained and understood forever.

A second pressure is to add caching, retries, or request deduplication here, because those
are genuinely useful and the module looks like the natural home for them. They are also each
a design problem with a wrong answer that is hard to see: a cache needs an invalidation rule,
a retry needs to know which methods are safe to repeat, and deduplication needs to decide
what counts as the same request. Adding any of them by reflex means adopting a policy you
have not chosen. This is what data-fetching libraries exist to supply, and Part 08 is where
TanStack Query arrives to supply them — with real requirements in hand rather than imagined
ones.

Nor should the module ever import React. If `issues.ts` contains a `setState`, a hook, or JSX, the boundary has inverted: transport now depends on UI, which is prediction 4's answer and the exact thing that makes an API client untestable. The module should run unchanged in a Node script.

Keep it four functions, one helper, one error class, and the parsers. Part 05 will change the base URL and the error envelope; Part 07 may add routing-driven parameters. Generalise then, against real requirements, rather than now against imagined ones.

One immediate dividend is worth noticing before you move on. Because the parsers are ordinary functions over `unknown`, they are testable without a server, a browser, or a component:

```ts
import { expect, test } from 'vitest';

test('rejects an issue list whose members are not issues', () => {
  expect(() => parseIssueList([1, 2, 3])).toThrow(ApiError);
});

test('rejects an unknown status the UI cannot render', () => {
  expect(() => parseIssue({ id: 'ISS-1', projectId: 'PRJ-1', title: 'x', status: 'archived', priority: 'low' }))
    .toThrow(ApiError);
});
```

Those two tests encode the decision you made in §2 — which shapes this application accepts — in a form that survives you. Export the parsers for tests if your setup requires it; that is a reasonable exception to keeping internals private. Part 07 tests components against a faked client; these tests are the layer below that, and they are the cheapest tests in the whole track.

## Try it

Search your application source for `fetch(` when you finish. The only match should be inside the client module. Then, one at a time:

```sh
# 1. Break the base URL in the module. Every flow should fail understandably.
# 2. Restore it, and make parseIssue reject a valid field (e.g. require record.id === 'x').
#    The UI must show a failure, not render malformed data.
# 3. Restore it, and confirm all four flows still work.
```

Step 2 is the plausible-fake check on your own parser. A parser that never rejects anything is indistinguishable from no parser at all, and you will not know which you wrote until you make it reject something on purpose.

## Common mistakes

- Moving only the URL string while leaving response parsing and status checks in components.
- Returning `any` or using assertions to make invalid JSON look typed.
- Catching every error and replacing it with "Something went wrong," losing 422 versus network information.
- Letting the error-parsing path itself throw on a non-JSON error body.
- Putting React state setters or JSX in the client module.
- Creating a generic repository framework for four functions.
- Dropping abort support during the extraction, reintroducing the FS04.1 race.
- Refactoring all operations at once and losing the known-good checkpoint.

## When this goes wrong

If the page breaks after extraction, compare the Network request with the pre-refactor request field by field: method, URL, headers, body, status, response. The bug is almost always a header you dropped or a path you rebuilt slightly differently, and the diff finds it faster than reading the code.

If TypeScript reports a type as unused, it may belong in the domain module rather than the transport module — domain types describe the application, and only transport types should live beside `fetch`. If an error is shown as generic when it used to be specific, log the caught object before rendering: information is being discarded somewhere between `errorFromResponse` and the component, and the discard is usually a `catch` that forgot to re-throw.

## Exercise

**Goal:** Move all fixture communication into a small typed issue API module.

**Starting state:** FS04.2 has working fetch calls in components.

**Requirements:** Export one function each for list, create, status update, and delete. Parse unknown response data — including every member of a list — before returning it. Normalize errors with status, code, and message, while keeping components responsible for pending state and presentation. Preserve abort support. Leave no `fetch(` outside the module and no React import inside it.

**Verification:** Search for `fetch(`, execute all four flows, inspect each request in Network, stop the fixture once, and deliberately cause a parser rejection. Confirm the UI still distinguishes its outcomes.

**Mode: manual browser evidence, code search, and TypeScript compiler output.** The module is not automatically graded; the evidence proves a boundary that actually works.

**Hints:** Extract the GET function verbatim first, then improve its return parser. Write `requestJson` only after two operations repeat the same mechanics — you have four, so this is safe. Keep the 204 branch inside the helper.

## In the project

This completes B04's server seam. Your React components now express issue-tracker work, and a small client layer owns HTTP. In Part 05 the fixture's base URL and temporary behaviour are replaced by application-owned DALT routes and persistent PostgreSQL data. The API client survives that replacement because components depend on its domain contract, not on its fixture URL — which is the entire return on the work you just did.

## Closed-book checkpoint

1. What belongs in a component and what belongs in an API client?
2. Why does a return type not validate a server response?
3. Which error information must be preserved for a form to recover from 422?
4. Why does the 204 branch belong in the shared helper rather than in `deleteIssue`?
5. What makes this module a boundary rather than an abstraction for its own sake?
6. Why must the error-parsing path tolerate a non-JSON body?

## Resources

### Read

- [TypeScript: narrowing](https://www.typescriptlang.org/docs/handbook/2/narrowing.html)
- [MDN: `Response.ok`](https://developer.mozilla.org/en-US/docs/Web/API/Response/ok)
- [React: you might not need an effect](https://react.dev/learn/you-might-not-need-an-effect)

### Go deeper

- [MDN: `Response.json()`](https://developer.mozilla.org/en-US/docs/Web/API/Response/json)
- [MDN: `Error` cause](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Error/cause)
- [Full Stack Open Part 2](https://fullstackopen.com/en/part2)

## You are done when

- [ ] `fetch(` appears only in my API client module.
- [ ] The module exports typed list, create, update-status, and delete operations.
- [ ] Every JSON response is checked from `unknown` before the UI receives it, including list members.
- [ ] 422, HTTP failures, and no-response failures retain different useful evidence.
- [ ] The module imports nothing from React.
- [ ] Aborting still works after the extraction.
- [ ] Components still own drafts, pending states, selection, and error placement.
- [ ] GET, POST, PATCH, and DELETE all work after the extraction.
- [ ] `npm run typecheck`, `npm run lint`, and `npm run test` pass.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_02.md`; `docs/dalt-fullstack/sources/TYPESCRIPT_HANDBOOK.md`
- Official sources: TypeScript narrowing handbook; MDN Response and Error APIs; React effects guidance; Full Stack Open Part 2 as pedagogy source
- Versions: React 19.2.3; TypeScript 5.9.3; PHP 8.4 fixture server
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/course/fullstack/react-server-fixture/fixture-api.php`; existing Fullstack TypeScript runtime-boundary lab
- Curriculum authority: `CURRICULUM.md` §14 FS04.3 — client layer only, explicitly no over-architecture
- Laravel bridge: optional only — the client's transport boundary is framework-neutral; DALT/Laravel route implementation arrives in Part 05
