> **What exists when you finish:** an issue tracker with deliberate homes for URL, local, shared-client, derived, and server state; query-backed reads and mutation-backed writes synchronize with the DALT API.

## What you are building

Refactor the navigable, tested B07 issue tracker into an application whose state boundaries can be
explained. Add TanStack Query at the React root, replace manual remote-data effects where a query
client improves synchronization, and place real issue writes behind mutations. Preserve B07 routes,
the API boundary, and B06 behavior tests. This is not a feature-building milestone and it is not a
reason to introduce a giant store. It is a chance to make the application honest about where a
value comes from, who can change it, and when a browser copy must ask the server again.

Create a small state inventory in the learner project. It may be an architecture note or a comment,
but it must classify actual application values and state why each has that owner. A concise starting
shape is:

```text
URL state: /issues/:id and ?status=open
local component state: unfinished comment text and open dialog
derived state: open issue count calculated from a returned list
shared client state: one proven cross-layout UI interaction
server state: issues, comments, membership, and current-user response
```

Keep learner application source outside `.dalt`, `framework/`, `config/`, and `public/`. Deleting
`.dalt` must still leave a working framework and learner application.

## Why this milestone exists

Manual fetching in Part 04 was intentional. It made loading, error, stale data, cancellation, and
the difference between a response and React state tangible. By B07, copying that lifecycle across
routes, issue details, lists, and writes becomes a source of bugs. One screen may refresh while a
second screen presents an old issue. A local array may be patched after a mutation while a filtered
list stays wrong. A request failure may be rendered as “no issues.” A query client gives remote
snapshots one cache, a named address, and a policy for synchronizing again.

The server remains authoritative. A cached issue is a browser snapshot, not a database row. A
successful PATCH can normalize data, reject a transition, or enforce membership that a client could
not know. Query caching never replaces session, CSRF, ownership, or authorization checks. A mutation
describes one attempt to change remote state; invalidation returns affected displays to server truth.
Optimism is a limited UX choice that requires rollback, not proof that a click committed work.

The milestone also protects against “global store everything.” A copied filter belongs in the URL.
An unfinished form belongs near its form. A value calculable from an issue list is derived, not a
second field to synchronize. Context, reducers, and Zustand are client coordination tools with
different costs; they do not improve API authority or replace a query cache.

## Before you start

Complete FS08.1–FS08.3 and begin from a passing B07 project. Record the output before moving code.

```sh
php artisan test
npm run typecheck
npm run lint
npm run test
npm run build
```

Install TanStack Query in the learner root at the version this course pins, and commit the
lockfile change in the same commit:

```sh
npm install @tanstack/react-query@5.101.4
```

Zustand is not required. If you reach Stage 5 and can state why local state, props, Context
and a reducer are each awkward for one specific shared client-only interaction, install
`zustand@5.0.15`. A project with no production Zustand store is complete, and is the
expected outcome.

## Stage 1 — Inventory the state

Walk an issue list, detail page, create/edit form, comment form, authenticated shell, and route
filter. Classify each important value. Write whether refresh should restore it, whether a person
should copy it, who is authoritative, and which components coordinate it. Identify at least one
derived value you can calculate instead of storing. Keep shareable filters in search parameters.

**Working looks like:** another developer can see why issue data is not in a global store, why a
draft is not in a URL, and why a filter is durable browser state.

**Check it yourself:** reload a detail route, copy a filtered URL into another tab, and close an
unfinished form. Confirm the first two restore their intentional state and the last does not become
durable private data.

## Stage 2 — Query remote reads

Create one QueryClient outside render and provide it at the application root. Move transport to the
existing API module. Give every query key every input that changes its response: project id and
status for a list, issue id for a detail, issue id for comments. Query functions return data or
reject; they do not call setters, navigate, or turn failures into empty arrays.

Refactor at least one list and one detail or comments screen. Render pending, failure, successful
empty, populated success, and background refresh explicitly. A cache snapshot may remain visible
while synchronization happens, but the interface must make a meaningful refresh visible.

**Working looks like:** returning to a route can reuse a known snapshot; changing `?status` changes
the cache address; a stopped API creates a retryable failure rather than an empty list.

**Check it yourself:** observe Network requests while switching open and closed filters. Stop or
reject the API, read the visible error, restore it, retry, and refresh the route.

## Stage 3 — Put writes behind mutations

Choose existing API actions: create, edit, change status, or comment. Each API function returns the
confirmed record or rejects. Invoke a mutation from an event handler, not an Effect. Make pending
work visible and prevent accidental duplicate commands. Keep a failed comment draft; do not clear a
person's writing before the server accepts it.

