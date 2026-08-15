# FS03.3 — Forms and state design

Lesson ID: FS03.3  
Title: Forms and state design  
Part: 03 — React foundations  
Order: 3  
Status: Published  
Estimated effort: 90–120 minutes  
Difficulty: Applied  
Prerequisites: FS03.2 — State and events  
Project milestone: B03 — The local issue tracker  
Primary source dossier: `FSO_PART_01.md`; `REACT_DOCS.md`  
Last reviewed: 2026-08-14

## Why this matters

A form is where every weakness in a state design becomes visible at once. It introduces a second kind of data — a half-finished thing the user is still typing — and that thing is *not* the same as the issues your application owns. Confuse the two and you get the bugs everyone has met: a list that updates as you type, a draft that survives when it should have cleared, a submit button that fires twice.

FS00.2 already showed you what the browser does with a form on its own: it serialises the fields, sends a request, and navigates to the response. That behaviour is genuinely good, and you are about to switch it off. This lesson is about switching it off deliberately — knowing what you gave up, and taking responsibility for replacing it.

It is also where FS03.2's habit gets its real test. Deriving `visibleIssues` was easy because nothing was competing for ownership. A form makes ownership contested, and the right answer stops being obvious.

## Before you start

Required:

- FS03.2 — State and events.
- Your lab copy at `.dalt/workspace/fs03-react-foundations`, with FS03.2's exercise complete and `npm test` passing.

Recommended first:

- Re-read FS00.2's section on what a browser does with an ordinary form submission. You are about to prevent exactly that.

Going deeper in DALT Core — optional:

- [Validation and error contracts](/learn/lessons/02-routing) shows the server side of form handling. Optional, and not needed until Part 05.

## By the end

You should be able to:

- explain what a controlled input is and why React needs both `value` and `onChange`;
- describe what `preventDefault` actually prevents, in terms of FS00.2's request model;
- separate draft state from committed domain data, and say why they must not be the same thing;
- lift state to the right owner and pass changes back through typed callbacks;
- derive validity and empty states instead of storing them;
- choose a component boundary by responsibility rather than by size;
- explain why client-side validation is a usability feature and never a security one.

## Predict before reading

Answer before reading on.

```tsx
<input value={title} />
```

1. What happens when the user types into this input? Why?
2. What is the difference between `value={title}` and `defaultValue={title}`?
3. A form has one text field and a submit button. The user presses Enter in the field. Does the form submit?
4. Should "is this form valid?" be stored with `useState`?

Question 1 catches almost everyone once. Question 3 is the reason to use a real `<form>` element.

## Mental model

Two kinds of data, and the boundary between them:

```text
DRAFT                          COMMITTED
what the user is typing        what the application owns
lives in the form              lives at the owner of the list
changes on every keystroke     changes once, on a valid submit
discarded freely               the source of truth
        │                              ▲
        └──── submit, if valid ────────┘
```

A draft is not a bad or incomplete issue. It is a **different kind of thing**: `{ title: string; priority: Priority }` with no id and no status, because those are not the user's to supply. Modelling it as `Partial<Issue>` blurs a boundary you want sharp — FS02.2's point about optional-versus-absent, arriving in a place where it costs you something real.

The submit is the only moment the two touch. Everything before it is the form's business; everything after it belongs to the owner of the list.

## 1. Controlled inputs

Now the prediction. `<input value={title} />` with no `onChange` renders a field the user **cannot type into**. React re-renders it with `value={title}` after every keystroke, so the character appears and is immediately replaced by the unchanged state. React will warn you in the console.

The fix is to close the loop:

```tsx
const [title, setTitle] = useState('');

<input
  id="issue-title"
  value={title}
  onChange={(event) => setTitle(event.target.value)}
/>
```

Now React state is the source of truth for the input's contents, and the DOM merely displays it. That is what "controlled" means: **the value on screen and the value in state cannot disagree, because there is only one of them.**

`defaultValue` is the alternative — the DOM keeps its own value, React sets only the initial one, and you read it out later. It is fewer lines and it reintroduces exactly the second copy this lesson is about. Prefer controlled inputs; you will need the value during typing anyway, for the character counter or the live validity hint.

For a `<select>`, the same pattern with `value` on the select itself, not `selected` on the options:

```tsx
<select
  id="issue-priority"
  value={priority}
  onChange={(event) => setPriority(event.target.value as Priority)}
>
  {(['low', 'medium', 'high'] as const).map((value) => (
    <option key={value} value={value}>{value}</option>
  ))}
</select>
```

Rendering the options from the same `as const` tuple that produces the union means adding a priority in FS02.2's model adds an option here automatically — and the `as` stays honest, because the only values reachable are the ones just rendered.

