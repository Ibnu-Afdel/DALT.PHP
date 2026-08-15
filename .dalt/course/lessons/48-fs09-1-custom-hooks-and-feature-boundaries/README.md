# FS09.1 — Custom hooks and feature boundaries

Lesson ID: FS09.1
Title: Custom hooks and feature boundaries
Part: 09 — Advanced React and tooling
Order: 1
Status: Published
Estimated effort: 90–120 minutes
Difficulty: Advanced
Prerequisites: FS08.1, FS08.2, FS08.3
Project milestone: B09 — Maintainable frontend
Primary source dossier: FSO_PART_07.md
Last reviewed: 2026-08-15

## Why this matters

The issue tracker now has routes, authenticated requests, query-backed server state, URL filters,
and a few shared client interactions. A component that has to parse search parameters, name query
keys, call mutations, and lay out a panel can still work, but its purpose becomes difficult to see.
The answer is not to put every repeated line behind a `useSomething` wrapper. A poor hook merely
moves complexity somewhere harder to find. A useful hook expresses one domain concept while keeping
the source of truth visible: the URL remains URL state, TanStack Query remains the server cache, and
local interaction state remains near the interaction.

## Before you start

Required:
- FS08.1 — Client state versus server state
- FS08.2 — Mutations, invalidation and optimistic UI
- FS08.3 — Context, reducers and Zustand

Recommended first:
- Revisit FS07.1 search parameters and route parameters.

Going deeper in DALT Core — optional:
- None. This standalone Fullstack track already supplies the needed React and state foundations.

## By the end

- identify repeated *behavior*, rather than merely repeated syntax;
- write a custom hook whose name, inputs, and return value describe one domain concept;
- preserve URL, Query, and local-state ownership through an extraction;
- organize code around change patterns without treating a folder tree as doctrine;
- use the hooks linter as evidence and avoid memoization cargo cults.

## Predict before reading

An issue-list page and a board page both need `status`, `assignee`, and `q` from the URL. If both
copy the parsing code, what can drift? If a `useIssueFilters` hook uses `useSearchParams`, where is
the durable value actually stored? Finally, does calling a custom hook from two components share its
`useState` automatically? Write your answer before continuing.

## Mental model

```text
component = describes UI and composes capabilities
custom hook = named, reusable behavior used by a component
URL / Query / local state = owners that remain owners after extraction
feature boundary = code that tends to change for the same product reason
```

A custom hook is an ordinary TypeScript function whose name starts with `use` and which may call
React hooks. React does not create a hidden global singleton for it. Mentally paste its hook calls
into the calling component: each caller gets its own hook state in that component's hook order.

## 1. Extract a concept, not a convenience wrapper

Start with concrete pressure. Here, two screens need the same URL encoding and the same domain
operations. The repeated concept is “issue filters,” not “a call to `useState`.” The hook accepts no
secret global dependencies and returns intent-level operations.

```tsx
type IssueFilters = { status: 'open' | 'closed' | 'all'; assignee: string | null; query: string };

function readFilters(search: URLSearchParams): IssueFilters {
  const status = search.get('status');
  return { status: status === 'open' || status === 'closed' ? status : 'all',
    assignee: search.get('assignee'), query: search.get('q') ?? '' };
}
```

```tsx
export function useIssueFilters() {
  const [search, setSearch] = useSearchParams();
  const filters = readFilters(search);
  const setStatus = (status: IssueFilters['status']) => setSearch(current => {
    const next = new URLSearchParams(current); status === 'all' ? next.delete('status') : next.set('status', status); return next;
  });
  return { filters, setStatus, clearFilters: () => setSearch(new URLSearchParams()) };
}
```

The URL is still the source of truth. Refreshing or sharing the page keeps the filters because the
hook reads and writes the address; it does not copy them into Zustand or an effect-managed local
mirror. An object return works here because named domain operations are clearer than a six-position
tuple.

```tsx
function IssueListPage() {
  const { filters, setStatus, clearFilters } = useIssueFilters();
  const issues = useProjectIssues(filters);
  return <IssueFiltersBar filters={filters} onStatusChange={setStatus} onClear={clearFilters} />;
}
```

## 2. Wrap a correct owner; do not create another one

A query hook can add a domain name, an input-complete key, and a typed transport call. It must not
copy the result into `useState`; that would create a second remote snapshot with no invalidation
policy.

