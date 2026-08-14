# FS03.2 — State and events

Lesson ID: FS03.2  
Part: 03 — React foundations  
Order: 2  
Status: Published  
Estimated effort: 75–105 minutes  
Difficulty: Applied  
Prerequisites: FS03.1 — Components, JSX and typed props  
Project milestone: B03 — The local issue tracker  
Primary source dossier: FSO_PART_01.md; REACT_DOCS.md  
Last reviewed: 2026-08-14

## Before you start

Required:

- FS03.1 — Components, JSX and typed props.

Recommended first:

- Revisit immutable array updates from FS01.1.

Going deeper in DALT Core — optional:

- None.

## Why state needs a different mental model

A filter button should change which issues are visible. It is tempting to change an array or a variable and expect the page to notice. React does not continuously watch local variables. A component render receives a snapshot of state; an event can request the next snapshot.

This distinction is what prevents a local prototype from becoming a collection of invisible mutations. Later, server data will add another boundary; for now, learn the local one clearly.

By the end, you can:

- explain state as input to a render snapshot;
- attach typed event handlers without calling them during render;
- use a functional state updater when next state depends on prior state;
- update arrays and objects immutably;
- locate shared state at the nearest component that needs to coordinate it.

## Predict before reading

After one click, what is `count` in the final `console.log`?

```tsx
setCount(count + 1);
setCount(count + 1);
console.log(count);
```

Write an answer before continuing. Also predict the difference if both calls use `setCount((current) => current + 1)`.

## State is input; events request the next render

```text
current state snapshot
        ↓
render describes UI
        ↓
user event
        ↓
state update is queued
        ↓
next render receives next snapshot
```

`useState` gives a current value and a setter. Call the hook unconditionally at the top level of a component; React relies on calls occurring in the same order every render.

```tsx
const [selectedIssueId, setSelectedIssueId] = useState<string | null>(null);

function selectIssue(id: string) {
  setSelectedIssueId(id);
}
```

Pass a handler to an event. `onClick={selectIssue(issue.id)}` calls it during render, which is not what a click means. Instead create a function React can call later:

```tsx
<button type="button" onClick={() => selectIssue(issue.id)}>View details</button>
```

The original prediction: both `count + 1` expressions read the same snapshot, and the log reads the old snapshot. When the next value depends on the prior value, state the relationship explicitly:

```tsx
setCount((current) => current + 1);
setCount((current) => current + 1);
```

## Immutable updates preserve the old snapshot

Never change state values in place. This is wrong because it mutates the object a previous render still refers to:

```tsx
// issue.status = 'done';
```

Describe a new value instead:

```tsx
setIssues((current) => current.map((issue) =>
  issue.id === issueId ? { ...issue, status: 'done' } : issue,
));
```

The old array and old issue object remain intact; only the changed issue gets a new object. This makes the update inspectable and keeps React's render inputs honest.

## State ownership is a coordination question

Keep state close to the component that uses it, then move it upward only when siblings must agree. A row can own a temporary hover detail; a page should own the selected issue ID if both the list and a detail panel need it. Do not duplicate `selectedIssue` and `selectedIssueId`: derive the selected issue from the list and ID.

```tsx
const selectedIssue = issues.find((issue) => issue.id === selectedIssueId) ?? null;
```

## Debug a render with evidence

When a click appears to do nothing, do not begin by adding another state variable. First put a temporary `console.log` in the handler: did the event run? Then log the values at the top of the component: which snapshot rendered? Finally use React DevTools, when available, to inspect the component tree and its current props/state. Remove debugging output when the question is answered.

The Part 03 requirement includes both status and priority filtering. Keep them as two source values, then derive one visible list by applying both predicates. Expandable details are the same ownership exercise: the page owns `selectedIssueId`; a detail component receives the derived issue or `null`. If filtering hides the selected issue, choose and document one honest rule—clear selection, or show that the selection is outside the current filter. Do not leave two competing “selected issue” values.

## Focused exercise — Filter and select locally

**Mode: tool-run + manual proof. B03 remains unbuilt.** In the resettable lab from FS03.1, extend the typed local list:

1. Store `statusFilter` and `priorityFilter` state values (`'all' | Issue['status']` and `'all' | Issue['priority']`).
2. Render buttons that set it through click handlers.
3. Derive `visibleIssues`; do not store a second copied list in state.
4. Store one `selectedIssueId`, then render a details region from the derived issue.
5. Add a “mark done” action with an immutable functional update.

Verification evidence: select an issue, filter it out, and explain whether the detail region should remain or clear; click “mark done” twice and inspect that the original mock array was not mutated in place. Your editor should reject a handler that attempts to set an invalid status.

### Hints

<details><summary>Hint 1 — when is a functional updater necessary?</summary>
Use it when the next value is computed from the current state: changing an issue inside the current array is that case.</details>

<details><summary>Hint 2 — should visible issues be state?</summary>
No. It is a calculation from `issues` and `statusFilter`, so derive it during render.</details>

<details><summary>Answer and reasoning</summary>
Keep source-of-truth state small: issues, filter, and selected ID. Compute the list and selected issue from those values every render.</details>

## Closed-book checkpoint

1. Why can logging a state variable immediately after its setter show the old value?
2. When do you use `setValue(value + 1)` versus `setValue((value) => value + 1)`?
3. Why is `map` plus object spread safer than changing `issue.status` directly?
4. What is the smallest owner for state shared by an issue list and detail panel?

## Next

FS03.3 lets a user create an issue with a controlled form while keeping the source of truth deliberate.