## 2. Submission, and what you are switching off

```tsx
function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
  event.preventDefault();
  // ...
}

<form onSubmit={handleSubmit}> … </form>
```

Be precise about what `preventDefault` prevents. From FS00.2: the browser's default for a form submit is to build a request from the fields, send it, and **navigate to the response** — a new document, a fresh page, all client state gone. `preventDefault()` cancels that navigation. It does not cancel the event, stop other handlers, or do anything to the data.

And the answer to question 3 is **yes** — a real `<form>` with a submit button gets Enter-to-submit for free, along with the browser's own required-field handling and the semantics screen readers rely on. That is a lot of behaviour to receive for one element.

This is why you put the handler on the form's `onSubmit` rather than on the button's `onClick`. A button click and an Enter keypress both raise `submit`; only one of them raises `click`. Handling `click` gives you a form that silently ignores the Enter key, which users notice immediately and bug reports describe badly.

## 3. Lifting state, and the callback contract

The form owns the draft. It does not own the issue list. So it cannot add an issue — it can only report that a valid draft is ready:

```tsx
type IssueDraft = { title: string; priority: Priority };

type CreateIssueFormProps = {
  onCreate: (draft: IssueDraft) => void;
};
```

`ProjectPage` receives the draft and turns it into an issue, because it owns `issues` and is therefore the only place that knows what the next id should be and what status a new issue starts in:

```tsx
function handleCreate(draft: IssueDraft) {
  setIssues((current) => [
    ...current,
    { id: `ISS-${current.length + 1}`, status: 'todo', ...draft },
  ]);
}
```

Read the prop type again — it is the whole design. `onCreate` takes an `IssueDraft`, not an `Issue`. The form cannot invent an id even by accident, because the type will not let it. **The component boundary and the type boundary are the same boundary**, and that is what makes this design hold up rather than merely look tidy.

(`ISS-${current.length + 1}` is fine for a local lab and quietly wrong in general — delete two issues and it collides. Part 05 hands id generation to the database, which is where it belongs. Leaving an obvious placeholder is better than building a client-side id scheme you are about to throw away.)

## 4. Derive validity; do not store it

```tsx
const trimmedTitle = title.trim();
const isValid = trimmedTitle.length > 0;

<button type="submit" disabled={!isValid}>Create issue</button>
```

No `useState` for `isValid`. It is a calculation from `title`, so it is recomputed on every render and cannot be stale — FS03.2's rule, applied to the value people most often store by reflex.

Note `trim()`. A title of three spaces is not a title. Decide what you consider empty and apply the same rule in the disabled check and in the submit handler, or the button will enable for input the handler then rejects.

**Disable *and* re-check.** The disabled button is a usability affordance; the check inside `handleSubmit` is what actually protects the list, because form submission can arrive by paths a `disabled` attribute does not cover.

And to be unambiguous, because this becomes load-bearing in Part 06: **none of this is validation in the security sense.** Everything here runs in a browser the user controls entirely. Client-side validation exists to give fast, kind feedback. The server will validate again, from scratch, trusting nothing — that is FS02.5's trust boundary, and no amount of frontend checking moves it.

## 5. Choosing component boundaries

The temptation after four lessons is to split everything. Resist it. Extract a component when one of these is true:

- **It owns a distinct piece of state.** `CreateIssueForm` owns the draft. That is a real boundary.
- **It is genuinely reused.** `IssueRow` renders once per issue.
- **It has a coherent single responsibility you can name** without using "and".

Not because a file got long, and not because a `div` looked lonely. A component extracted without one of those reasons adds a props contract, an indirection, and a place for state to end up in the wrong owner — and it removes nothing.

For this screen the honest split is: `ProjectPage` (owns issues, filters, selection), `CreateIssueForm` (owns the draft), `IssueList` / `IssueRow` (render), `IssueDetail` (render). Five components, each nameable in one phrase.

Conditional and empty states are part of the same design question. With a form present, the empty list now has two distinct meanings — "this project has no issues yet" and "no issues match this filter" — and they deserve different words, because after adding the first issue, seeing "no issues yet" would make the user think the create failed.

## Try it

**Before — predict.** Write down what you think will happen in each of the four steps below *before* doing them.

**Do — three experiments in the running lab** (`npm run dev`):

1. Render `<input value={title} />` with no `onChange`. Try to type.
2. Add the `onChange`. Type again, and log `title` on each render.
3. Remove `event.preventDefault()` from `handleSubmit`, then submit with the Network panel open.
4. Move the handler from the form's `onSubmit` to the button's `onClick`. Type a title and press Enter.

**Observe — record.**

