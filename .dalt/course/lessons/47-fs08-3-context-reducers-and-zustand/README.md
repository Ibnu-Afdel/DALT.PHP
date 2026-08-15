# FS08.3 — Context, reducers and Zustand

Lesson ID: FS08.3
Title: Context, reducers and Zustand
Part: 08 — Server and application state
Order: 3
Status: Published
Estimated effort: 90–120 minutes
Difficulty: Integration
Prerequisites: FS08.2 — Mutations, invalidation and optimistic UI
Project milestone: B08 — Intentional state architecture
Primary source dossier: FSO_PART_06.md
Last reviewed: 2026-08-15

## Why this matters

“Global state” is an answer often chosen before there is a question. An issue tracker has state
that crosses component boundaries, but most of it already has a better owner: a route owns location,
a QueryClient owns remote snapshots, and a form owns an unfinished draft. Moving all of that into
one store creates duplicate sources of truth and makes a small application hard to trace.

This lesson compares client-state tools in increasing order of coordination pressure. Lift state and
compose first. Use Context when a stable value is needed throughout a subtree. Add a reducer when
related transitions need names and testing. Consider Zustand only when an actual shared client
concern makes those tools awkward. It never becomes a second server cache.

## Before you start

Complete FS08.2. List the important values on one B07 screen and classify them before installing
anything. The default B08 result may contain no production Zustand dependency: that is correct when
no genuine shared-client problem exists.

```sh
npm run typecheck
npm run lint
npm run test
```

Going deeper in DALT Core — optional:

- None. React state design is part of this standalone Fullstack track.

## By the end

- choose local state, lifting, Context, a reducer, or an external store for a stated reason;
- model related transitions as reducer actions rather than scattered setters;
- keep Context values narrow and stable enough for their subtree;
- describe Zustand stores, actions, and selectors as a bounded comparison;
- refuse to duplicate URL or server state in a client store.

## Predict before reading

1. If a filter must be copied to another browser, can Context make it shareable?
2. Does a value read by five components automatically belong in Zustand?
3. What failure becomes easier to see when an action is named `opened` rather than `setOpen(true)`?

## Mental model

```text
local state → lift through composition → Context → Context + reducer → external store
     ↑                                                                  ↓
smallest owner that coordinates the interaction              only when justified

URL state and TanStack Query server state stay on their own paths.
```

The progression is not a ritual. It makes cost visible before adding a dependency. Passing a prop
through two components is not automatically prop drilling; it can be an explicit dependency. A
context provider is not automatically a performance problem; a giant provider holding unrelated
changing values often is. Ask who changes the value, who reads it, how long it lives, and whether a
browser refresh should restore it.

## Start with the closest owner

An issue editor owns its unsaved title because it is the only place that changes it. A parent can
coordinate two siblings without creating an application-wide service. Use callbacks and props while
the relationship remains obvious.

```tsx
function IssueToolbar() {
  const [isFilterPanelOpen, setFilterPanelOpen] = useState(false);
  return <><button onClick={() => setFilterPanelOpen(true)}>Filters</button>
    <FilterPanel open={isFilterPanelOpen} onClose={() => setFilterPanelOpen(false)} /></>;
}
```

If the filter value needs a durable address, use the search parameters from FS07.1 instead. Local
open/closed presentation is not the same as the selected filter. Avoid keeping both in a store just
because they are nearby visually.

```tsx
const [search, setSearch] = useSearchParams();
const status = search.get('status') === 'closed' ? 'closed' : 'open';
```

### The ladder, and how to know you are on the wrong rung

Each rung costs more than the one below it — in concepts, in indirection, and in what a new
reader has to hold in their head. Climb only when the rung you are on has a *named* problem:

```text
useState in the owner        → climb when two siblings need it and neither owns it
lift to the common parent    → climb when the parent is threading it through 3+ layers
                               that do not use it themselves
Context                      → climb when consumers re-render on values they never read,
                               and splitting the context has stopped helping
Context + reducer            → not a rung: add this whenever transitions have a vocabulary,
                               at any level, including inside one component
external store               → climb when the provider cannot sit above all consumers,
                               or selection is genuinely needed
```

