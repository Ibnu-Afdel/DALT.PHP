# FS03.2 — State and events

Lesson ID: FS03.2  
Title: State and events  
Part: 03 — React foundations  
Order: 2  
Status: Published  
Estimated effort: 90–120 minutes  
Difficulty: Applied  
Prerequisites: FS03.1 — Components, JSX and typed props  
Project milestone: B03 — The local issue tracker  
Primary source dossier: `FSO_PART_01.md`; `REACT_DOCS.md`  
Last reviewed: 2026-08-14

## Why this matters

FS03.1's screen is honest but frozen. It renders whatever is in `src/issue.ts` and nothing a user does can change it. The moment you add a filter button, you need somewhere for "which filter is selected" to live — and the choice you make there is the single most consequential decision in a React codebase.

Get it wrong and the symptoms are familiar from any interface you have hated: two parts of the screen disagreeing about the same fact, a filter that clears when it shouldn't, a detail panel showing an issue that is no longer visible. None of those are rendering bugs. They are all the same bug — the same fact stored in more than one place, and the copies drifting.

This lesson is therefore not "how to use `useState`". `useState` takes ten minutes. It is about deciding **what is genuinely state, what is merely derived from state, and who owns it** — and about a mental model of rendering that makes React's stranger behaviours stop being surprises.

## Before you start

Required:

- FS03.1 — Components, JSX and typed props.
- Your lab copy at `.dalt/workspace/fs03-react-foundations`, with FS03.1's exercise finished and `npm test` passing.

Recommended first:

- Install React DevTools in your browser. It is not required, but the Components panel makes several observations in this lesson much cheaper.

Going deeper in DALT Core — optional:

- [Session and state](/learn/lessons/01-request-lifecycle) covers server-side state across requests. Different problem, useful contrast, entirely optional.

## By the end

You should be able to:

- decide whether a value should be state, a prop, or derived during render;
- explain why state is a snapshot for one render rather than a variable you mutate;
- predict what two `setCount(count + 1)` calls in one handler produce, and say why;
- choose the functional updater form for the right reason, not as a superstition;
- update arrays and objects in state without mutating them, and say what breaks if you do;
- place state at the correct owner and pass changes down as callbacks;
- state the Rules of Hooks and the reason they exist;
- find out why a component re-rendered instead of guessing.

## Predict before reading

Answer before reading on.

```tsx
function Counter() {
  const [count, setCount] = useState(0);

  function handleClick() {
    setCount(count + 1);
    setCount(count + 1);
    console.log(count);
  }

  return <button onClick={handleClick}>{count}</button>;
}
```

1. After one click, what number is on the button — 0, 1, or 2?
2. What does `console.log(count)` print during that click?
3. What would `setCount((c) => c + 1)` twice produce instead?
4. If a `visibleIssues` array is computed by filtering `issues`, should it be stored with `useState`?

Question 1 is the one that separates a working model from a memorised API.

## Mental model

```text
event happens
      ↓
handler calls a setter          ← "I request different state"
      ↓
React schedules a re-render
      ↓
the component function runs again, from the top
      ↓
`useState` hands back the NEW value
      ↓
new JSX description → React commits the differences
```

The load-bearing idea: **state is not a variable you change. It is a value React gives you for the duration of one render.**

Inside a single render, `count` is a constant. Calling `setCount` does not reassign it — nothing can, it is a `const`. It tells React "next time you call this function, hand back this instead." Your current render keeps the old value until it finishes, which is why `console.log(count)` prints the *old* number and why two `setCount(count + 1)` calls with the same `count` produce 1, not 2.

That is not a quirk to memorise. It is what makes each render a consistent snapshot: every value in one render agrees with every other value in that render, so the screen can never show a half-applied change.

## 1. `useState` declares a source of truth

```tsx
import { useState } from 'react';
import type { Issue } from './issue.js';

type StatusFilter = 'all' | Issue['status'];

function IssueList({ issues }: { issues: readonly Issue[] }) {
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  // ...
}
```

