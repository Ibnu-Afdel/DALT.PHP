# FS04.1 — Fetching data and effects

Lesson ID: FS04.1  
Title: Fetching data and effects  
Part: 04 — React and server  
Order: 1  
Status: Published  
Estimated effort: 90–120 minutes  
Difficulty: Applied  
Prerequisites: FS03.4 — Tailwind and accessible application UI  
Project milestone: B04 — First full-stack loop  
Primary source dossier: `FSO_PART_02.md`  
Last reviewed: 2026-08-14

## Why this matters

Until now the issue list was a local array. Reading it was immediate and could not fail, because the browser owned every value. A real application begins with a different fact: the useful data lives elsewhere. The browser asks for a representation, waits, and then decides what that representation means on screen.

That wait is not an implementation detail. Before data arrives, the list is neither empty nor loaded; it is unknown. If the request fails and you show an empty-state message, you have told the user a lie with a friendly illustration on it. If the server answers 500, `fetch` still resolves successfully, so treating a resolved promise as success is a second lie. And a component that starts a request while rendering will start another on every state update, which is how a page ends up making four hundred requests to a healthy server.

This lesson makes those states explicit while the data is still read-only. Part 04's next lesson adds mutations, and mutations are much harder to reason about if "what is on screen right now" is already ambiguous.

## Before you start

Required:

- FS03.4 — Tailwind and accessible application UI.
- Your B03 issue tracker, still driven by typed local data.

Recommended first:

- FS01.2 — Modules, async JavaScript and failure.
- FS02.5 — Runtime boundaries.

Going deeper in DALT Core — optional:

- [01-request-lifecycle](/learn/lessons/01-request-lifecycle) — a framework-level request trace. It is optional; this lesson teaches the browser boundary it needs.

Copy the resettable fixture into your workspace and run it in one terminal:

```sh
mkdir -p .dalt/workspace/fs04-react-server
cp .dalt/course/fullstack/react-server-fixture/fixture-api.php .dalt/workspace/fs04-react-server/
cd .dalt/workspace/fs04-react-server
php -S 127.0.0.1:8034 fixture-api.php
```

In another terminal, confirm the actual response before you write any React:

```sh
curl -i http://127.0.0.1:8034/api/issues
```

You should see this, and you should read it rather than skim it:

```text
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8
Vary: Origin

[{"id":"ISS-41","title":"Show a loading state","status":"todo","priority":"high"}, ...]
```

Restarting the fixture resets its state. It is course material living in the gitignored workspace — not an edit to the framework skeleton, and not the backend you will build in Part 05.

## By the end

- distinguish loading, empty, network failure, and HTTP failure;
- explain why `fetch` does not reject for HTTP 404 or 500;
- load an issue list in an effect without rendering a Promise;
- use a dependency array as a statement about values an effect reads;
- preserve the server response as the source of the displayed list;
- name one race that cleanup prevents.

## Predict before reading

1. What renders if `useState<Issue[]>([])` is the only state and a request has not finished yet?
2. Does `await fetch('/missing')` throw for a 404? Predict before trying it.
3. If an effect reads `projectId` but has `[]`, what happens after `projectId` changes?
4. If request A starts, request B starts later, and A finishes last, which response should the UI show?

## Mental model

```text
render → start request → waiting → response or failure → set state → render
                     \                         /
                      effect: synchronise React with something outside React
```

Rendering describes the screen for the current state. It must be pure: it should not begin a request every time React asks what to show. Concretely, this is wrong:

```tsx
function IssueBoard() {
  const [issues, setIssues] = useState<Issue[]>([]);

  // Wrong. Runs during render, so setIssues triggers a render,
  // which runs this again, which fetches again, forever.
  fetch('http://127.0.0.1:8034/api/issues')
    .then((r) => r.json())
    .then(setIssues);

  return <IssueList issues={issues} />;
}
```

