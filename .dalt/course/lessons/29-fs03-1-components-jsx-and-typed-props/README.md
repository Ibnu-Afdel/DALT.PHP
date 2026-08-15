# FS03.1 — Components, JSX and typed props

Lesson ID: FS03.1  
Title: Components, JSX and typed props  
Part: 03 — React foundations  
Order: 1  
Status: Published  
Estimated effort: 75–100 minutes  
Difficulty: Applied  
Prerequisites: FS02.5 — Runtime boundaries  
Project milestone: B03 — The local issue tracker  
Primary source dossier: `FSO_PART_01.md`; `REACT_DOCS.md`  
Last reviewed: 2026-08-14

## Why this matters

Everything you have built so far ran once and finished. A script transforms data, prints, exits. A screen does not work like that. It has to keep matching data that changes — a filter is applied, an issue is marked done, a new one is created — and it has to do that without you writing instructions for each individual change.

The obvious approach is to write those instructions anyway: find the list element, append a row, update the counter, hide the empty message. That approach is what makes interfaces rot. Every new feature adds another place where the screen and the data can silently disagree, and the bug you get is the worst kind — the data is correct, and the screen is lying about it.

React's answer is narrow and worth stating precisely: **you write a function from data to a description of the UI, and React works out which browser changes are needed.** You stop maintaining the screen and start maintaining the function. That is the whole idea, and this lesson is about getting it exactly right before state arrives in FS03.2 and starts making it feel complicated.

## Before you start

Required:

- FS02.5 — Runtime boundaries.
- Node and npm available on your machine (`node --version`).

Recommended first:

- Revisit FS02.2's `Issue` model — the same type is used here.
- Revisit FS02.4's thinking about function boundaries. A component is a function, and the question "what should this take as arguments?" is the same question.

Going deeper in DALT Core — optional:

- None. This is the start of the React application, not a detour through Core.

## By the end

You should be able to:

- explain what React is actually doing when it renders, without using the word "update";
- read a JSX expression and say which parts are markup and which are JavaScript;
- decide what belongs in a component's props and what does not;
- type a props contract so a wrong call is a compiler error, not a broken screen;
- render a list of domain data with keys that survive filtering and reordering;
- explain why `key` is not a prop;
- distinguish a real empty state from accidentally hiding broken data.

## Predict before reading

Write answers down before you read on. The comparison is the exercise.

Given `issues` with two entries:

```tsx
{issues.map((issue) => <IssueRow key={issue.id} issue={issue} />)}
```

1. What does this produce — two elements, two DOM nodes, or two function calls?
2. Can `IssueRow` read `key` as `props.key` and display it?
3. If `issues` were `[]`, what appears on screen?
4. If you changed `key={issue.id}` to `key={index}` and then filtered the list, would anything visibly break?

Question 4 is the one worth being wrong about. Most people predict "nothing", and on this small example they are almost right, which is exactly why the mistake survives into real code.

## Mental model

```text
typed local data
        ↓
component functions run          ← "render"
        ↓
JSX describes the UI for that data
        ↓
React compares it with the previous description
        ↓
React commits only the browser changes needed
```

Three things follow, and they are the whole lesson:

**Render is a description, not a command.** When your component returns JSX, nothing has touched the DOM yet. You have produced a value describing what the UI should look like. React decides what to do with it. This is why you never write "add a row" — you write "here is what the list looks like now" and let React find the difference.

**Render is a function call.** A component is an ordinary JavaScript function. React calls it. When the data changes, React calls it again. There is no persistent object holding your component's insides — a point that becomes load-bearing in FS03.2.

**The data flows one way.** A parent passes props to a child. The child cannot reach back and change them. This feels restrictive for about a day, and then it becomes the reason you can look at a wrong value on screen and trace it upward to exactly one owner.

## 1. A component is a function with a capital letter

```tsx
function IssueRow() {
  return <li>Nothing useful yet</li>;
}
```

That is a complete component. Two rules make it one: the name starts with a capital letter, and it returns JSX. The capital letter is not style — it is how JSX distinguishes your component from an HTML tag. `<issueRow />` compiles to a request for an HTML element named `issuerow`, which does not exist, and you get a silently empty screen rather than an error.

JSX is not HTML and not a template language. It is JavaScript syntax that compiles to function calls. `<li>Hello</li>` becomes something equivalent to `createElement('li', null, 'Hello')`. Knowing this explains most JSX rules that otherwise look arbitrary:

- **It is an expression**, so it can be returned, stored in a variable, or put in an array.
- **It must have one root**, because a function call returns one value. Use `<>…</>` when you do not want a wrapper element.
- **Attributes are JavaScript object keys**, so `class` — a reserved word — is `className`, and `for` is `htmlFor`.