`useState` returns the current value and a setter. The argument is the *initial* value, used on the first render only — after that React ignores it and returns what it is holding.

Note the type. `Issue['status']` is FS02.2's indexed access: if the status union changes, `StatusFilter` follows automatically. `useState<StatusFilter>('all')` is one of the few places an explicit type argument earns its keep — inference from `'all'` alone would give you the type `string`, and every invalid filter value would then typecheck.

**Before adding state, ask whether the value is state at all.** A value belongs in state only if all three are true:

1. it changes over time;
2. it cannot be computed from props or other state;
3. it must survive a re-render.

`statusFilter` qualifies. `visibleIssues` does not — it is a function of `issues` and `statusFilter`, so it is computed during render:

```tsx
const visibleIssues = statusFilter === 'all'
  ? issues
  : issues.filter((issue) => issue.status === statusFilter);
```

No `useState`, no synchronisation, no way for it to be stale. **The answer to question 4 is no**, and this is the single highest-value habit in the lesson: derived values are recomputed, never stored. Every stored copy is a fact that can drift.

## 2. Event handlers request the next state

```tsx
<button type="button" onClick={() => setStatusFilter('todo')}>
  To do
</button>
```

`onClick` takes a function — you are handing React something to call later. `onClick={setStatusFilter('todo')}` calls it immediately during render, which sets state during render, which schedules a render, which sets state again. React stops that with a "Too many re-renders" error. If you see it, look for a missing arrow.

When you need the event object, type it by its element:

```tsx
function handleChange(event: React.ChangeEvent<HTMLSelectElement>) {
  setStatusFilter(event.target.value as StatusFilter);
}
```

That `as` is doing something real and slightly uncomfortable: the DOM types `value` as `string`, because a `<select>` genuinely could hold anything at runtime. FS02.5's rule still applies — the assertion is a *claim*, not proof. It is defensible here only because the options are rendered from the same finite union a few lines away. A cleaner alternative, and the one to prefer once you have a real form in FS03.3, is a small guard that checks the value against the known list and falls back.

Note also: React's `onClick` is not the DOM's. React attaches its own listeners and gives you a synthetic event with a consistent shape across browsers. It behaves like the DOM event for everything in this lesson.

## 3. When the next value depends on the current one

Now reveal the prediction. One click gives **1**, and `console.log` prints **0**:

```tsx
setCount(count + 1);   // count is 0 in this render → "set it to 1"
setCount(count + 1);   // count is STILL 0 here      → "set it to 1"
console.log(count);    // 0 — this render's snapshot never changes
```

Both calls said "set it to 1". React batches them and renders once. The functional form fixes it:

```tsx
setCount((current) => current + 1);   // 0 → 1
setCount((current) => current + 1);   // 1 → 2
```

React queues the functions and applies them in order, each receiving the result of the last. The button shows 2.

**The rule, stated so it is not a superstition:** use the functional updater whenever the next state is computed from the current state. Use the plain form when you are setting an unrelated value (`setStatusFilter('todo')` does not depend on the previous filter). Both are correct in their place; reaching for the updater everywhere is harmless but shows you have memorised a fix rather than the reason.

## 4. Immutable updates, and what mutation actually breaks

React decides whether to re-render by comparing the new state value with the old one by identity. Mutate an array in place and both references are the same object, so React concludes nothing changed and skips the render. Your data updated and the screen did not.

```tsx
// Wrong — same array reference, no re-render.
issues[0].status = 'done';
setIssues(issues);

// Right — a new array, and a new object for the one that changed.
setIssues((current) =>
  current.map((issue) =>
    issue.id === id ? { ...issue, status: 'done' } : issue,
  ),
);
```

Read the correct version carefully: `.map` builds a new array, and only the matching issue becomes a new object. Every other issue is the *same* object reference as before — which is exactly what you want, because it lets React skip work for the rows that did not change.