- (1) What appears in the field? What does the console say?
- (3) What request appears in Network? What is its method and URL? What happens to the page and to your issue list?
- (4) Does anything happen on Enter?

**Explain.** Step 3 is FS00.2 arriving in your own code — name the exact default behaviour you had been preventing, and what it cost you. Step 4: why does the click handler miss the Enter key, and what does that tell you about which element the handler belongs on?

## Common mistakes

### `value` without `onChange`

A read-only field and a console warning. If typing does nothing, this is the first thing to check.

### Handling `click` instead of `submit`

Enter stops working. Severity: high, because it is invisible to mouse-driven testing and every keyboard user hits it immediately.

### Forgetting `preventDefault`

The page navigates, React unmounts, all state is lost. Dramatic and easy to diagnose once you have seen it — which is why step 3 above asks you to.

### Storing derived validity

`const [isValid, setIsValid] = useState(false)` plus a hand-written update on every keystroke. It will drift. Derive it.

### Duplicating the draft in the parent

The parent does not need to watch the user type. It needs the finished draft, once. If `ProjectPage` holds a `draftTitle` that mirrors the form's, you have two copies of one fact — the exact bug FS03.2 warned about.

### Modelling the draft as `Partial<Issue>`

It compiles and it hides the boundary. A draft has no id and no status, and `Partial<Issue>` says both are optional rather than *absent by design*. Give the draft its own type.

### Resetting the draft before the parent accepts it

Clear the fields after `onCreate` returns, not before you have validated. Clearing first means a rejected submit silently eats the user's typing.

### Trusting the client

Covered above, and worth repeating because it is the one with real consequences. A `disabled` button protects nobody.

## When this goes wrong

1. **Cannot type.** Missing `onChange`, or `value` bound to something that never changes.
2. **The page reloads on submit.** Missing `preventDefault`.
3. **Enter does nothing.** Handler on `onClick`, or the button is missing `type="submit"`.
4. **A stray button submits the form.** Buttons inside a form default to `type="submit"` — set `type="button"` on anything that is not the submit.
5. **The list updates as you type.** The draft and the committed data are the same state. Separate them.
6. **The draft clears on an invalid submit.** The reset is running before validation.
7. **`event.target.value` is typed `string` when you wanted a union.** Expected: the DOM types it that way. Keep the options and the union in one place.

## Exercise

### Goal

Build a local Create Issue flow with a clean draft/committed boundary.

### Starting state

Your lab copy with FS03.2's exercise complete: filters, selection and "Mark done" all working, `npm test` passing.

### Requirements

1. Define `IssueDraft` as its own type — title and priority only. Not `Partial<Issue>`.
2. Build `CreateIssueForm` as a real `<form>` with an `onSubmit` handler that calls `preventDefault()`.
3. Give it a controlled text input and a controlled priority select. Every field has a visible `<label>` associated by `htmlFor` / `id`.
4. Derive validity from the trimmed title. Disable the submit button when invalid, **and** re-check inside the handler.
5. Type `onCreate: (draft: IssueDraft) => void` and pass it from `ProjectPage`, which assigns the id and the starting status.
6. Reset the draft only after a successful create.
7. Give the empty list two distinct messages — no issues at all, versus none matching the current filters — and derive which one shows.
8. Add two tests: submitting a whitespace-only title adds no row; submitting a valid title adds exactly one.

### Constraints

- No `useEffect`.
- No validation library, no form library. Five lines of hand-written checking is the lesson.
- Do not store the draft anywhere but the form.
- Do not store validity or the empty-state choice — derive both.
- No `any`. The `as Priority` in the select handler is permitted only while the options are rendered from the same union.

### Verification

**Mode: tool-run plus manual proof.** Nothing here is automatically graded.

- `npm run typecheck` clean; `npm test` passes with both new tests.
- Submitting a blank or whitespace-only title leaves the list unchanged and the button disabled.
- Submitting a valid title adds exactly one row, with no page reload — confirm in the Network panel that no document request occurred.
- Pressing Enter in the title field submits.
- The new issue appears with status `todo` and the chosen priority, and is affected by the filters like any other.
- After a successful create the fields are empty; after a rejected one they are not.
- Prove the boundary: try calling `onCreate({ id: 'ISS-9', title: 'x', priority: 'low' })` from the form and confirm the compiler rejects the extra property.

### Hints

<details>
<summary>Hint 1 — what should <code>onCreate</code> receive?</summary>

An `IssueDraft`. The parent owns the list, so the parent decides the id and the starting status. If the form could supply an id, the boundary is not real.
</details>

<details>
<summary>Hint 2 — why a real <code>&lt;form&gt;</code> rather than a div and a button?</summary>