Curly braces switch from markup back into JavaScript, and what goes inside must be an *expression* — something that produces a value:

```tsx
<li>{issue.id}: {issue.title.toUpperCase()}</li>
```

An `if` statement is not an expression, which is why conditional rendering uses `?:` and `&&` rather than `if`. You can still use `if` in the function body, before the `return`.

## 2. Props are the component's declared inputs

A component that reaches out for data it was not given is a component you cannot reuse, cannot test, and cannot reason about locally. Props are how a component states its dependencies out loud.

```tsx
type Issue = {
  id: string;
  title: string;
  status: 'todo' | 'in_progress' | 'done';
  priority: 'low' | 'medium' | 'high';
};

type IssueRowProps = { issue: Issue };

function IssueRow({ issue }: IssueRowProps) {
  return (
    <li>
      <strong>{issue.id}</strong> {issue.title}
      <span>{issue.status}</span>
    </li>
  );
}
```

React passes one argument — an object holding all the props — so `{ issue }` is ordinary destructuring, and `IssueRowProps` is an ordinary type alias. Nothing here is React-specific except where the object comes from. That is the point: your FS02 modeling skills transfer directly.

The type is doing real work. `<IssueRow issue={{ id: 'ISS-1', title: 'x', status: 'urgent', priority: 'high' }} />` is a compile error, because `'urgent'` is not in the finite `IssueStatus` union. Without the type you get a row rendering the word "urgent" and no indication anything is wrong.

**Keep FS02.5's boundary in mind.** The compiler proves the *call sites in your source* agree. It proves nothing about a value that arrives from a server at runtime. Typed props are a contract between your components, not a validation layer. Part 04 is where that distinction gets expensive.

### Choosing what goes in props

The useful question is not "what does this component need to display?" It is **"what does this component have no business deciding?"**

`IssueRow` should not decide *which* issue to show — that is the list's job. It should not fetch, filter, or sort. Give it the issue and let it render the issue. A component whose props are `{ issue }` can be dropped into a search result, a detail panel, or a test with a hand-written fixture.

## 3. Composition and `children`

Small components combine into a screen. When a wrapper owns a shared *structural* boundary rather than a specific piece of data, it accepts `children` — whatever JSX is nested between its tags:

```tsx
type PanelProps = { title: string; children: React.ReactNode };

function Panel({ title, children }: PanelProps) {
  return (
    <section aria-labelledby="issues-heading">
      <h2 id="issues-heading">{title}</h2>
      {children}
    </section>
  );
}
```

`React.ReactNode` is the type for "anything React can render" — elements, strings, numbers, arrays, `null`. Use `children` when the wrapper genuinely does not care what is inside it. If it *does* care — if it needs a list specifically — take a typed prop instead and keep the contract honest.

Resist building a `Panel` before you have two things that need one. Extracting a component that has exactly one caller adds a layer and removes nothing.

## 4. Conditional rendering, and the empty state trap

Conditionals in JSX are ordinary JavaScript decisions:

```tsx
{issues.length === 0 ? (
  <p>No issues match this view.</p>
) : (
  <ul>{issues.map((issue) => <IssueRow key={issue.id} issue={issue} />)}</ul>
)}
```

A real empty state says *why* the list is empty. "No issues match this view" is different information from "This project has no issues yet", and a learner who writes only the second will confuse themselves the first time a filter hides everything.

Two traps worth naming now:

**`&&` with a number.** `{issues.length && <ul>…</ul>}` renders a literal `0` on screen when the list is empty, because `0` is falsy but is still a renderable value. Compare explicitly: `issues.length > 0 && …`.

**Hiding instead of fixing.** `{issue.title && <h2>{issue.title}</h2>}` looks defensive and is often a bug in disguise. If `title` is required by your model and it is missing, the screen quietly omits a heading and you never find out. Guard values that are genuinely optional; let required values that go missing be visible.

## 5. Lists and keys

```tsx
<ul>{issues.map((issue) => <IssueRow key={issue.id} issue={issue} />)}</ul>
```

Now the prediction. `.map` produces an **array of element descriptions** — two function calls have not happened yet; React calls `IssueRow` when it renders them. So: two elements, and *later* two `<li>` DOM nodes.

`key` is **not a prop.** React strips it and uses it internally; `IssueRow` cannot read `props.key`. If you need the id inside the row, pass it again as part of `issue`.

A key answers one question: *"across two renders, which of these is the same logical item?"* React uses the answer to decide what to reuse and what to throw away.

