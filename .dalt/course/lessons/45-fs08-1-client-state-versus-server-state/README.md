# FS08.1 — Client state versus server state

Lesson ID: FS08.1
Title: Client state versus server state
Part: 08 — Server and application state
Order: 1
Status: Published
Estimated effort: 100–130 minutes
Difficulty: Integration
Prerequisites: FS07.3 — Test frontend behavior
Project milestone: B08 — Intentional state architecture
Primary source dossier: FSO_PART_06.md
Last reviewed: 2026-08-15

## Why this matters

Manual `useEffect` fetching was the right low-level experience in Part 04. It exposed loading,
failure, cancellation, stale displays, and the fact that an HTTP response is not automatically
part of React state. It becomes costly when several routes need the same issue, when navigation
returns to a screen, or when a successful write must make several displays truthful again. Copying
an effect into every component creates several accidental caches with no shared policy.

An issue list, issue detail, comments, workspace membership, and current-user response are remote
facts. The browser can retain a recent answer, but it does not own the answer. A dialog being open,
a draft title, and a selected display density are client concerns. A status filter already encoded
in the address is URL state. Classification comes before a library: it tells you who may change a
value and what must happen when it is stale.

## Before you start

Complete B07 and retain its routes, API client boundary, and backend authorization tests. The
following install is a project dependency, so install the version this course pins and commit
the lockfile. Do not put React tooling in `.dalt`.

```sh
npm install @tanstack/react-query@5.101.4
npm run typecheck
npm run lint
```

The exact version matters for the same reason it did in FS07.1: a bare install takes
whatever is newest today, so your project and this lesson stop describing the same
software. Commit `package-lock.json` in the same commit — the lockfile is what makes the
install reproducible for anyone who clones your work, including you on another machine.

Going deeper in DALT Core — optional:

- None. This is frontend state architecture; DALT Core is optional and never a gate.

## By the end

- classify URL, local client, derived, and server state by authority;
- configure one QueryClient for the React application;
- write stable query keys and query functions at the API boundary;
- render loading, error, empty, and success intentionally;
- explain cache freshness without treating cached data as authorization.

## Predict before reading

1. If an issue title changes in another tab, which copy is authoritative?
2. Should an open compose dialog survive a full browser refresh?
3. What must change when `/issues/12` becomes `/issues/13`?
4. A refetch fails while the previous list is still on screen. What should the person see?

## Mental model

```text
URL          → browser-owned, shareable location
local state  → one component's temporary interaction
derived      → computed from other state, not stored twice
server state → remote snapshot, cached and synchronized by a query client
```

TanStack Query is not a database and does not replace your DALT API. It stores a client-side
snapshot under a key, decides when that snapshot is stale, and gives React a subscription to the
request lifecycle. The query function still calls the API module from FS04/FS07; that module still
maps JSON and HTTP failures into useful application errors. The DALT server still authenticates and
authorizes every request.

## Why a library, and why only now

This course withheld TanStack Query for four parts on purpose. Adopting a cache before you
have felt the problem it solves produces a developer who can configure it and cannot debug
it — and the configuration is the easy half.

You already solved every one of these by hand in Part 04. Name what each cost you:

```text
loading / error / empty as distinct states   you wrote a discriminated union per screen
a request cancelled on unmount               you tracked a `live` flag or an AbortController
the same issue fetched by two screens        you fetched it twice and hoped they agreed
returning to a screen                        you refetched from scratch and showed a spinner
a write that invalidates a list              you patched local arrays and drifted
```

The first two you can keep doing forever; they are a dozen lines and they are honest. The
last three are where hand-rolling stops scaling, because they are not about one component.
They are about **two components disagreeing about one server fact**, and no amount of care
inside a single `useEffect` can coordinate that. A cache is the shared place where the
answer lives so that the disagreement cannot happen.

That is the actual claim for adopting the library. Not "less boilerplate" — the boilerplate
is a symptom. The claim is: a request cache gives several screens one address for one
remote fact, plus a policy for when to recheck it.

Which tells you what **not** to move. A library that manages remote facts has nothing to
offer a value that is not a remote fact:

```text
issue list, issue detail, comments, /api/me   → query   (remote, shared, server-owned)
compose dialog open, draft title, active tab  → useState (local, private, yours)
?status=open                                  → the URL  (shareable, browser-owned)
count of open issues                          → derived  (compute, never store)
```

The mistake this lesson most wants to prevent is the enthusiastic one: putting a form draft
in a query cache because queries are the new tool. A draft has no server address, no
freshness question and no second reader. Keyed into a cache it gains three concepts and
loses none.