An effect runs *after* React commits a render, and is where a component synchronises with a system it does not own: a network, a timer, a subscription, a browser API. Fetching initial data is one of those jobs.

`fetch` has two failure channels, and conflating them is the most common bug in this lesson. A **network failure** means no usable HTTP response arrived — offline, DNS failure, connection refused, or an aborted request. That rejects the promise. An **HTTP failure** means the server did reply, with a status such as 404 or 500. The promise resolves normally, and you must inspect `response.ok` yourself. JSON parsing is a third boundary: a 200 response can still contain invalid JSON, or valid JSON in a shape your UI cannot safely use.

## 1. Model the request state honestly

Do not use an empty array for both "nothing has arrived" and "the server says there are no issues." Use a small discriminated union instead:

```ts
// src/load-state.ts
import type { Issue } from './issue';

export type LoadState =
  | { kind: 'loading' }
  | { kind: 'ready'; issues: Issue[] }
  | { kind: 'failed'; message: string };
```

The names matter less than the separation. `loading` has no list because no list is known. `ready` can carry an empty `issues` array, which is an honest empty state. `failed` carries a message, because retrying and inspecting are real actions the user might take. This is the same union-and-narrowing discipline from FS02.3, applied to a browser lifecycle instead of a parser.

Because the union is discriminated, the render is a `switch` with no impossible branches and no optional chaining:

```tsx
function IssueBoard() {
  const [state, setState] = useState<LoadState>({ kind: 'loading' });

  switch (state.kind) {
    case 'loading':
      return <p role="status">Loading issues…</p>;

    case 'failed':
      return (
        <p role="alert" className="text-red-700">
          Could not load issues: {state.message}
        </p>
      );

    case 'ready':
      return state.issues.length === 0 ? (
        <p>No issues yet. Create the first one.</p>
      ) : (
        <IssueList issues={state.issues} />
      );
  }
}
```

Two accessibility choices are doing real work there. `role="status"` announces politely — it waits for a pause in speech, which is right for "something is happening." `role="alert"` interrupts, which is right for "the thing you asked for did not happen." Using `alert` for the loading message would make a screen reader interrupt the user every time a list refreshed. Using `status` for the failure would let the failure pass unannounced.

Note also what is *not* in `LoadState`: no `isLoading` boolean alongside a `data` field. The boolean-plus-data shape permits `{ isLoading: true, error: 'Server returned 500', issues: [...] }`, a state that means nothing. FS02.3's point was that a type should not be able to express a situation your code cannot handle.

## 2. Fetch after the first render

At introductory depth, keep the fetch near the component so you can see the moving parts. FS04.3 extracts it; extracting it now would hide the mechanics you are here to learn.

```tsx
useEffect(() => {
  const controller = new AbortController();

  async function load() {
    try {
      const response = await fetch('http://127.0.0.1:8034/api/issues', {
        signal: controller.signal,
      });

      // fetch resolved, so a server answered. Whether it answered *usefully*
      // is a separate question, and only this line asks it.
      if (!response.ok) {
        throw new Error(`Server returned ${response.status}`);
      }

      const data: unknown = await response.json();
      if (!Array.isArray(data)) {
        throw new Error('Expected an issue array');
      }

      setState({ kind: 'ready', issues: data as Issue[] });
    } catch (error) {
      // An abort is not a failure the user needs to see: we caused it.
      if (controller.signal.aborted) return;
      setState({
        kind: 'failed',
        message: error instanceof Error ? error.message : 'Request failed',
      });
    }
  }

  void load();
  return () => controller.abort();
}, []);
```

Three details in that block repay attention.

`void load()` says deliberately that nothing awaits this promise. The effect callback itself must return either nothing or a cleanup function, so it cannot be `async` — an `async` function returns a Promise, and React would treat that Promise as your cleanup function. Writing `void` instead of just calling `load()` also keeps the `no-floating-promises` lint rule satisfied without a disable comment.