Enter-to-submit, the `submit` event, and the semantics assistive technology depends on. Handle its `onSubmit`; do not rebuild any of that by hand.
</details>

<details>
<summary>Hint 3 — the two empty states</summary>

They differ by a condition you already have. `issues.length === 0` is one thing; `issues.length > 0 && visibleIssues.length === 0` is the other. Neither needs state.
</details>

<details>
<summary>Hint 4 — testing the whitespace case</summary>

Render, type `'   '` into the field, submit, and assert the row count is unchanged. `@testing-library/user-event` is already installed and types more realistically than firing a change event directly.
</details>

<details>
<summary>Reference explanation — read after an honest attempt</summary>

`CreateIssueForm` owns exactly two pieces of state — the title and the priority — and nothing else. `isValid` and both empty-state messages are derived. `ProjectPage` owns `issues` and receives a draft.

The transferable idea is that **the component boundary and the type boundary are the same line.** `onCreate: (draft: IssueDraft) => void` makes the design enforceable rather than merely intended: the form is structurally unable to invent an id, so no amount of later editing can quietly move that responsibility.

Everything else follows. Because the draft lives only in the form, the list cannot update as you type. Because validity is derived, it cannot go stale. Because the parent assigns the id, Part 05 can move that job to the database by changing one function — and nothing in the form needs to know.
</details>

## In the project

This is the create flow of **B03**, and it is the last piece of the local issue tracker. After FS03.4 makes it usable, Part 04 replaces `handleCreate`'s array push with a request to a server — and the draft/committed boundary you built here is exactly the seam that change goes through.

The pattern also recurs at every later layer. Part 05's DALT validation and Part 06's authorization are the same question — *who is allowed to decide this?* — asked where the answer actually matters, because the server does not trust the form and never will.

## Closed-book checkpoint

Close the lesson and the lab.

1. What makes an input "controlled", and what breaks if you supply `value` without `onChange`?
2. In FS00.2's terms, what exactly does `preventDefault()` prevent?
3. Why put the handler on the form's `onSubmit` rather than the button's `onClick`?
4. Why is a draft a different type from an issue rather than a partial one?
5. Which component assigns a new issue's id, and why not the form?
6. Name two values on this screen that look like state and are not.
7. Why is a disabled submit button not a security control?
8. Give one reason to extract a component and one bad reason.

Then reopen and correct your answers in a different colour.

## Resources

### Read

- [MDN: Your first form](https://developer.mozilla.org/en-US/docs/Learn_web_development/Extensions/Forms/Your_first_form) — what you get from a real `<form>` before React is involved.
- [React: Reacting to Input with State](https://react.dev/learn/reacting-to-input-with-state)
- [React: Sharing State Between Components](https://react.dev/learn/sharing-state-between-components) — lifting state up.

### Go deeper

- [React: Choosing the State Structure](https://react.dev/learn/choosing-the-state-structure) — "Avoid redundant state", read again with a form in mind.
- [React: `<input>`](https://react.dev/reference/react-dom/components/input) — the controlled-versus-uncontrolled section.

### Reference

- [MDN: `preventDefault`](https://developer.mozilla.org/en-US/docs/Web/API/Event/preventDefault)
- [Testing Library: `user-event`](https://testing-library.com/docs/user-event/intro)

## You are done when

- [ ] A valid submit adds exactly one issue, with no page reload.
- [ ] A whitespace-only title is rejected by both the disabled button and the handler.
- [ ] Enter in the title field submits the form.
- [ ] `IssueDraft` is its own type and the form cannot supply an id — I proved it with the compiler.
- [ ] Validity and both empty-state messages are derived, not stored.
- [ ] I removed `preventDefault` once, watched the navigation in the Network panel, and put it back.
- [ ] Every field has a visible associated label.
- [ ] `npm run typecheck` and `npm test` pass, including both new tests.
- [ ] I attempted the closed-book checkpoint without notes.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_01.md`; `docs/dalt-fullstack/sources/REACT_DOCS.md`
- Official sources: React Learn — Reacting to Input with State, Sharing State Between Components, Choosing the State Structure; React DOM Reference — `<input>`, `<select>`; MDN — Your first form, `preventDefault`
- Versions: React 19.2.3, TypeScript 5.9.3, Vite 8.0.12, Vitest 4.0.18, `@testing-library/user-event` 14.6.1 (CR-08 pinned toolchain)
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/course/fullstack/react-foundations-lab/starter/**`, `.dalt/course/lessons/21-fs00-2-forms-json-and-spa/README.md`
- Curriculum authority: `CURRICULUM.md` §13 FS03.3
- Laravel bridge: deferred to Part 05, where server-side validation gives the comparison something to compare