The reducer is deliberately off to one side. It is not "more global than Context" — it is a
way of organising transitions, and it is just as appropriate inside a single component as
behind a provider. Conflating the two is why people end up with a Context they did not need
wrapped around a reducer they did.

Two signals that you have climbed too high. First, the provider sits at the application root
but every consumer is inside one route — the value belongs to that route. Second, you cannot
describe the state without saying "and the server also has a copy of this", which means it
is server state and belongs in a query.

And the signal you have climbed too low: the same `useState` and the same `useEffect` copied
into three components to keep them agreeing. Copying is how a shared concern announces
itself.

## Use Context for a subtree contract

Context removes repeated plumbing when many nested components need the same stable client concern.
A theme preference, command-palette controller, or layout-density setting can fit. Keep the value
focused: the provider should communicate one contract, not become a dumping ground for every setter.

```tsx
type Density = 'comfortable' | 'compact';
const DensityContext = createContext<{ density: Density; setDensity: (value: Density) => void } | null>(null);

export function DensityProvider({ children }: PropsWithChildren) {
  const [density, setDensity] = useState<Density>('comfortable');
  return <DensityContext value={{ density, setDensity }}>{children}</DensityContext>;
}
```

Give consumers a hook that fails clearly outside the provider. That is better than silently using a
default which makes a missing provider appear to work until a person changes a preference.

```tsx
export function useDensity() {
  const value = useContext(DensityContext);
  if (value === null) throw new Error('useDensity must be used within DensityProvider');
  return value;
}
```

Context propagates when its value changes. Do not place a rapidly changing issue collection in it:
TanStack Query already manages that remote lifecycle and invalidation. Split unrelated contexts when
their update rates and consumers differ, rather than memoizing a giant object as a reflex.

## What Context actually costs

Context is a distribution mechanism, not a state manager, and it has one behaviour that
surprises people in production: **every consumer re-renders whenever the provider's value
changes identity** — not when the part they read changes. Identity, not equality.

The provider above rebuilds `{ density, setDensity }` on every render, so a parent
re-rendering for an unrelated reason gives every consumer a new object and re-renders all
of them. With three consumers nobody notices. With a density context read by every row in a
four-hundred-issue list, typing in a filter box becomes visibly slow.

```tsx
export function DensityProvider({ children }: PropsWithChildren) {
  const [density, setDensity] = useState<Density>('comfortable');
  // Stable identity: a new object only when density actually changes.
  const value = useMemo(() => ({ density, setDensity }), [density]);
  return <DensityContext value={value}>{children}</DensityContext>;
}
```

`setDensity` is already stable — React guarantees the setter identity across renders — so
`density` is the only real dependency.

Memoising the value fixes accidental re-renders. It does not fix the deeper case, which is
one context carrying values that change at different rates:

```tsx
// A component that only dispatches still re-renders every time the state changes.
const PaletteContext = createContext<{ state: PaletteState; dispatch: Dispatch<PaletteAction> } | null>(null);
```

The standard fix is to split by update rate rather than by topic. The dispatch function
never changes; the state changes on every keystroke. Two contexts, and the components that
only dispatch stop re-rendering entirely:

```tsx
const PaletteStateContext = createContext<PaletteState | null>(null);
const PaletteDispatchContext = createContext<Dispatch<PaletteAction> | null>(null);
```

That split is the honest answer to "why would I ever want Zustand?". A store lets a
component subscribe to a *slice*, so the same problem is solved by selecting rather than by
splitting providers. When you have split a context three ways and it is still awkward, you
have found the evidence the project rule asks for — and not before.

Do the cheap thing first. Most contexts in most applications hold one slow-changing value,
need `useMemo` and nothing else, and would be made worse by a store.

## Add a reducer when transitions have a vocabulary

Several related flags can become hard to reason about: a command palette is closed, opening, open
with a selected item, or closing. A reducer makes allowed transitions and inputs explicit. The
reducer is pure; it does not fetch or write storage. Event handlers or effects may perform those
boundary operations and dispatch the result.