A last thing that does not change. TanStack Query sits *above* your API module from FS04.3;
it does not replace it. The query function still calls `listIssues`, that function still
owns URLs, headers, status codes and parsing, and DALT still authorizes every request. If
you find yourself writing `fetch` inside a `queryFn`, the boundary you built in Part 04 has
quietly dissolved, and Part 05's lesson — that one file should decide where the application
talks to — has been undone.

## Identify the source of truth

Start with a small table before moving code. It catches the tempting but incorrect move of putting
everything behind a global hook.

```text
issue detail              server state       API is authoritative
?status=open              URL state          copied and refreshed
new-comment text          local state        temporary and private
visible issue count       derived state      calculate from the list
sidebar compact setting   shared client      only if several layouts need it
```

Derived state is frequently mistaken for state. Do not keep `openCount` and `issues` separately
then attempt to synchronize both after a mutation. Compute it during render instead.

```tsx
const openCount = issues.filter((issue) => issue.status === 'open').length;
return <p>{openCount} open issues</p>;
```

The right home can change with the product. A filter that must survive refresh belongs in the URL;
the same filter inside an unsaved modal may be local. State is not classified by its TypeScript type
or whether many components happen to read it. It is classified by its lifetime, sharing needs, and
authority.

## Create one query client

Create a QueryClient once at the application root. Recreating it during render loses its cache and
turns every render into a fresh client. Provider placement makes the client available to route
screens without passing transport state through every component.

```tsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const queryClient = new QueryClient();

export function App() {
  return <QueryClientProvider client={queryClient}><AppRouter /></QueryClientProvider>;
}
```

A query key is an address for cached remote data. Put every input that changes the response in the
key. Arrays make the hierarchy visible: the issue collection is distinct from one issue, and a
workspace parameter prevents one workspace's list standing in for another's.

```ts
export const queryKeys = {
  issues: (projectId: number, status: string) => ['projects', projectId, 'issues', { status }] as const,
  issue: (issueId: number) => ['issues', issueId] as const,
  comments: (issueId: number) => ['issues', issueId, 'comments'] as const,
};
```

Do not use a key such as `['issues']` for every route and hope the component parameter explains the
difference. The cache does not know what a closure means. Conversely, do not put a new object with
unrelated display preferences in a key just because it is nearby; that creates needless cache
entries.

## Keep the query function boring

Query functions fetch and return data or reject. They do not call `setState`, navigate, or silently
convert an API failure into an empty array. Those actions hide the distinction between no issues
and a broken request.

```ts
export async function getIssue(issueId: number): Promise<Issue> {
  const response = await fetch(`/api/issues/${issueId}`, { credentials: 'include' });
  if (response.status === 404) throw new Error('Issue not found');
  if (!response.ok) throw new Error('Could not load issue');
  return response.json() as Promise<Issue>;
}
```

The route has already validated the URL parameter in FS07.1. Give the validated number to the
query, and disable a query whose input is not ready. Never turn `undefined` into an API address.

```tsx
const issueId = parsedIssueId(params.issueId);
const issueQuery = useQuery({
  queryKey: queryKeys.issue(issueId),
  queryFn: () => getIssue(issueId),
  enabled: issueId !== null,
});
```

In real code, branch before invoking a hook only by choosing a screen component: `IssueRoute`
validates, then renders `IssueScreen` with a number. That keeps the hook call unconditional inside
the screen and preserves the Rules of Hooks.

## Render the lifecycle honestly

A query exposes a pending state, an error, and data. Empty is not an error; it is a successful
response whose collection contains no rows. Preserve a visible retry path for a transient failure.

```tsx
if (issuesQuery.isPending) return <p>Loading issues…</p>;
if (issuesQuery.isError) return <button onClick={() => issuesQuery.refetch()}>Try loading again</button>;
if (issuesQuery.data.length === 0) return <p>No open issues yet.</p>;
return <IssueList issues={issuesQuery.data} />;
```

Cached data can remain on screen while a refetch is happening. That is often useful: navigation
does not flash blank content merely because a known snapshot is being checked. Show a subtle
refreshing indication if it changes the decision a person can make.

```tsx
{issuesQuery.isFetching && !issuesQuery.isPending ? <p aria-live="polite">Refreshing…</p> : null}
```

The branch order above hides one real case. A background refetch can *fail* while a
perfectly good snapshot is still on screen — you are offline, and the list you already have
is the list from ninety seconds ago. `isError` is true and `data` is defined at the same
time. Falling into the error branch throws away a useful screen to display a message;
ignoring it lies about what the person is looking at:

```tsx
if (issuesQuery.isError && issuesQuery.data === undefined) return <LoadFailed retry={issuesQuery.refetch} />;
return (
  <>
    {issuesQuery.isError && <p role="alert">Showing the last known list — could not refresh.</p>}
    <IssueList issues={issuesQuery.data} />
  </>
);
```

Five states, then, not four: first load, success, empty, hard failure with nothing to show,
and soft failure with something stale to show. The fifth is the one that separates an
application people trust on a train from one that blanks out at the first dropped packet.

One naming trap while you are here. In this version, `isPending` means *there is no data
yet*, and `isLoading` is the narrower `isPending && isFetching` — a first load actually in
flight. A disabled query is pending but not loading. Reading old tutorials where `isLoading`
meant the former is a reliable way to render a spinner that never disappears.

Stale is a policy signal, not proof that data is false. Query defaults favor refetching at useful
times such as remount or focus. Configure `staleTime` only for a reason you can state: a stable
reference list may tolerate a minute; permission-sensitive responses still require server
enforcement when used. Do not choose a huge time merely to make the spinner disappear.

```ts
useQuery({
  queryKey: queryKeys.issues(projectId, status),
  queryFn: () => getIssues(projectId, status),
  staleTime: 30_000,
});
```

## Freshness is a policy you choose

The default that surprises everyone: a query is stale the instant it resolves. TanStack
Query still shows the cached value immediately, then refetches in the background when the
component remounts, the window regains focus, or the network reconnects. That is why a
screen you return to renders instantly and then quietly corrects itself.

Two durations control this, and they answer different questions:

```ts
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,   // how long a snapshot is trusted without rechecking
      gcTime: 5 * 60_000,  // how long an unused snapshot is kept before being discarded
    },
  },
});
```

`staleTime` is *"how wrong am I willing to be?"*. `gcTime` is *"how long do I keep this
around for a fast return visit?"*. Confusing them produces the two classic complaints: a
list that refetches on every navigation because `staleTime` is zero, and a list that shows
yesterday's data because someone set `staleTime: Infinity` to stop the refetching.

Choose per query, because the right answer is a product question:

```ts
useQuery({ queryKey: queryKeys.issue(issueId), queryFn: ..., staleTime: 10_000 });
useQuery({ queryKey: ['workspaces'], queryFn: ...,          staleTime: 60 * 60_000 });
```

An issue changes while people work; a workspace list does not change all afternoon. There
is no globally correct number, and picking one deliberately is most of what "configuring a
cache" means.

One boundary is not negotiable. A cached response is a **snapshot of what the server was
willing to tell this session**, not a permission. When a user signs out, clear the cache —
otherwise the next person at that browser sees the previous user's issue titles rendered
from memory before any request is made:

```tsx
async function logout() {
  await api.logout();
  queryClient.clear();     // discard every cached answer, not just the current screen
  navigate('/login', { replace: true });
}
```

`queryClient.clear()` on logout is a one-line habit that prevents a genuinely serious leak.
It does not replace server authorization; it prevents your own cache from displaying data
the current visitor was never authorized to see.

## Testing a screen that queries

Adding a query client will break the tests you wrote in FS07.3, and this is the second time
this course has done that to you deliberately. In B04 the arrival of `fetch` broke B03's
tests; here the arrival of `useQuery` breaks B07's. The cause is the same both times: a
component gained a dependency the test does not provide.

The fix is the same shape as the seam. A query needs a client in context, so give the test
one — a fresh client per test, with retries off, because a test should not spend three
seconds retrying a failure it asked for:

```tsx
function renderWithQuery(ui: React.ReactNode, api: IssueApi) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      <ApiProvider api={api}>{ui}</ApiProvider>
    </QueryClientProvider>,
  );
}
```

Build the client **inside** the helper, never once at module scope. A shared client carries
cached answers from one test into the next, which produces the worst kind of failure: a
suite that passes in isolation and fails in order, or vice versa.

Your fake API needs no changes at all. That is the payoff for having put the seam at the
API boundary rather than at `fetch` — the query client sits above your client, so swapping
the client still works.

```tsx
it('renders the issues the API returned', async () => {
  renderWithQuery(<ProjectPage projectId="PRJ-1" />, fakeApi());

  expect(await screen.findByRole('listitem')).toHaveTextContent('Search is slow');
});
```

Note what did not change: the assertion. It still describes what a person sees. The
plumbing moved; the claim did not. That is the sign the test was written at the right
level.

## Try it

Replace one manual issue-list effect with a query. Navigate between `?status=open` and
`?status=closed`, then return to the first route and observe a cached rendering followed by a
freshness check. Stop the API temporarily and verify that a failure is an alert or retryable state,
not an empty list. In a second tab, change an issue through the existing application and reload the
first tab: the server answer wins.