```ts
export const issueKeys = { all: ['issues'] as const,
  list: (workspaceId: number, filters: IssueFilters) => ['issues', workspaceId, filters] as const };
```

```tsx
export function useProjectIssues(workspaceId: number, filters: IssueFilters) {
  return useQuery({ queryKey: issueKeys.list(workspaceId, filters),
    queryFn: () => listIssues(workspaceId, filters) });
}
```

```tsx
// Wrong: this cache stops following invalidation and background refetches.
const query = useProjectIssues(workspaceId, filters);
const [issues, setIssues] = useState<Issue[]>([]);
useEffect(() => setIssues(query.data ?? []), [query.data]);
```

The same caution applies to effects. A hook is not a legal hiding place for code that should run in
an event handler or for derived data that can be calculated during render.

```tsx
const visibleIssues = issues.data?.filter(issue => issue.title.includes(filters.query)) ?? [];
// No effect and no second state field are needed for this derived value.
```

## 3. Keep the Rules of Hooks structural

Hooks must run at the top level of a React component or another custom hook, in the same order on
every render. The linter catches structural mistakes that can otherwise attach state to the wrong
call site. Do not silence `react-hooks/exhaustive-deps` to force a desired schedule; redesign the
effect or hook until its reactive inputs are honest.

```tsx
// Wrong: a render can skip the hook call.
if (enabled) { const filters = useIssueFilters(); }
```

```tsx
// Right: call unconditionally, then choose what to render.
const filters = useIssueFilters();
return enabled ? <IssueListPage /> : <EmptyState />;
```

```tsx
useEffect(() => {
  const listener = () => console.log('online');
  window.addEventListener('online', listener);
  return () => window.removeEventListener('online', listener);
}, []);
```

Cleanup is part of a hook’s contract when it owns a subscription, timer, observer, or connection.
The hook name is also a promise: `useCurrentWorkspace` is constrained by its domain; `useEverything`
is a warning that the architecture has been made invisible.

## 4. Group by change, then refactor in green steps

There is no universally correct tree. Start from code that changes together. An issue feature may
own its pages, presentational components, query hooks, transport types, and focused tests. Truly
shared infrastructure belongs elsewhere. Do not move every file at once, create a `common` dumping
ground, or import feature internals across the application without a reason.

```text
src/features/issues/
  api.ts             # typed transport boundary
  queries.ts          # keys and useProjectIssues
  useIssueFilters.ts  # URL behavior
  IssueListPage.tsx
  IssueListPage.test.tsx
src/shared/
  http.ts             # cross-feature transport primitive
  config.ts           # public build-time configuration
```

```sh
npm run typecheck
npm run lint
npm run test
```

Refactor one boundary, run the checks, and preserve route and accessible behavior. Feature
organization is a map for future change, not a reason to create arbitrary layers.

## 5. Make hook inputs and outputs an honest contract

The parameters of a custom hook are reactive inputs. If `workspaceId`, `issueId`, or a filter is
used by an Effect, query key, memoized calculation, or subscription, the hook must respond when the
caller changes it. A hook should not read an old input once and quietly continue to represent the
wrong resource. Type the boundary so a caller can see what the concept needs without opening its
implementation.

```ts
type UseIssueResult = {
  issue: Issue | undefined;
  isPending: boolean;
  isError: boolean;
  retry: () => void;
};

export function useIssue(issueId: number): UseIssueResult {
  const query = useQuery({ queryKey: issueKeys.detail(issueId), queryFn: () => getIssue(issueId) });
  return { issue: query.data, isPending: query.isPending, isError: query.isError, retry: query.refetch };
}
```

Here `issueId` is visible in both the hook signature and Query address, which is useful evidence:
changing routes changes the requested resource. Do not return a giant untyped object just because it
is convenient for the first caller. A focused object return exposes named capabilities. A tuple can
be right for a familiar pair such as `[value, setValue]`; six positional values force callers to
memorize an implementation order rather than a domain.

```tsx
const { issue, isPending, isError, retry } = useIssue(issueId);
if (isPending) return <IssueSkeleton />;
if (isError || !issue) return <ApiFailure retry={retry} />;
return <IssueDetail issue={issue} />;
```

Avoid a false kind of encapsulation. A page still needs to know whether it is rendering a pending,
failed, or successful state, because that is UI responsibility. A `useIssueScreenEverything` hook
that performs fetching, redirecting, toast messages, permission decisions, and JSX selection makes
the page smaller at the cost of an invisible flow. Hide repetition; do not hide important decisions.