```ts
type PaletteState = { open: boolean; query: string; selectedIndex: number };
type PaletteAction = { type: 'opened' } | { type: 'closed' } | { type: 'queryChanged'; query: string };

function paletteReducer(state: PaletteState, action: PaletteAction): PaletteState {
  if (action.type === 'opened') return { ...state, open: true };
  if (action.type === 'closed') return { ...state, open: false, query: '', selectedIndex: 0 };
  return { ...state, query: action.query, selectedIndex: 0 };
}
```

Use `useReducer` locally when one component owns the interaction; pair it with Context only when a
subtree truly needs to dispatch and read it. Reducers do not make state global. They make complex
local transitions understandable and testable.

```tsx
const [palette, dispatch] = useReducer(paletteReducer, { open: false, query: '', selectedIndex: 0 });
dispatch({ type: 'opened' });
```

## A reducer is a pure function, so test it like one

This is the best-value testing opportunity in Part 08, and it costs almost nothing. A
reducer takes a state and an action and returns a state. No React, no DOM, no fake client,
no query client — the bottom row of FS07.3's pyramid, where the tests run in single-digit
milliseconds:

```ts
describe('paletteReducer', () => {
  const closed: PaletteState = { open: false, query: '', selectedIndex: 0 };

  it('clears the query and selection when closing', () => {
    const open = { open: true, query: 'assign', selectedIndex: 3 };

    expect(paletteReducer(open, { type: 'closed' })).toEqual(closed);
  });

  it('resets the selection when the query changes', () => {
    const open = { open: true, query: 'as', selectedIndex: 3 };

    expect(paletteReducer(open, { type: 'queryChanged', query: 'assi' }).selectedIndex).toBe(0);
  });

  it('does not mutate the state it was given', () => {
    const before = { ...closed };
    paletteReducer(closed, { type: 'opened' });

    expect(closed).toEqual(before);
  });
});
```

The third test is the one worth keeping. Accidental mutation — `state.open = true` instead
of returning a new object — produces a component that does not re-render, because React
compares by identity and sees the same object. That bug presents as "my click does nothing",
sends people looking at event handlers, and is caught here in four lines.

The transitions worth testing are the ones with a *vocabulary*: what closing discards, what
resets a selection, which actions are ignored in which state. Those are product decisions.
Testing that `{type: 'opened'}` sets `open: true` restates the implementation and will never
fail for a useful reason.

There is a design signal here too. If a transition is awkward to test because it needs a
fetch, a timer or `localStorage`, the reducer is doing something a reducer must not do. Move
the boundary work into the handler and dispatch its result — which is the rule the previous
section stated, now with a way to notice when you have broken it.

## Understand Zustand without promoting it by default

Zustand creates an external store and lets a component subscribe to a selected slice. A small store
has state, actions, and narrow selectors. It can help a command palette controlled by a header and
independent layouts when threading or provider placement has become genuinely awkward. It does not
improve a server request, cache freshness, or authorization.

```ts
import { create } from 'zustand';

type PaletteStore = { open: boolean; openPalette: () => void; closePalette: () => void };
export const usePaletteStore = create<PaletteStore>((set) => ({
  open: false,
  openPalette: () => set({ open: true }),
  closePalette: () => set({ open: false }),
}));
```

Select the smallest useful value rather than reading a whole store in every consumer. Narrow
selection limits rerenders and makes a component's dependency obvious.

```tsx
const open = usePaletteStore((state) => state.open);
const openPalette = usePaletteStore((state) => state.openPalette);
```

Do not write `issues: Issue[]` beside client concerns. It duplicates the query cache, forces you to
invent loading/error/stale semantics again, and creates disagreement after mutation. Do not put
`status` there either when it belongs in a copied URL. External stores are tools for specific shared
client coordination, not a destination for all application data.

### Selectors are the whole point, and the easy thing to get wrong

A store subscribes a component to exactly what its selector returns. Return more and you
subscribe to more:

```tsx
const { open } = usePaletteStore();                      // subscribes to every change
const open = usePaletteStore((state) => state.open);     // subscribes to `open` only
```

The first line looks tidier and re-renders the header on every keystroke typed into the
palette. Written that way throughout, a store performs worse than the Context it replaced,
which is how "we adopted a store and it got slower" happens.

The subtler trap is returning a **new object** from a selector. Zustand compares the
previous and next selected value with `Object.is`, so a fresh object is never equal and the
component re-renders on every store change:

```tsx
// New object every time — re-renders on any change, including unrelated ones.
const { open, query } = usePaletteStore((state) => ({ open: state.open, query: state.query }));

// Two subscriptions, each compared by value.
const open = usePaletteStore((state) => state.open);
const query = usePaletteStore((state) => state.query);
```

Two calls is the simplest correct answer and usually the right one. When you genuinely need
several fields at once, `useShallow` compares the object one level deep:

```tsx
import { useShallow } from 'zustand/react/shallow';

const { open, query } = usePaletteStore(useShallow((state) => ({ open: state.open, query: state.query })));
```

Actions are worth selecting individually too, because they never change identity — a
component that only dispatches then never re-renders at all.

### A module-level store outlives your test

`create()` runs once per module, so the store is a singleton shared by every test in the
run. State set by one test is visible to the next, and the failure appears in whichever test
happens to run second:

```ts
const initial = usePaletteStore.getState();

afterEach(() => {
  usePaletteStore.setState(initial, true);   // `true` replaces rather than merges
});
```

This is the same lesson as the query client in FS08.1, arriving from a different direction:
anything shared across tests must be reset between them. It is also a hint about the design.
A store you must carefully reset in tests is global mutable state, and the reason to keep it
small and few is exactly that this cost scales with it.

## Decide with evidence

Before adding Zustand permanently, answer the blueprint gate: why are props, Context, or a reducer
awkward here? “Several files import it” is not enough. State the exact interaction, consumers,
lifetime, and why its values are neither remote facts nor URL state. If you cannot make the case,
keep the example as a comparison and leave the dependency out of the project.

```text
Need: header and route layouts open one command palette
Not server state: commands are local UI capability
Not URL state: reopening after refresh is not desirable
Why store: provider placement crosses independent layout roots awkwardly
Decision: small PaletteStore with selectors, no issue data
```

That decision is reversible and legible. A one-sentence record is more useful than silently
installing a fashionable store because it tells the next maintainer why the boundary was chosen.

## Try it

Take one client-only interaction from the issue tracker. First implement it with the closest owner.
If several components coordinate it, use focused Context plus a reducer and test two named actions.
Only then write a short decision record for or against a small Zustand comparison. Confirm that the
issue list, current user, comments, and URL filter still come from their existing boundaries.

```text
draft title             → local editor state
issue status filter     → URL search parameter
issue and comments      → TanStack Query
palette open/selection  → reducer/context or justified small external store
```

Then write reducer tests before wiring any of it to a component: the transitions are pure
functions and cost nothing to cover. Finish by checking your provider value is memoised and
that no consumer re-renders on a value it does not read — React DevTools' "Highlight updates
when components render" makes that visible in about ten seconds.

## Common mistakes

- Reaching for Context because a prop passed through two components felt tedious. Two levels
  of props is not prop drilling; it is how React works.
- Building the provider value inline, so every consumer re-renders whenever an unrelated
  ancestor does.
- One context holding values that change at different rates, so a component that only
  dispatches re-renders on every keystroke.
- A `null` default with no guard hook, so a missing provider fails somewhere far away
  instead of at the component that needed it.
- Mutating state inside a reducer, which produces a UI that does not update and sends you
  hunting through event handlers.
- Doing boundary work — fetch, timer, `localStorage` — inside a reducer, which makes it
  untestable and its results unpredictable under Strict Mode's double invocation.
- Copying server data into Zustand "so everything is in one place", which reinvents loading,
  error and staleness and guarantees the two copies disagree after a mutation.