That is why `key={index}` is wrong here even though it looks fine. An index describes a *position*, not an item. Filter the list and the issue at position 0 becomes a different issue — but React, told the key is still `0`, concludes it is the same item and reuses its DOM node and any state inside it. On a list of plain text rows you see nothing. Add a checkbox or an expanded/collapsed row in FS03.2 and you get the classic bug: you tick a box, apply a filter, and the tick has moved to a different issue.

Use `issue.id`. It is stable, it is unique, and it means the same thing on every render. Index keys are defensible only for a list that is static and never reordered, filtered, or added to — which the issue list is not.

## Try it

**Before — predict.** In the lab's `src/IssueList.tsx`, the list and the row markup live in one component. Before running anything, write down: how many times does `IssueList` run when the page first loads, and how many times does the row markup get evaluated?

**Do — run the lab.** Part 03 uses one course-owned lab across all four lessons. It is not B03 and not your future application.

```sh
mkdir -p .dalt/workspace
cp -R .dalt/course/fullstack/react-foundations-lab/starter .dalt/workspace/fs03-react-foundations
cd .dalt/workspace/fs03-react-foundations
npm ci
npm run typecheck
npm test
npm run dev
```

`typecheck` and `test` must both pass on the untouched starter. If either fails before you have changed anything, the lab is broken — report it rather than debugging it. `npm run dev` serves the screen on `http://localhost:5174`; leave it running, it reloads as you edit.

**Observe — record four things.**

1. Add `console.log('IssueList render')` at the top of `IssueList`. Reload. Note how many times it prints, and that it prints on *every* edit.
2. In `src/issue.ts`, change one title. Watch the browser update without a reload, then run `npm test` and read which assertion moved.
3. Pass an invalid value: temporarily add `priority: 'urgent'` to one issue. Run `npm run typecheck`. Record the exact message and *which file* it points at. Restore the valid value.
4. Change `key={issue.id}` to `key={index}` (you will need `(issue, index)` in the `map`). Note that the screen looks identical.

**Explain.** For (3): the compiler pointed at `issue.ts`, not at the component — why is that the more useful place for it to complain? For (4): the screen is unchanged, so what exactly did you break, and what would have to be added to the row before you could see it?

## Common mistakes

### Lowercase component names

`<issueRow />` is treated as an HTML tag. You get no error and no output. If a component renders nothing at all, check the capital letter first.

### Calling the component instead of describing it

`{IssueRow({ issue })}` runs the function immediately and inlines the result. It often looks right and then behaves strangely, because React never learns there is a component there — in FS03.2 it will have nowhere to attach state. Write `<IssueRow issue={issue} />`.

### Index keys on a list that changes

Covered above. The severity is high because it is invisible until state exists, and by then the cause is several lessons behind you.

### Passing the whole world as props

`<IssueRow issue={issue} allIssues={issues} filters={filters} onEverything={...} />`. When a props list grows like this, the component has been given decisions that belong to its parent. Ask what it has no business deciding.

### Treating a typed prop as runtime validation

`IssueRowProps` disappears at runtime — FS02.5's point, unchanged. It constrains your source, not a server's response.

## When this goes wrong

1. **Nothing renders.** Check the capital letter, then check that the component actually `return`s (an arrow function with `{}` and no `return` gives `undefined`).
2. **`Objects are not valid as a React child`.** You interpolated an object where a string was expected — usually `{issue}` instead of `{issue.title}`.
3. **A stray `0` on screen.** The `&&`-with-a-number trap.
4. **`Each child in a list should have a unique "key" prop`.** You mapped without a key, or two items share one.
5. **The compiler is quiet but the screen is wrong.** The types agree with each other and disagree with reality. Log the actual value; TypeScript cannot help you here.

## Exercise

### Goal

Describe one project screen as a small set of components with honest prop contracts, from typed local data.

### Starting state

The lab at `.dalt/workspace/fs03-react-foundations`, with `npm run dev` running and `npm test` passing.

### Requirements

Work in `src/`:

1. Split the current `IssueList` into `IssueRow` (renders one issue) and `IssueList` (owns the `.map`). Give each an explicit props type.
2. Add `ProjectPage`, taking `workspaceName` and `projectName` as props and rendering them as a heading above the list. Render it from `App`.
3. Render all three issues with `issue.id` as the key.
4. Add an empty state shown only when the passed list is empty, whose wording says the list is empty *for this view*.
5. Extend `src/IssueList.test.tsx` with a test that the empty state appears for `[]` and does not appear for the three issues.

### Constraints

- No `useState` — that is FS03.2. All data stays in `src/issue.ts`.
- No `any`, no `as`. If a type fights you, the model is wrong.
- No component with exactly one caller that exists only to add a layer.
- Do not create anything under `resources/app/`. B03 does not exist yet.

### Verification

**Mode: tool-run plus manual proof.** Nothing here is automatically graded.