`const data: unknown` is the runtime boundary from FS02.5. `response.json()` is typed as `Promise<any>` in the standard library, and `any` will silently infect everything it touches. Annotating `unknown` forces you to prove something before using it.

The `as Issue[]` is a temporary inspection shortcut for this first pass, and it is the weakest line in the lesson. `Array.isArray` establishes that the response is an array; it establishes nothing about the members. A server that returns `[1, 2, 3]` satisfies this check and then produces `undefined` in your JSX. FS04.3 removes the assertion and validates properly. Leave a comment on it so you remember it is a debt, not a decision:

```tsx
// Debt: proves array-ness, not member shape. FS04.3 replaces this with a parser.
setState({ kind: 'ready', issues: data as Issue[] });
```

## 3. The dependency array describes inputs, not timing

The empty array means *this exact effect reads no changing values from the component scope*. It does not mean "run once" as a general rule, and reading it that way is how effects go stale.

```tsx
// Reads nothing from scope: [] is accurate.
useEffect(() => { void load(); }, []);

// Reads projectId: [] is now a lie. After projectId changes, the effect
// keeps showing the first project's issues, and nothing looks broken.
useEffect(() => { void load(projectId); }, []);

// Correct: the effect re-synchronises when its input changes.
useEffect(() => { void load(projectId); }, [projectId]);
```

The trap is a value created during render. Objects and functions get a fresh identity on every render, so a dependency array containing one is never equal to the previous one, and the effect runs after every render — including the renders it causes itself:

```tsx
// Loops. `filters` is a new object every render, so the effect always re-runs.
const filters = { status: 'todo' };
useEffect(() => { void load(filters); }, [filters]);

// Fixed by depending on the primitive that actually changes.
useEffect(() => { void load({ status }); }, [status]);
```

If the dependency linter objects, it is nearly always right and you are nearly always about to ship a stale closure. A stale closure is worth naming precisely, because it produces no error and no warning: the effect captured the variables that existed on the render it ran on, and it keeps using those values forever. The screen looks fine. The data is from a state the user left five minutes ago. Describe the values the effect reads instead of silencing the rule.

There is a related question worth asking before you write any effect at all: does this need to be an effect? Deriving `visibleIssues` from `issues` and `filter` is a calculation you can do during render, not a synchronisation with an outside system. Storing it in state and updating it from an effect gives you two sources of truth and a render where they disagree. Effects are for the network, timers, subscriptions and browser APIs — for things React does not control. Filtering an array you already have is not one of them.

## 4. Cleanup is about ownership

The abort controller answers prediction 4. A component can unmount while a request is pending, and an older request can finish *after* a newer one:

```text
t0  filter=todo   request A starts ──────────────────────────┐
t1  filter=done   request B starts ─────────┐                │
t2                request B resolves ───────┘  screen: done  │
t3                request A resolves ────────────────────────┘  screen: todo  ← wrong
```

Nothing errored. The user clicked "done", saw the right list, and then watched it silently revert. Aborting on cleanup says: the screen that started this request no longer exists, so its answer is not wanted.

```tsx
return () => controller.abort();
```

That single line is why the `catch` block checks `controller.signal.aborted` before setting failure state. An abort rejects the fetch promise with an `AbortError`, and without the guard, every filter change would flash "Could not load issues: The operation was aborted."

React's development mode deliberately mounts, cleans up, and mounts effects again, to surface effects that cannot safely be restarted. Two requests in the Network panel during development is the tool working. Do not "fix" it by removing `<StrictMode>` — make the effect abortable and safe to repeat, and production inherits the same correct ownership story.

## 5. The whole component, assembled

Reading the pieces separately hides how small the result is. Here it is in one place, which
is also the version to compare against when yours misbehaves:

```tsx
import { useEffect, useState } from 'react';
import type { Issue } from './issue';
import type { LoadState } from './load-state';
import { IssueList } from './IssueList';

export function IssueBoard() {
  const [state, setState] = useState<LoadState>({ kind: 'loading' });

  useEffect(() => {
    const controller = new AbortController();

    async function load() {
      try {
        const response = await fetch('http://127.0.0.1:8034/api/issues', {
          signal: controller.signal,
        });
        if (!response.ok) throw new Error(`Server returned ${response.status}`);

        const data: unknown = await response.json();
        if (!Array.isArray(data)) throw new Error('Expected an issue array');

        setState({ kind: 'ready', issues: data as Issue[] });
      } catch (error) {
        if (controller.signal.aborted) return;
        setState({
          kind: 'failed',
          message: error instanceof Error ? error.message : 'Request failed',
        });
      }
    }

    void load();
    return () => controller.abort();
  }, []);

  if (state.kind === 'loading') return <p role="status">Loading issues…</p>;

  if (state.kind === 'failed') {
    return <p role="alert" className="text-red-700">Could not load issues: {state.message}</p>;
  }

  return state.issues.length === 0
    ? <p>No issues yet. Create the first one.</p>
    : <IssueList issues={state.issues} />;
}
```

Forty lines, and every one of them is doing something you can name. That is worth noticing
before Part 04's next lesson adds mutations: the reason the file stays this size is that the
component holds exactly one piece of state and asks exactly one question of the network.

Notice too what is absent. There is no `isLoading`, no `error` string alongside a `data`
array, no `useRef` guarding against double execution, no `if (!data) return null` scattered
through the JSX. Those appear when the state model is a bag of independent variables instead
of a union, and each one is a small patch over the same missing decision.

## 6. Make each failure happen on purpose

You have not modelled four states until you have watched all four on screen. Each of these takes under a minute:

```sh
# 1. Success. Read the status line and the body, not just the rendered list.
curl -i http://127.0.0.1:8034/api/issues

# 2. HTTP failure: a real reply the UI must not treat as data.
curl -i http://127.0.0.1:8034/api/missing        # 404 + JSON error body

# 3. Empty, honestly. Delete every seeded issue, then reload the page.
curl -X DELETE http://127.0.0.1:8034/api/issues/ISS-41
curl -X DELETE http://127.0.0.1:8034/api/issues/ISS-42
curl -X DELETE http://127.0.0.1:8034/api/issues/ISS-43

# 4. Network failure: stop the fixture (Ctrl-C in its terminal) and reload.
```

Case 2 is the one to sit with. Point your component at `/api/missing`, and confirm the screen says the server returned 404 rather than showing an empty list. If it shows an empty list, your `response.ok` check is missing or unreachable — and that same missing check is what turns a production outage into a page that calmly reports there is no work to do.

Restart the fixture afterwards to restore the seed.

One more case deserves attention because it is invisible on a fast local machine: a slow
response. Everything you just built works instantly against a fixture on loopback, which
means the loading state you carefully designed appears for about four milliseconds and you
never really see it. Throttle the connection in DevTools — Chrome and Firefox both offer a
"Slow 3G" preset in the Network panel — and reload.

Now the loading state is on screen long enough to judge. Does it reserve the space the list
will occupy, or does the page jump when data arrives? Does a spinner appear for a request
that finishes in 80ms, producing a flash that reads as a glitch? These are real questions
with real answers, and none of them are visible at localhost speed. Throttling is the
cheapest way to see the interface your users will actually get.

## Try it

With DevTools Network open, reload the page. Find `GET /api/issues`; read its status, its response headers, and its JSON body. Then work through the four cases above, and after each one write down in one sentence what the user sees. If two cases produce the same sentence, your state model has collapsed two facts into one and you should go back to §1.

## Common mistakes

- Starting `fetch` in the component body. Every state update causes another render and another request.
- Treating `[]` as loading. It makes a slow service look like it has no work.
- Calling `response.json()` without checking status. An error document becomes an accidental success path.
- Making the effect callback `async`. React receives a Promise where it expected a cleanup function.
- Setting failure state on an `AbortError`. Every navigation flashes an error the user did not cause.
- Putting an object or function created during render in the dependency array. Its identity changes each render and loops the effect.
- Silencing the dependency linter instead of describing the values the effect reads.