```tsx
// Too broad: it makes tracing user-visible behavior require opening a private mini-framework.
const screen = useIssueScreenEverything({ issueId, navigate, notify, permissions });
return screen.element;
```

## 6. Effects synchronize; events command

An effect exists to synchronize React with something outside React: a browser subscription, a timer,
an imperative widget, or a network connection whose lifecycle follows rendering. A person clicking
“close issue” is a command, so the event invokes a mutation. Moving that command into an effect does
not make the program more declarative; it creates a second state transition and a timing question.

```tsx
function CloseIssueButton({ issueId }: { issueId: number }) {
  const mutation = useCloseIssue();
  return <button disabled={mutation.isPending}
    onClick={() => mutation.mutate(issueId)}>
    {mutation.isPending ? 'Closing…' : 'Close issue'}
  </button>;
}
```

```tsx
// Wrong: a local flag and an Effect obscure the causal user action.
const [shouldClose, setShouldClose] = useState(false);
useEffect(() => { if (shouldClose) closeMutation.mutate(issueId); }, [shouldClose, issueId]);
```

An extraction does not excuse a bad effect. First ask whether rendering, an event, or a derived
calculation expresses the behavior directly. Only then ask whether an unavoidable synchronization
belongs in a hook. This is especially important after Part 08: a custom hook must not recreate
manual request lifecycle state that TanStack Query already owns.

```tsx
export function useOnlineStatus() {
  const [online, setOnline] = useState(navigator.onLine);
  useEffect(() => {
    const update = () => setOnline(navigator.onLine);
    window.addEventListener('online', update); window.addEventListener('offline', update);
    return () => { window.removeEventListener('online', update); window.removeEventListener('offline', update); };
  }, []);
  return online;
}
```

This hook is justified because it packages one browser-system synchronization with cleanup. It does
not pretend that online status proves a request will succeed; the mutation still needs server error
handling. The distinction makes debugging better: inspect the browser signal, request, and server
response as separate evidence.

## 7. Optimize only after you can name the cost

`useMemo` caches a calculation and `useCallback` caches a function identity as performance tools.
Neither should be needed for correctness, and neither is a blanket rule for “professional React.” A
memoized child, an expensive calculation measured in a profile, or a library API that requires a
stable callback can justify one. A state-owner boundary or a clear hook API is usually more valuable
than premature identity management.

```tsx
const filtered = useMemo(() => filterIssues(issues, filters), [issues, filters]);
// Keep this only after the list size/profile demonstrates work worth avoiding.
```

```tsx
const onSelect = useCallback((issueId: number) => setSelectedIssueId(issueId), []);
// Keep this only if a memoized consumer or a documented API contract benefits from stability.
```

Modern React tooling may automate some memoization when configured, so the durable discipline is to
measure, make one change, and measure again. Do not require a React Compiler, add a new dependency,
or turn this lesson into a performance course. If deleting a memo changes behavior, locate the
missing source-of-truth or effect dependency instead of treating the memo as a semantic lock.

## 8. Test the observable boundary

Custom hooks are implementation details until they create an observable behavior. Prefer a route or
component test that proves the filter appears in a URL, the correct query result renders, a retry
button works, or a subscription cleans up when unmounted. Dedicated hook tests can be useful for a
complex pure interface, but they should not replace testing the screen that a person uses.

```tsx
await user.selectOptions(screen.getByLabelText('Status'), 'closed');
expect(await screen.findByRole('heading', { name: 'Closed issues' })).toBeVisible();
expect(window.location.search).toContain('status=closed');
```

For an extracted URL hook, this protects the contract better than asserting an internal call count.
For a Query hook, mock only at a sensible transport seam and ensure the query client setup resembles
the real provider. For a subscription hook, unmount and prove listeners are removed when practical.
The aim is not to test React itself; it is to preserve the behavior that justified the abstraction.

When a hook genuinely is the unit — a pure interface with several inputs whose combinations
would need a dozen component tests to cover — Testing Library gives you `renderHook`:

```tsx
import { renderHook, act } from '@testing-library/react';

it('debounces to the latest value', async () => {
  vi.useFakeTimers();
  const { result, rerender } = renderHook(({ value }) => useDebouncedValue(value, 300), {
    initialProps: { value: 'a' },
  });

  rerender({ value: 'ab' });
  rerender({ value: 'abc' });
  expect(result.current).toBe('a');            // nothing settled yet

  act(() => { vi.advanceTimersByTime(300); });
  expect(result.current).toBe('abc');          // the last value, not each one
  vi.useRealTimers();
});
```