This is also where FS02.2's `readonly` earns its keep. Typing the prop as `readonly Issue[]` makes `issues[0].status = 'done'` a compile error rather than a debugging session.

## 5. State ownership

When two components need the same fact, it belongs to their closest common parent, passed down as a prop and changed through a callback passed down alongside it.

```text
ProjectPage        owns: statusFilter, priorityFilter, selectedIssueId, issues
  ├── FilterBar    props: statusFilter, onStatusChange
  ├── IssueList    props: visibleIssues, selectedIssueId, onSelect
  │     └── IssueRow   props: issue, isSelected, onSelect
  └── IssueDetail  props: selectedIssue
```

`IssueRow` does not know it is selected — it is *told*. Selection lives in one place, so a row and the detail panel cannot disagree about it.

Two questions decide every placement:

- **Who needs to read it?** State goes at or above the lowest common ancestor of all readers.
- **Who needs to change it?** Everyone else gets a callback, not the setter.

Push state as low as it will go without splitting a fact in two. State kept unnecessarily high re-renders more of the tree than it needs to; state kept too low has to be duplicated, and duplicated facts drift.

### The question the design has to answer

Selection and filtering interact. If you select `ISS-2` and then apply a filter that hides it, what should the detail panel show?

There is no single right answer — but there is a wrong process, which is to not decide and let the code answer by accident. Decide deliberately: keep showing it, or clear the selection. Then make the code say so. This is what "state architecture" means at this scale.

## 6. The Rules of Hooks, and why

1. Call hooks only at the top level of a component — never inside a condition, loop, or nested function.
2. Call hooks only from React components or from other hooks.

The reason is worth knowing, because it makes the rules obvious rather than arbitrary: **React identifies your state by call order, not by name.** There is no variable name in `useState(0)` for React to key on; it stores state in a list and matches it up by position on each render. Wrap a `useState` in an `if` and the positions shift between renders, so React hands the wrong slot's value back.

The ESLint plugin catches this. Understanding why means you can also see why a hook inside a `.map` callback is the same violation wearing a different hat.

## Try it

**Before — predict.** In your lab, before writing anything: if `ProjectPage` owns `statusFilter` and you click a filter button, which components re-render — only the button, only the list, or the whole subtree?

**Do — observe rendering.** Add a log to every component:

```tsx
console.log('IssueList render', { statusFilter });
```

Run `npm run dev`, open the console, and click through the filters. If React DevTools is installed, open Components, enable **Highlight updates when components render**, and click again.

**Observe — record.**

1. Which components logged on a filter click, and in what order?
2. Did `IssueRow` re-render even though its `issue` prop did not change?
3. Now `console.log(count)` — or `statusFilter` — directly inside a handler, immediately after calling its setter. What prints?
4. Mutate deliberately: write `issues[0].status = 'done'; setIssues(issues);` behind a button. Click it. Does the screen change? Does the underlying array?

**Explain.** For (2): why is re-rendering a child whose props are unchanged not automatically a bug? For (4): the data changed and the screen did not — describe, in terms of the snapshot model, exactly where the update was lost.

## Common mistakes

### Storing derived data in state

```tsx
const [visibleIssues, setVisibleIssues] = useState(issues);   // ✗
```

Now every place that changes `issues` or the filter must remember to recompute this too, and eventually one of them will not. Derive it during render.

### Calling the handler instead of passing it

`onClick={handleClick()}` runs on render. Symptom: "Too many re-renders", or an action that fires once on load and never again.

### Expecting state to change within the handler

```tsx
setStatusFilter('todo');
console.log(statusFilter);   // still the old value — this render's snapshot
```

Nothing is broken. If you need the new value in the handler, you already have it: it is the argument you just passed.

### Mutating state

`issues.push(newIssue)`, `issue.status = 'done'`, `sortedIssues.sort()` (`.sort` mutates in place — copy first). Severity: high, because the symptom is a *missing* update rather than a wrong one, and missing updates are hard to notice in a test you wrote yourself.

### Duplicating a prop into state

