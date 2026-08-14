# FS03.3 — Forms and state design

Lesson ID: FS03.3  
Part: 03 — React foundations  
Order: 3  
Status: Published  
Estimated effort: 75–105 minutes  
Difficulty: Applied  
Prerequisites: FS03.2 — State and events  
Project milestone: B03 — The local issue tracker  
Primary source dossier: FSO_PART_01.md; REACT_DOCS.md  
Last reviewed: 2026-08-14

## Before you start

Required:

- FS03.2 — State and events.

Recommended first:

- Revisit discriminated unions and optional fields from Part 02.

Going deeper in DALT Core — optional:

- None.

## Why a form exposes bad state boundaries

Creating an issue looks small: collect a title and append an object. But it forces choices about what is authoritative. If the input owns one title, the parent owns another, and a derived preview owns a third, the UI can disagree with itself.

For now a submission creates an in-memory issue. Refreshing loses it. That limitation is intentional: this lesson is about browser state and form behavior, not persistence or HTTP.

By the end, you can:

- explain a controlled input as state driving an input value;
- prevent the browser’s default form navigation;
- model draft state separately from saved issue state;
- lift state only when sibling components must coordinate;
- derive empty, filtered, and selected UI from source state rather than duplicate it.

## Predict before reading

What happens after typing into this input, and why?

```tsx
<input value={title} />
```

Assume `title` is state but there is no `onChange`. Then predict what the browser does when a `<form>` submits without `preventDefault()`.

## Controlled means React owns the value

An uncontrolled browser input can keep its own value. A controlled input receives its value from React state and reports edits through an event:

```tsx
const [title, setTitle] = useState('');

<label>
  Title
  <input
    value={title}
    onChange={(event: React.ChangeEvent<HTMLInputElement>) => setTitle(event.target.value)}
  />
</label>
```

The prediction is now explainable: a `value` without an update handler makes React repeatedly describe the old value, so the field cannot accept the edit. A normal form submission navigates/reloads; prevent it when the React handler owns the local behavior.

```tsx
function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
  event.preventDefault();
  // validate local draft, then request a state update
}
```

## Separate draft from domain data

The draft is not an `Issue` yet. It has only the fields the form currently collects. Give it a small honest type:

```tsx
type IssueDraft = {
  title: string;
  priority: 'low' | 'medium' | 'high';
};
```

On submission, reject blank titles, construct a complete local issue, append it immutably, and reset the draft. The parent should own the issue list when both `IssueForm` and `IssueList` need it; pass the form an `onCreate(draft)` callback rather than letting it mutate a parent value.

```text
IssuePage owns issues
     ├── IssueForm owns temporary draft, calls onCreate
     └── IssueList receives issues
```

This is lifting state: move the source of truth to the closest common owner. It is not a rule to move every input to the top of the application.

## Derive UI, do not mirror it

`hasVisibleIssues`, a filtered list, and a selected issue are descriptions computed from source state. Storing each one separately creates synchronization work and bugs. Use a single source of truth, then calculate what the current render needs.

```tsx
const visibleIssues = issues.filter(matchesFilter);
const hasVisibleIssues = visibleIssues.length > 0;
```

## Choose boundaries by responsibility

Extract a component when it owns a coherent responsibility, not merely because a file is long. `IssueForm` owns draft editing and emits a valid draft. `IssueList` describes many issues. `IssueDetails` describes the selected issue. `IssuePage` coordinates the shared issue collection, filter, and selection. Passing a long chain of unrelated props through components that do not use them is a signal to reconsider the boundary—but Context is not the automatic cure; it arrives later with a concrete shared-state problem.

Conditional UI follows the same rule: one visible branch for validation feedback, one for an empty filtered list, and one for a selected detail. Each is derived from the smallest real source of truth. This is why a local form is valuable before HTTP: you can isolate UI state decisions before remote failure and persistence complicate them.

## Focused exercise — Create one local issue

**Mode: tool-run + manual proof. Do not start B03.** In the resettable lab:

1. Create `IssueDraft` state for title and priority.
2. Build semantic controls: a visible `<label>` for each field and a submit `<button>`.
3. In `handleSubmit`, prevent default navigation, reject a whitespace-only title, and call a typed `onCreate` callback.
4. Let the parent append a complete local issue with an immutable functional update.
5. Reset the draft only after the parent receives a valid one.

Verification evidence: submitting a blank title leaves the list unchanged; submitting a valid title adds exactly one row without a page reload; the empty state disappears because it is derived from the list. Explain why the form draft is not duplicated in the parent.

### Hints

<details><summary>Hint 1 — what belongs in `onCreate`?</summary>
Pass an `IssueDraft`; the parent adds ID and default status because it owns the saved local issue collection.</details>

<details><summary>Hint 2 — why a real form?</summary>
It gives Enter-key submission and meaningful semantics. Handle its submit event rather than putting all behavior on a click.</details>

<details><summary>Answer and reasoning</summary>
The form owns its temporary input state; the page owns issues. `visibleIssues` and empty state are calculations, not extra setters.</details>

## Closed-book checkpoint

1. What makes an input controlled?
2. Why should a React form usually call `preventDefault` in its submit handler?
3. What is the difference between `IssueDraft` and `Issue`?
4. When should state move to a parent component?

## Next

FS03.4 turns this local behavior into an accessible, responsive interface using Tailwind utilities without adding a component library.