`result.current` is the hook's return value after the most recent render, and `rerender`
supplies new props — which is how you exercise a hook whose whole job is reacting to
changing inputs. `act` is needed here because you are advancing timers rather than
performing a user interaction; `user-event` already wraps its own work.

Use it sparingly and deliberately. `useDebouncedValue` qualifies: it is pure, timing-dependent,
and expensive to provoke through a form. `useIssueFilters` usually does not — its contract is
"the URL changes and the right issues appear", and a `renderHook` test of it asserts the shape
of a return value that only one component reads. The question to ask is whether the hook has
a contract independent of any screen. If it does not, the screen is the test.

## 9. Refactor with a reversible plan

Architecture is safest when each change has a small observable purpose. Before moving code, name the current behavior, the boundary you will improve, and the command or test that proves behavior survived. Commit or otherwise preserve a clean checkpoint. Extracting a hook and changing routes, transport, providers, and tests in one move makes a later failure impossible to localize. The goal is not a pretty tree in a screenshot; it is a sequence in which every new boundary earns its place.

```text
observe repeated domain behavior
→ state the existing owner and callers
→ write/exercise an observable behavior
→ extract one narrow hook or module
→ run typecheck, lint, test
→ inspect route and request behavior
→ keep, reduce, or revert the abstraction
```

When an extraction grows parameters quickly, pause. It may mean callers share syntax but not a concept. A hook with seven optional flags can be harder to use correctly than two small local implementations. Duplication has a maintenance cost, but a poor abstraction imposes a coordination cost on every future caller. Choose the cheaper cost with the evidence currently in the project, not a prediction that every screen will become identical.

```ts
// Clear duplication may be cheaper than a premature generic configuration object.
const openIssues = issues.filter((issue) => issue.status === 'open');
const closedIssues = issues.filter((issue) => issue.status === 'closed');
```

If the behavior later acquires the same URL encoding, permissions, debounce policy, and visible
semantics across callers, then a named extraction has evidence. Until then, keep the code direct and
use comments sparingly. A comment should explain a decision that is not obvious from names, such as
why Query data remains in its cache rather than being copied to an external store; it should not
repeat what `setStatus` already says.

```tsx
// The route owns this state so a copied link and browser refresh reproduce the screen.
const { filters, setStatus } = useIssueFilters();
```

Feature boundaries also guide dependencies. Prefer a feature importing an explicitly shared HTTP
primitive over one feature reaching into another feature’s hook file. If two features need the same
concept, promote the smallest stable interface to shared infrastructure; do not promote an entire
feature because one helper was convenient. This keeps product vocabulary near product behavior and
prevents circular imports that make startup and testing harder to reason about.

Keep the migration legible to test code too. A test should normally enter through the same public
page, provider, or API seam that production uses. If a refactor forces every test to import a
private file solely to make it pass, that is evidence that the boundary has become more hidden, not
more useful. Test setup may provide a QueryClient or MemoryRouter, but the assertion should still
describe a person-visible outcome such as a selected filter, loading result, empty state, or retry.

```tsx
render(<IssueListRoute />, { wrapper: createTestApp('/issues?status=closed') });
expect(await screen.findByRole('heading', { name: 'Closed issues' })).toBeVisible();
```

This is also how to evaluate an extraction that appears elegant but changes behavior. Run the route,
watch the Network request, and reread the state inventory. If a filter stops surviving refresh or a
mutation no longer invalidates the list, the abstraction hid a source of truth. Restore the direct
path first, then make a narrower second attempt.

```text
good: issues/query hooks → shared/http
good: projects/query hooks → shared/http
review: projects/page → issues/private/useIssueFilters
```

During review, ask a reader to trace one user action without relying on folder names alone: location
change, filter parsing, query key, transport call, displayed state. If the path crosses several
wrappers whose names do not add information, reduce the abstraction. Transparent code is not code
with no functions; it is code where the important owner and consequence remain discoverable.

## Try it

Before: predict whether changing `?status=closed` in a second tab can affect the first tab.

Do: implement a small `useIssueFilters` equivalent around an existing duplicated URL parser, then
open two URLs with different filters and refresh each.