```tsx
const [title, setTitle] = useState(issue.title);   // ✗ unless it is a draft
```

The prop later changes and the state does not follow, because the initial value is only used once. This is legitimate only when you genuinely want an independent editable copy — which is FS03.3's form draft, and it should be named to say so.

### One state variable per field of the same thing

Three separate `useState` calls for a single issue's three fields will drift. Group values that change together.

## When this goes wrong

1. **The screen does not update.** Suspect mutation first: did you create a new array/object, or change the old one?
2. **"Too many re-renders."** A setter is being called during render — usually a handler invoked instead of passed.
3. **"Rendered fewer hooks than expected."** A hook is inside a condition or an early `return`.
4. **A value is one click behind.** You read state in the same handler that set it, or you used the plain setter where the functional updater was needed.
5. **Two parts of the screen disagree.** The same fact is stored twice. Find both copies and delete one.
6. **Something re-renders constantly.** Log with a distinguishing value and use DevTools' highlight-updates rather than guessing.

## Exercise

### Goal

Add filtering, selection and a status change to the lab screen, with exactly one source of truth for each fact.

### Starting state

Your lab copy with FS03.1's exercise complete: `ProjectPage` → `IssueList` → `IssueRow`, `npm test` passing.

### Requirements

1. Add `statusFilter` state, typed `'all' | Issue['status']`, with buttons that set it.
2. Add `priorityFilter` state, typed `'all' | Issue['priority']`, with its own buttons.
3. Derive `visibleIssues` from `issues` and both filters during render. Do not store it.
4. Add `selectedIssueId` state (`string | null`), set by clicking a row, and render an `IssueDetail` for the derived selected issue.
5. Add a "Mark done" action that sets one issue's status to `'done'` using a functional updater and an immutable update. This means `issues` must now be state.
6. Decide, and write down in a comment, what happens to the selection when a filter hides the selected issue — then implement your decision.
7. Add a test that filtering to `'done'` renders exactly one row.

### Constraints

- No form yet — that is FS03.3.
- No `useEffect`. Nothing here needs to synchronise with anything outside React, and reaching for an effect to "keep `visibleIssues` up to date" is the mistake this lesson exists to prevent.
- No mutation of arrays or objects held in state.
- No `any`. The `as` in the select handler is the only assertion permitted, and only if you keep the options in sync with the union.

### Verification

**Mode: tool-run plus manual proof.** Nothing here is automatically graded.

- `npm run typecheck` exits clean; `npm test` passes including your new test.
- In the browser: both filters narrow the list; combining them narrows further; clearing returns to three rows.
- Clicking a row shows its details; clicking another switches them.
- "Mark done" twice on the same issue leaves it `done` — not double-applied, not reverted.
- Prove immutability: log `issues` before and after "Mark done" and confirm the array identity changed while the untouched issues are the same objects.
- Prove the compiler is helping: try `setStatusFilter('urgent')` and confirm it is rejected.

### Hints

<details>
<summary>Hint 1 — when do I need the functional updater?</summary>

When the next value is computed from the current one. "Mark done" maps over the current array, so yes. Setting a filter to a fixed value does not, so no.
</details>

<details>
<summary>Hint 2 — should <code>visibleIssues</code> be state?</summary>

No. It is a calculation from `issues` and the two filters. Compute it in the component body on every render — that is not a performance problem at this size, and correctness comes first.
</details>

<details>
<summary>Hint 3 — where does <code>selectedIssueId</code> live?</summary>

`IssueList` and `IssueDetail` both need it, so it belongs to their closest common parent. Store the *id*, not the issue object — then derive the issue with `find`, and it cannot go stale when the issue is updated.
</details>

<details>
<summary>Hint 4 — the selection-versus-filter decision</summary>

Both answers are defensible. Keeping it shown means the detail panel can display an issue not in the list, which needs no extra code but may confuse. Clearing it needs you to decide *when* — during render is wrong, because that is setting state during render. Deriving "is the selection still visible?" and rendering accordingly avoids the problem entirely.
</details>