- Reading the whole store in every consumer, `useStore((s) => s)`, which subscribes each one
  to every change and discards the reason to use a store.
- One growing global store because the first one was easy, which is the state of affairs
  every tool on this page exists to avoid.
- Installing Zustand to complete the lesson. A project with no production store is the
  expected outcome, not a gap in your work.

## When this goes wrong

If a context consumer rerenders surprisingly often, inspect whether unrelated values share the
provider; split the contract before applying memoization. If reducer actions lead to impossible
states, tighten the action union and return to the transition table. If a Zustand store now contains
queries, remove the duplicate and make the query cache authoritative again.

```ts
// Wrong: this creates a competing cache with no invalidation policy.
type Store = { issues: Issue[]; setIssues: (issues: Issue[]) => void };
```

## Exercise

**Goal:** Make one shared client-only interaction legible without globalizing server data.

**Starting state:** B08 has query-backed issues and mutation-backed writes.

**Requirements:** Classify the interaction, implement the smallest owner that coordinates it, and
use Context plus a reducer only when related transitions need shared access. Write a short
Zustand-gate decision. If you use Zustand, use a small store with actions and selectors only for
that interaction.

**Verification:** Trigger every named action, refresh the page, inspect that URL and query state
retain their own behavior, and run the frontend suite. Make one reducer action fail a test before
restoring it, or demonstrate the equivalent visible behavior manually.

**Mode: tool-run — browser behavior plus `npm run typecheck`, `npm run lint`, and `npm run test`.** This is an architectural choice; the stated interaction and observable result are the evidence.

**Hints:** A Context provider around one layout is usually enough. Start with a reducer before
installing a store. If the state represents an API response, return to FS08.1.

## In the project

B08 ends with a state inventory that names each major value's owner. The inventory is the durable
artifact, not the number of libraries imported. Part 09 can now extract repeated behavior into
custom hooks and feature boundaries because data fetching, mutations, routes, and client UI have
explicit seams.

## Closed-book checkpoint

1. What question must be answered before moving state out of a component?
2. When is Context preferable to passing a prop?
3. What does a reducer improve over related independent setters?
4. What are a Zustand store's three basic pieces?
5. Why must issue collections stay out of Zustand in this application?
6. Why does a consumer re-render when the provider's value changes identity rather than value?
7. Which reducer test catches accidental mutation, and what symptom does that bug produce?
8. Why does returning a new object from a Zustand selector defeat the purpose of selecting?
9. What must a test do about a module-level store, and why?

## Resources

### Read

- [React: Passing Data Deeply with Context](https://react.dev/learn/passing-data-deeply-with-context)
- [React: Extracting State Logic into a Reducer](https://react.dev/learn/extracting-state-logic-into-a-reducer)
- [Zustand: Introduction](https://zustand.docs.pmnd.rs/getting-started/introduction)

### Go deeper

- [React: Choosing the State Structure](https://react.dev/learn/choosing-the-state-structure)

## You are done when

- [ ] Each shared interaction has a stated owner and lifetime.
- [ ] Context and reducers have focused contracts rather than an application-wide bucket.
- [ ] URL and query cache state have not been duplicated in a client store.
- [ ] A Zustand decision is justified, bounded, and optional when no use case exists.
- [ ] Every provider value is stable across renders that did not change it.
- [ ] Reducer transitions are covered by tests, including one that proves no mutation.
- [ ] Any store selector returns a primitive, or uses a shallow comparison deliberately.
- [ ] `npm run typecheck`, `npm run lint`, and `npm run test` pass.

## Maintainer source record

Source dossier: `docs/dalt-fullstack/sources/FSO_PART_06.md`.

Official sources: React Context, reducer, and state-structure documentation; Zustand introduction, linked above.

Versions: React 19.2.3; TypeScript 5.9.3; TanStack Query 5.101.4; Zustand 5.0.15, a bounded comparison unless the B08 gate justifies it.

Consulted: 2026-08-15.

Curriculum authority: `CURRICULUM.md` §19, FS08.3; `PROJECT_BLUEPRINT.md` §§48–50.