Observe: each address restores its own state; no store or effect had to synchronize it.

Explain: the hook made the URL API easier to use, but it did not relocate ownership.

## Common mistakes

### Extracting because two lines look alike

Two `useState` calls do not imply a reusable concept. Extract when callers need the same behavior
and an intentful API makes that behavior clearer.

### A mega-hook as service locator

A hook that silently supplies routing, API, permissions, notifications, and every query hides more
than it clarifies. Split by domain responsibility.

### Copying Query or URL state locally

The display may go stale after invalidation, navigation, or another tab changes the address. Wrap
the existing owner instead of manufacturing a cache.

### Memoizing by reflex

`useMemo` and `useCallback` are performance optimizations, not semantic guarantees. Profile a real
problem first; modern React tooling may also optimize some cases. Do not make correctness depend on
memoization identity.

## When this goes wrong

1. Name the value that is stale and identify its intended owner.
2. Inspect the URL and TanStack Query Devtools/network activity before adding state.
3. Check the hook’s inputs and effect dependencies; do not suppress the linter first.
4. Search for callers: does the hook still describe one coherent concept?
5. Move one file back or reduce the abstraction if a trace now crosses too many wrappers.

## Exercise

### Goal

Extract one repeated issue-tracker behavior into a focused custom hook.

**Mode: Manual, tool-backed evidence.** You run the frontend tools and inspect route/query behavior; no course verifier claims to assess the design decision.

### Starting state

Use your passing B08 project. Choose a repeated URL-filter, workspace-selection, or Query setup
that exists in at least two places; do not invent duplication solely for this exercise.

### Requirements

- State the duplicated concept and its source of truth in a short comment or architecture note.
- Give the hook a domain name and typed inputs/return value.
- Keep URL state in search parameters and server state in TanStack Query.
- Replace two real call sites without changing visible route behavior.
- Add or retain a focused test for an accessible outcome.

### Constraints

- Do not create `useEverything`, a generic manager, or a client-side copy of query data.
- Do not disable a hooks lint rule.

### Verification

Run `npm run typecheck`, `npm run lint`, and `npm run test`. Refresh and share the relevant URL;
then force a query invalidation and confirm the display follows the actual owner.

### Hints

<details><summary>Hint 1</summary>Write down what changes together before choosing a file name.</details>

<details><summary>Hint 2</summary>A URL helper can parse/default search params and expose intent-level setters.</details>

<details><summary>Hint 3</summary>A Query hook should return `useQuery`; do not mirror `data` in `useState`.</details>

## In the project

Used in B09 — Maintainable frontend. Extract only repeated complexity that B08 made visible. The
result should let a future reader find issue behavior without concealing the API, query cache, URL,
or local interaction that owns it.

## Closed-book checkpoint

Close the lesson first.

1. Why does calling the same custom hook twice not normally share local state?
2. What source of truth should `useIssueFilters` preserve, and how can you prove it?
3. Name one signal that a custom hook is too broad.
4. Why is copying Query data into local state dangerous after a mutation?

## Resources

### Read

- [React: Reusing Logic with Custom Hooks](https://react.dev/learn/reusing-logic-with-custom-hooks) — extraction and intent.
- [React: You Might Not Need an Effect](https://react.dev/learn/you-might-not-need-an-effect) — avoid effect-shaped state machines.

### Reference

- [React Rules of Hooks](https://react.dev/reference/rules/rules-of-hooks) — structural rules and lint support.

## You are done when

- [ ] You can name the owner of data before extracting a hook around it.
- [ ] One focused hook improves two real call sites without changing their behavior.
- [ ] The typecheck, lint, and frontend test commands pass.
- [ ] You can explain why a hook is not a global store.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_07.md` §§10–57, 237.
- Official sources: React custom hooks, Effects, Rules of Hooks, and memoization references linked above.
- Versions: React 19.2.3; TanStack Query 5.101.4; React Router 7.18.2.
- Consulted: 2026-08-15.
- Curriculum authority: `docs/dalt-fullstack/CURRICULUM.md` §20, FS09.1.
- DALT files inspected: `.dalt/course/lessons/45-fs08-1-client-state-versus-server-state/README.md`, `46-fs08-2-mutations-invalidation-and-optimistic-ui/README.md`, and `47-fs08-3-context-reducers-and-zustand/README.md`.
- Laravel source: not applicable; this is a frontend architecture lesson.