- `npm run typecheck` exits clean.
- `npm test` passes, including your new empty-state test.
- In the browser: three rows, with the workspace and project names above them.
- Set `issues` to `[]` in `src/issue.ts` — the empty state appears and no empty `<ul>` is left behind. Restore it.
- Predict, then confirm: give `ProjectPage` a `projectName={42}` and check that `npm run typecheck` rejects it before the browser ever renders.

### Hints

<details>
<summary>Hint 1 — where does the <code>.map</code> belong?</summary>

In `IssueList`. `ProjectPage` decides *which* list it owns; `IssueRow` knows about exactly one issue and nothing about the collection.
</details>

<details>
<summary>Hint 2 — what should a key identify?</summary>

The same logical issue on the next render — not its current position in the array. Ask what stays true after a filter is applied.
</details>

<details>
<summary>Hint 3 — where should the empty-state decision live?</summary>

Near whoever knows the list is empty. `IssueRow` never sees the collection, so it cannot be there.
</details>

<details>
<summary>Reference explanation — read after an honest attempt</summary>

`ProjectPage` owns the heading and passes the array down. `IssueList` decides between the empty state and the `<ul>`, and maps to rows. `IssueRow` receives one `Issue` and renders it.

The proof that this is right is not that it looks the same on screen — it is the direction of the data. Every value a component displays arrived through its props, from exactly one owner. When a wrong value appears in FS03.2, that direction is what lets you walk upward to the single place responsible.

Keys use `issue.id`. The empty-state wording refers to the current view rather than the project, because from FS03.2 onward a filter will be able to empty the list without the project being empty.
</details>

## In the project

This is the first code of **B03 — The local issue tracker**, but B03 is not started here and no project scaffold is created. What carries forward is the component boundary: `ProjectPage` → `IssueList` → `IssueRow`, with typed props at each seam.

FS03.2 adds state to the same three components. FS03.3 adds a form beside them. FS03.4 makes them semantic and responsive. If the boundaries are wrong now, each of those lessons makes the mistake more expensive — which is why this lesson spends its time on prop contracts rather than on getting more pixels on screen.

## Closed-book checkpoint

Close the lesson and the lab first.

1. What does a component return, and what does React do with it?
2. Why must a component name start with a capital letter?
3. What is the difference between `{IssueRow({ issue })}` and `<IssueRow issue={issue} />`?
4. Why is `key` not readable as `props.key`?
5. An index key and an id key render identically today. Describe a concrete change that makes them behave differently.
6. What does a typed props contract prove, and what does it not prove?
7. Which component should own an empty-state decision, and why not the row?

Then reopen the lesson and correct your answers in a different colour.

## Resources

### Read

- [React: Your First Component](https://react.dev/learn/your-first-component) — through "Nesting and organizing components".
- [React: Passing Props to a Component](https://react.dev/learn/passing-props-to-a-component) — including the `children` section.
- [React: Rendering Lists](https://react.dev/learn/rendering-lists) — read "Why does React need keys?" carefully.

### Go deeper

- [React: Conditional Rendering](https://react.dev/learn/conditional-rendering) — the logical-AND pitfalls section.
- [React TypeScript: typing props](https://react.dev/learn/typescript#typescript-with-react-components).

### Reference

- [React: Writing Markup with JSX](https://react.dev/learn/writing-markup-with-jsx) — the three JSX rules, when a syntax error is puzzling.

## You are done when

- [ ] `npm run typecheck` and `npm test` both pass in my lab copy.
- [ ] The screen renders a workspace name, a project name, and three issue rows from typed local data.
- [ ] `IssueRow`, `IssueList` and `ProjectPage` each have an explicit props type and no `any`.
- [ ] I saw the compiler reject an invalid `priority` and named the file it pointed at.
- [ ] I can explain what an index key would break, using a concrete example.
- [ ] My empty state has its own test and says why the list is empty.
- [ ] I attempted the closed-book checkpoint without notes.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_01.md`; `docs/dalt-fullstack/sources/REACT_DOCS.md`
- Official sources: React Learn — Your First Component, Passing Props, Rendering Lists, Conditional Rendering, Writing Markup with JSX; React TypeScript guidance
- Versions: React 19.2.3, TypeScript 5.9.3, Vite 8.0.12, Vitest 4.0.18 (CR-08 pinned toolchain)
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/course/fullstack/react-foundations-lab/starter/**`, `.dalt/course/lessons/25-fs02-2-modeling-application-data/README.md`
- Curriculum authority: `CURRICULUM.md` §13 FS03.1; CR-02 places lists and keys in FS03.1
- Laravel bridge: not applicable — no DALT or Laravel primitive corresponds to client-side rendering