On success, invalidate every query family made stale. A status change can affect issue detail and
open and closed project lists. A comment can affect comments and an issue count. Prefer targeted
invalidation over fragile manual patches until you can enumerate every display affected by a write.
Retain backend behavior tests: frontend code cannot prove a crafted request is denied.

**Working looks like:** a write has named pending and error states, then relevant lists and details
become current. A rejected write does not leave a fake successful result displayed.

**Check it yourself:** view one issue in detail and filtered lists. Change its status, then inspect
each representation. Force a failed write and verify its alert differs from empty, 401, and 403 UI.

## Stage 4 — Earn optimism or reject it

Choose at most one reversible, frequent interaction, normally issue status. First complete the
confirmed mutation path. If optimism earns its complexity, cancel an in-flight query, snapshot each
affected cache, patch it, restore the snapshot on error, and invalidate on settlement. Explain what
another tab or person can do while the request travels. Creation, long comments, destructive work,
and uncertain server-generated values are strong reasons to remain confirmed.

**Working looks like:** a safe optimistic value appears immediately; a forced failure restores the
previous display and announces the failure; settlement reconciles with server truth.

**Check it yourself:** make that request fail deliberately, watch rollback, restore the API, and
repeat successfully. If you reject optimism, demonstrate the honest pending state and record why.

## Stage 5 — Bound shared client state

Choose a client-only interaction only if it has real cross-component pressure, such as a command
palette or layout preference. Start with the closest owner and lifting. Use focused Context for a
subtree, and a reducer when named transitions clarify related state. Use Zustand only after the
stated gate; keep its store to client state, named actions, and narrow selectors. Never put issue
arrays, comments, current-user API responses, route parameters, or permissions into it.

**Working looks like:** a shared interaction works without a giant bucket, and its lifetime and
owner appear in the inventory. No external store is a valid completed decision.

**Check it yourself:** refresh after using the interaction and compare the result with the inventory.
Inspect provider/store data to confirm it contains neither query data nor URL state.

## Stage 6 — Preserve evidence

Update frontend tests at accessible boundaries. A test can prove loading text, retryable failure,
disabled pending control, visible mutation error, or result after refetch. Do not assert cache
internals. B06 backend tests must still make direct requests and prove CSRF and authorization.

**Working looks like:** one frontend test fails if lifecycle feedback disappears, and the backend
suite still proves the server boundary independently.

**Check it yourself:** remove one tested visible state, run its focused test and read the failure,
restore it, then run the complete project checks.

## Decisions you have to make

- Which manual effects are true server-state synchronization work?
- Which response-changing inputs belong in each query key?
- Which query representations can each mutation make stale?
- Which one interaction earns optimism, or why is confirmation better?
- Which values are derived rather than stored?
- Is any shared client-only state genuinely awkward with simpler tools?
- Which facts remain URL state, and which remain server-authorized?

## Acceptance criteria

Nothing here is checked automatically. Read each item against software you actually built and ran.

- [ ] A state inventory classifies major URL, local, derived, shared-client, and server values.
- [ ] One root QueryClient and input-complete query keys back at least one list and detail/comments route.
- [ ] Pending, error, empty, success, and refresh states are visibly distinct.
- [ ] Query functions use the API boundary and never hide failures as empty data.
- [ ] One real write uses a mutation with visible pending and error behavior.
- [ ] Successful writes invalidate or reconcile all affected query representations.
- [ ] One optimistic update has rollback and settlement, or a recorded decision rejects it.
- [ ] URL and query data have not been duplicated in Context or an external store.
- [ ] Shared-client state is bounded to a genuine client-only concern.
- [ ] Any Zustand use has actions, selectors, and a written gate decision; no use is valid.
- [ ] Frontend tests prove visible behavior and B06 API tests still prove server enforcement.
- [ ] `php artisan test`, `npm run typecheck`, `npm run lint`, `npm run test`, and `npm run build` pass.
- [ ] Deleting `.dalt` would leave the framework and learner application working.

## Prove it to yourself

Narrate one status change from click to final render: event, mutation function, server session and
authorization, database result, invalidated key, refetch, and accessible UI. Narrate its failure
path too. Then name one value in each state category and why it does not belong in the others.

## What this unlocks

Part 09 can extract repeated query and client behavior into focused hooks and feature boundaries
without hiding ownership. The application has one coherent remote-state model instead of a growing
collection of request effects.