<details>
<summary>Reference explanation — read after an honest attempt</summary>

`ProjectPage` owns four things: `issues`, `statusFilter`, `priorityFilter`, `selectedIssueId`. Everything else is derived — `visibleIssues` by filtering, `selectedIssue` by `find`.

Storing `selectedIssueId` rather than the issue object is the detail worth transferring. An id is a reference into the source of truth, so "Mark done" on the selected issue updates the detail panel automatically. Storing a copy of the object would give you a detail panel showing a stale status — the same class of bug as storing `visibleIssues`, in a different costume.

The selection-versus-filter question has no canonical answer, but deriving `const selectedIssue = visibleIssues.find(...)` rather than `issues.find(...)` quietly implements "the detail clears when filtered out" with no extra state and no effect.
</details>

## In the project

This is the state architecture **B03** is built on. The four owned values and the two derived ones survive into the real issue tracker essentially unchanged.

It also sets up the problem Part 08 solves. Right now every fact is local and instantly correct because there is one copy in one process. From Part 04 the real facts live on a server, and "one source of truth" becomes genuinely hard — a cached copy in the browser is *always* a second copy. Notice how easy correctness is here, so that you recognise what changes when it stops being easy.

## Closed-book checkpoint

Close the lesson and the lab.

1. What are the three conditions for a value to be state?
2. Why does `setCount(count + 1)` twice in one handler produce 1 and not 2?
3. When is the functional updater required, and when is the plain form fine?
4. Why does mutating an array in state often produce no visible change at all?
5. Where does a fact belong when two sibling components both read it?
6. Why does React require hooks at the top level? What does it use to identify your state?
7. Why store `selectedIssueId` rather than the selected issue object?
8. Give an example of a value that looks like state and is not.

Then reopen and correct your answers in a different colour.

## Resources

### Read

- [React: State — A Component's Memory](https://react.dev/learn/state-a-components-memory)
- [React: State as a Snapshot](https://react.dev/learn/state-as-a-snapshot) — the one that makes question 2 obvious.
- [React: Choosing the State Structure](https://react.dev/learn/choosing-the-state-structure) — "Avoid redundant state" and "Avoid duplication".

### Go deeper

- [React: Queueing a Series of State Updates](https://react.dev/learn/queueing-a-series-of-state-updates)
- [React: Updating Arrays in State](https://react.dev/learn/updating-arrays-in-state)
- [React: Sharing State Between Components](https://react.dev/learn/sharing-state-between-components)

### Reference

- [React: Rules of Hooks](https://react.dev/reference/rules/rules-of-hooks)
- [React: `useState`](https://react.dev/reference/react/useState)

## You are done when

- [ ] Both filters work, combine, and clear.
- [ ] `visibleIssues` and `selectedIssue` are derived, not stored — I can point at the code and show it.
- [ ] "Mark done" uses a functional updater and an immutable update, and I proved the array identity changed.
- [ ] I wrote down my selection-versus-filter decision as a comment and implemented it.
- [ ] I observed which components re-render on a filter click and can explain it.
- [ ] I can explain the two-`setCount` result without looking it up.
- [ ] `npm run typecheck` and `npm test` pass, including the filter test.
- [ ] I attempted the closed-book checkpoint without notes.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_01.md`; `docs/dalt-fullstack/sources/REACT_DOCS.md`
- Official sources: React Learn — State as a Snapshot, Queueing a Series of State Updates, Choosing the State Structure, Updating Arrays in State, Sharing State Between Components; React Reference — Rules of Hooks, `useState`
- Versions: React 19.2.3, TypeScript 5.9.3, Vite 8.0.12, Vitest 4.0.18 (CR-08 pinned toolchain)
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/course/fullstack/react-foundations-lab/starter/**`
- Curriculum authority: `CURRICULUM.md` §13 FS03.2
- Laravel bridge: not applicable — client-side state has no DALT or Laravel counterpart
