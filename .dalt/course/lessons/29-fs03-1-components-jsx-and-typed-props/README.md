# FS03.1 — Components, JSX and typed props

Lesson ID: FS03.1  
Part: 03 — React foundations  
Order: 1  
Status: Published  
Estimated effort: 60–90 minutes  
Difficulty: Applied  
Prerequisites: FS02.5 — Runtime boundaries  
Project milestone: B03 — The local issue tracker  
Primary source dossier: FSO_PART_01.md; REACT_DOCS.md  
Last reviewed: 2026-08-14

## Before you start

Required:

- FS02.5 — Runtime boundaries.

Recommended first:

- Revisit FS02.2’s `Issue` model and FS02.4’s component-sized function boundaries.

Going deeper in DALT Core — optional:

- None. This is the start of the React application, not a detour through Core.

## Why a component boundary matters

The issue tracker will show one project and many issues. If one large function owns every heading, row, empty state, and future interaction, a small change makes unrelated code hard to see. React gives us a way to describe the screen as small functions that receive data and return UI.

That is not a new runtime model for data. The browser still receives JavaScript values; TypeScript still disappears before runtime. React's job here is narrower: from the current data, describe what the UI should look like.

By the end, you can:

- explain why a React component is a function used to describe UI;
- predict which JSX expressions render values and which need a component;
- model a typed `IssueRow` prop boundary;
- render typed local issue data with stable keys;
- distinguish a conditional empty state from hiding broken data.

## Predict before reading

What does this render when `issues` has two entries? Which value is a stable key?

```tsx
{issues.map((issue) => <IssueRow key={issue.id} issue={issue} />)}
```

Do not read ahead until you have an answer. In particular, ask whether `key` is an ordinary prop that `IssueRow` can display.

## Render is a description, not a command list

Use this model throughout Part 03:

```text
typed local data
        ↓
component functions run
        ↓
JSX describes the current UI
        ↓
React commits the necessary browser changes
```

A component is a JavaScript function whose name starts with a capital letter and whose return value is JSX. JSX looks HTML-like, but it is JavaScript syntax for describing elements. Curly braces enter JavaScript expressions:

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

`IssueRow` does not fetch data, edit a database, or secretly know which issue to display. Its prop contract makes the dependency visible. That makes it reusable and gives TypeScript a useful boundary to check.

## Composition, children, and conditions

Small components compose into a screen. A `Panel` can accept `children`: the nested JSX placed between its opening and closing tags. Use this when the wrapper owns a shared semantic boundary, not simply to avoid naming a prop.

```tsx
type PanelProps = { title: string; children: React.ReactNode };

function Panel({ title, children }: PanelProps) {
  return <section aria-labelledby="issues-heading"><h2 id="issues-heading">{title}</h2>{children}</section>;
}
```

React conditionals are ordinary JavaScript decisions. Make the visible state honest:

```tsx
{issues.length === 0 ? (
  <p>No issues match this view.</p>
) : (
  <ul>{issues.map((issue) => <IssueRow key={issue.id} issue={issue} />)}</ul>
)}
```

Now reveal the prediction: two `IssueRow` elements are described. `issue.id` identifies a logical item across renders; React uses `key` during reconciliation and does not pass it as `props.key`. Do not use an array index when the issue has its own stable identity: filtering or reordering would make the index describe position rather than the issue.

## Run a bounded React experiment

Part 03 uses one course-owned lab, not the learner's future application and not B03. Copy it once; its test proves rendering and its typecheck proves the TypeScript boundary:

```sh
mkdir -p .dalt/workspace
cp -R .dalt/course/fullstack/react-foundations-lab/starter .dalt/workspace/fs03-react-foundations
cd .dalt/workspace/fs03-react-foundations
npm ci
npm run typecheck
npm test
```

Read `src/IssueList.tsx` first. Change one title, rerun the test, then deliberately pass an object with `priority: 'urgent'` and run `npm run typecheck`. Restore the valid finite value afterwards. The test confirms DOM output; the compiler confirms the declared model. Neither is a server or persistence test.

## Focused exercise — Describe one project screen

**Mode: tool-run + manual proof. No B03 project scaffold is created in this lesson.** In the resettable lab, write the following from typed local data:

1. `Issue`, `IssueRowProps`, `IssueRow`, and `IssueList`.
2. A `ProjectPage` that displays a workspace and project name through props.
3. A list with three issues, using `issue.id` as the key.
4. A conditional empty state that is shown only when the passed list is empty.

Before running it, predict whether a component receiving `{ title: string }` can be called with `{ title: 42 }`. Then use your editor or `npx tsc --noEmit` in a React+TypeScript scratch project to confirm that the mismatch is caught.

Verification evidence: the screen renders three rows; changing the local array to `[]` renders the empty message; changing `key={issue.id}` to `key={index}` leaves the screen looking similar but you can explain why it is no longer a stable identity.

### Hints

<details><summary>Hint 1 — where should the array map?</summary>
Put it in `IssueList`. `ProjectPage` decides which list it owns; `IssueRow` knows only one issue.</details>

<details><summary>Hint 2 — what should a key identify?</summary>
The same logical issue on the next render, not its current position in an array.</details>

<details><summary>Answer and reasoning</summary>
One parent owns the local array, a list maps it to rows, and each row receives an `Issue`. The important proof is the direction of data: parent to child through typed props.</details>

## Closed-book checkpoint

Without reopening the lesson, answer:

1. What is React describing during render?
2. Why is a component prop type useful even though TypeScript disappears at runtime?
3. Why is an issue ID a better key than an array index?
4. Where should an empty-state decision live: in an individual row or near the list?

## Next

FS03.2 adds state and events. The screen will still use local data, but an event can request a new render from new state.