```text
same key + fresh snapshot    → reuse cache
same key + stale snapshot    → render snapshot, then synchronize
different status parameter   → different query key and request
failed request               → error state, not “no issues”
```

## Common mistakes

- Putting everything behind a query because it worked for the issue list. A form draft in a
  cache keyed by nothing is harder to reason about than `useState`, not easier.
- Leaving a request input out of the key, so two filters share one cache entry and the
  screen shows the previous filter's results.
- Building the key inline in each component, so `['issues', id]` and `['issue', id]` drift
  apart and silently address different entries. Use a key factory.
- Storing query data into `useState` in an effect, which recreates by hand every problem
  the library was adopted to solve, and adds a render.
- Catching an API error inside the query function and returning `[]`, which converts a
  failure into a successful empty result and makes the error state unreachable.
- Treating `isPending` as "no data" — on a background refetch there is data *and* a request
  in flight, and blanking the screen for it is a visible regression from Part 04.
- Setting `staleTime: Infinity` to stop unwanted refetching, which fixes the noise by
  guaranteeing the data is wrong.
- Creating the `QueryClient` inside a component, so every render discards the whole cache.
- Forgetting `queryClient.clear()` on logout and leaving one user's data on screen for the
  next one.
- Believing a cached answer means the user is still allowed to see it. Only the server
  knows that, and only at the moment it is asked.

## When this goes wrong

If changing the filter still displays the old list, inspect the key before changing the API: the
filter is probably missing from it. If a screen refetches indefinitely, make sure the query
function does not set state that changes a key input. If two screens show different answers after a
write, do not manually patch both yet; the next lesson gives mutations a shared synchronization
path.

```tsx
// Wrong: status changes the response but not the cache address.
useQuery({ queryKey: ['issues', projectId], queryFn: () => getIssues(projectId, status) });
```

## Exercise

**Goal:** Replace one manual server-data effect with a deliberately keyed query.

**Starting state:** B07 has a routed issue or project screen that fetches from the protected API.

**Requirements:** Write a key factory, move the request into the API client, render pending,
failure, empty, and success states, and keep the selected status in the URL rather than a new
global store. Identify one derived value you will calculate rather than store.

**Verification:** Change the URL filter, observe distinct requests, simulate a failed request,
and refresh a route. Run the existing frontend tests plus the project checks.

**Mode: tool-run — browser/network evidence plus `npm run typecheck`, `npm run lint`, and `npm run test`.** The platform does not inspect your cache; the visible lifecycle and tool output are the evidence.

**Hints:** Begin with one detail or list screen. Write the key before the query. If you cannot say
which request input belongs in the key, return to the API function signature.

## In the project

B08 replaces only the manual effects where a query client improves synchronization. Local form
drafts stay local, shareable filters stay in URLs, and protected writes remain server-authorized.
The next lesson gives each write an explicit mutation boundary and invalidates the remote facts it
can change.

## Closed-book checkpoint

1. Why is an issue list server state even after it is cached in the browser?
2. Which inputs must appear in a query key?
3. Why is an empty result different from a query error?
4. When should a value be derived rather than stored?
5. What security decision can TanStack Query never make for the server?
6. What is the difference between `staleTime` and `gcTime`, in one sentence each?
7. Why must the cache be cleared on logout even though the server still authorizes?

## Resources

### Read

- [TanStack Query: Queries](https://tanstack.com/query/latest/docs/framework/react/guides/queries)
- [TanStack Query: Query Keys](https://tanstack.com/query/latest/docs/framework/react/guides/query-keys)
- [TanStack Query: Important Defaults](https://tanstack.com/query/latest/docs/framework/react/guides/important-defaults)

### Go deeper

- [React: You Might Not Need an Effect](https://react.dev/learn/you-might-not-need-an-effect)

## You are done when

- [ ] One remote screen uses a stable, input-complete query key.
- [ ] Pending, error, empty, success, and refresh states are distinguishable.
- [ ] URL filters, local drafts, derived values, and remote data have separate homes.
- [ ] The API module remains the only transport boundary.
- [ ] `npm run typecheck`, `npm run lint`, and `npm run test` pass.

## Maintainer source record

Source dossier: `docs/dalt-fullstack/sources/FSO_PART_06.md`.

Official sources: TanStack Query query, query-key, and defaults guides; React effect guidance, linked above.

Versions: React 19.2.3; TypeScript 5.9.3; TanStack Query 5.101.4.

Consulted: 2026-08-15.

Curriculum authority: `CURRICULUM.md` §19, FS08.1; `PROJECT_BLUEPRINT.md` §§46–50.