## When this goes wrong

If the browser reports a CORS error, read the failing request's `Origin` request header and the response's `Access-Control-Allow-Origin`. The fixture reflects any `http://localhost:<port>` or `http://127.0.0.1:<port>` origin, so both the Part 03 lab port (5174) and Vite's default (5173) work — but `localhost` and `127.0.0.1` are *different origins* to the browser, so mixing them in one session will fail even though both are allowed.

If the request is absent from Network entirely, the effect did not run or the component did not mount. If it is present but the page stays on "Loading issues…", log the status and the parsed body before touching UI code — the bug is in the transport layer, and changing JSX will not find it. Locate the layer first.

## Exercise

**Goal:** Replace your local initial issue array with the fixture's `GET /api/issues` result.

**Starting state:** B03 renders typed local issues instantly.

**Requirements:** Render a loading status, a real empty state, a network failure state, and an HTTP failure state. Check `response.ok`; hold the parsed body as `unknown` until you have checked something about it; abort the request during cleanup and do not report the abort as a failure. Keep filtering and selection as local UI state after data is ready.

**Verification:** Run all four cases from §5 and confirm each produces a visibly different screen. Restore the route and the fixture, and make the list render again.

**Mode: manual browser evidence and TypeScript compiler output.** Nothing marks this exercise automatically; keep the Network screenshots or notes that prove each state.

**Hints:** Start with `LoadState`, not with JSX branches. Add the happy path first, then stop the server before writing the catch UI. If TypeScript objects to the response, keep it as `unknown` until you can state the check you performed.

## In the project

B04's first stage replaces the local array with this GET request. The fixture stays deliberately disposable: it gives the React application a remote truth without pre-empting Part 05's actual work — DALT routes, validation, persistence, and PostgreSQL. Do not add database code, authentication, or a data-fetching library here. The point of Part 04 is that you can see every moving part; a library that hides them is worth adopting only after you can describe what it hides.

## Closed-book checkpoint

1. Why is an empty list not a loading state?
2. Which failures reject `fetch`, and which require `response.ok`?
3. What does the dependency array describe?
4. What incorrect result can a late request produce?
5. Why is response JSON `unknown` at the boundary?
6. Why must the effect callback not be `async`?

## Resources

### Read

- [MDN: Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API)
- [React: `useEffect`](https://react.dev/reference/react/useEffect)
- [MDN: AbortController](https://developer.mozilla.org/en-US/docs/Web/API/AbortController)

### Go deeper

- [React: synchronizing with effects](https://react.dev/learn/synchronizing-with-effects)
- [React: you might not need an effect](https://react.dev/learn/you-might-not-need-an-effect)
- [MDN: HTTP response status codes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status)

## You are done when

- [ ] A reload shows loading before a successful list, not an invented empty state.
- [ ] I inspected the fixture's actual 200 JSON response in Network.
- [ ] A stopped server produces a visible network-failure state.
- [ ] A 404 route produces a visible HTTP-failure state, distinct from the empty state.
- [ ] An empty-but-successful response produces an honest empty state.
- [ ] The effect aborts its request during cleanup, and an abort does not render an error.
- [ ] I can explain every dependency in the effect.
- [ ] `npm run typecheck` and `npm run test` still pass in my project.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_02.md`
- Official sources: React `useEffect` and synchronizing-with-effects documentation; MDN Fetch API, AbortController, and HTTP status references
- Versions: React 19.2.3; TypeScript 5.9.3; PHP 8.4 fixture server
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/course/fullstack/react-server-fixture/fixture-api.php`; Part 03 lab source and Vite configuration
- Curriculum authority: `CURRICULUM.md` §14 FS04.1
- Laravel bridge: optional only — DALT request handling is introduced in Part 05, after the browser/API seam is understood
