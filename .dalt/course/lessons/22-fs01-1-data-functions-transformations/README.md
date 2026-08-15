# FS01.1 — Data, functions and transformations

Lesson ID: FS01.1  
Title: Data, functions and transformations  
Part: 01 — Modern JavaScript  
Order: 1  
Status: Published  
Estimated effort: 60–90 minutes  
Difficulty: Foundation  
Prerequisites: FS00.1, FS00.2  
Project milestone: B01 — JavaScript readiness  
Primary source dossier: FSO_PART_01.md  
Last reviewed: 2026-08-14

## Why this matters

Part 00 established that browser JavaScript can participate in application behavior. Here, focus on the language mechanisms that application code uses to shape data before it reaches a screen or changes state.

You already know how to program. This is a focused refresher for the JavaScript patterns that will repeatedly appear in TypeScript and, later, React: issue lists, callback functions, derived values, and deliberate updates.

One of them matters more than the rest. React decides whether to re-render by comparing references, so "did I change this array or produce a new one?" stops being a style question and becomes the difference between a screen that updates and one that silently does not. That is why this lesson spends its time on references and immutable updates rather than on syntax you could look up.

## Before you start

Required:

- FS00.1 and FS00.2.
- Node available (`node --version`), or a browser console — every snippet here runs in either.

Recommended first:

- Keep a scratch file or console open. This lesson is prediction-driven and the predictions are worthless unless you write them down before running anything.

Going deeper in DALT Core — optional:

- None.

## By the end

You should be able to:

- explain why objects and arrays can be shared through references;
- use functions as stored, passed, and returned values;
- use destructuring, spread, and rest without assuming spread deep-copies data;
- choose `map`, `filter`, `find`, `some`, `every`, or `reduce` by the result you need;
- create an updated array or object without mutating the value you started with.

## Predict before reading

Write your predictions first. Run each snippet only after committing to an answer.

### 1. Shared object reference

```js
const issue = { title: 'Broken search', status: 'open' };
const selectedIssue = issue;

selectedIssue.status = 'closed';

console.log(issue.status);
console.log(issue === selectedIssue);
```

What are the two outputs? More importantly: how can changing `selectedIssue` affect `issue` when neither binding was reassigned?

### 2. Is this really a copy?

```js
const project = {
  name: 'Atlas',
  settings: { notifications: true },
};
const nextProject = { ...project };

nextProject.settings.notifications = false;

console.log(project.settings.notifications);
console.log(project.settings === nextProject.settings);
```

Predict both outputs. Do not call this a deep copy until you have tested the nested value.

### 3. Passing a callback, or calling it?

```js
const announce = () => console.log('Issue updated');

const later = (work) => {
  console.log('before');
  work();
};

later(announce);
// What changes if this becomes later(announce())?
```

Which line calls `announce` in the working version? In the changed version, what is passed into `later`?

### 4. Transformation prediction

```js
const issues = [
  { id: 1, status: 'open', title: 'Broken search' },
  { id: 2, status: 'closed', title: 'Update docs' },
];

const result = issues
  .filter((issue) => issue.status === 'open')
  .map((issue) => issue.title);

console.log(result);
```

Predict the value and shape of `result`. Which callback chooses items, and which one changes their shape?

### 5. An update without changing the input

```js
const issues = [
  { id: 1, status: 'open' },
  { id: 2, status: 'open' },
];

const nextIssues = issues.map((issue) =>
  issue.id === 2 ? { ...issue, status: 'closed' } : issue,
);

console.log(issues[1].status, nextIssues[1].status);
console.log(issues === nextIssues, issues[1] === nextIssues[1]);
```

Predict all four values. Why is it useful that the changed issue and the containing array have new identities?

## Mental model

Two ideas carry the whole lesson.

**A binding is a name pointing at a value. For objects and arrays, the value is a reference.**

```text
const a = { title: 'Broken search' }
const b = a                     ← b points at the SAME object
b.title = 'Fixed'               ← a.title is now 'Fixed' too

const c = { ...a }              ← c points at a NEW object
c.title = 'Something else'      ← a is untouched
```

`const` prevents the *name* being re-pointed. It says nothing about the object it points at.

**A transformation produces the next value; it does not edit the current one.**

```text
current value  ──transformation──▶  next value
     (unchanged)                      (new reference)
```

`map`, `filter` and spread work this way. `push`, `sort` and direct property assignment do not. Both styles are valid JavaScript; only one survives contact with React, which is why the habit is worth building now, four lessons before you need it.

## Values, bindings, and references

Use `const` for a binding you will not reassign. Use `let` when you genuinely will reassign the binding. Neither keyword makes an object immutable.

```js
const issue = { status: 'open' };
issue.status = 'closed';       // allowed: the object changed

// issue = { status: 'open' }; // not allowed: the const binding cannot point elsewhere
```

Primitive values such as strings, numbers, and booleans behave like values you can replace. Objects and arrays are reference values: a variable holds a reference to an object or array elsewhere. Assigning that reference does not make another object.

```text
issue ───────────┐
selectedIssue ───┴──→ { status: 'open' }
```

That is why prediction 1 prints `closed` and `true`. It is not spooky shared state; both bindings refer to the same object.

### Copying deliberately, and the shallow boundary

Spread makes a new outer object or array.

```js
const issue = { id: 7, status: 'open' };
const nextIssue = { ...issue, status: 'closed' };

const queue = [issue];
const nextQueue = [...queue, nextIssue];
```

This is a **shallow** copy. The outer container is new; nested objects and arrays are still the same references unless you also copy the branch you are changing.

```js
const project = { name: 'Atlas', settings: { notifications: true } };
const nextProject = {
  ...project,
  settings: { ...project.settings, notifications: false },
};

console.log(project.settings.notifications);     // true
console.log(project.settings === nextProject.settings); // false
```

Do not reach for deep cloning as a habit. Copy the specific path whose next value differs. That keeps the transformation clear and preserves unrelated values.

## Functions are values

A function can be stored, passed, returned, and called later. Function declarations are useful when a named function reads best; arrow functions are compact when a function is a value in an expression.

```js
function formatIssue(issue) {
  return `#${issue.id} ${issue.title}`;
}

const formatTitle = (issue) => issue.title.toUpperCase();
const applyToIssue = (issue, formatter) => formatter(issue);

console.log(applyToIssue({ id: 4, title: 'Broken search' }, formatIssue));
console.log(applyToIssue({ id: 4, title: 'Broken search' }, formatTitle));
```

`formatter` is a callback: `applyToIssue` receives it now and calls it later. `formatIssue` passes the function value. `formatIssue()` calls it immediately, so it passes the return value instead. That distinction matters for array methods now and for event handlers later.

### A practical closure

When a function is created, it retains access to its lexical environment. It can run later and still use values from that surrounding scope.

```js
const makeStatusFilter = (status) => {
  return (issue) => issue.status === status;
};

const isOpen = makeStatusFilter('open');
const issues = [{ status: 'open' }, { status: 'closed' }];

console.log(issues.filter(isOpen));
```

`isOpen` was created while `status` was `'open'`. When `filter` calls it later, the inner function still has access to that `status`. This practical pattern—create a function configured with some data, then use it later—is a closure.

## Shape data with destructuring, spread, and rest

Destructuring names the values you need without repeated property access.

```js
const issue = { id: 8, title: 'Fix search', status: 'open', priority: 'high' };
const { title, status } = issue;
const [firstIssue] = [issue];

const describe = ({ id, title }) => `#${id} ${title}`;
console.log(describe(firstIssue));
```

Rest gathers remaining items or properties.

```js
const { priority, ...issueWithoutPriority } = issue;
const addLabels = (issue, ...labels) => ({ ...issue, labels });
```

Read the direction carefully: spread expands values into a new literal or call; rest gathers the remaining values during destructuring or parameter collection.

## Choose the operation that states the intent

Before reading the answers, choose one operation for each requirement:

1. Return only open issues.
2. Find issue 17.
3. Does any issue have high priority?
4. Are all closed issues assigned?
5. Produce a title for every issue.
6. Calculate issue counts grouped by status.

<details>
<summary>Compare your choices</summary>

1. `filter`: selection may produce zero or many issues.
2. `find`: one matching issue, or `undefined`.
3. `some`: “at least one?”
4. `every`, usually after selecting closed issues: `issues.filter(isClosed).every(hasAssignee)`.
5. `map`: transform every item into a corresponding title.
6. `reduce`: accumulate a single grouped-count object.
</details>

```js
const issues = [
  { id: 17, title: 'Broken search', status: 'open', priority: 'high', assignee: 'Mina' },
  { id: 18, title: 'Refresh docs', status: 'closed', priority: 'low', assignee: null },
];

const openIssues = issues.filter((issue) => issue.status === 'open');
const issue17 = issues.find((issue) => issue.id === 17);
const hasHighPriority = issues.some((issue) => issue.priority === 'high');
const titles = issues.map(({ title }) => title);
const countsByStatus = issues.reduce((counts, issue) => ({
  ...counts,
  [issue.status]: (counts[issue.status] ?? 0) + 1,
}), {});
```

`reduce` is appropriate when you need one accumulated result, such as grouped counts. It is not a badge of sophistication. Prefer `map`, `filter`, `find`, `some`, or `every` when one of those names says exactly what the requirement means. For example, use `find` instead of `filter(...)[0]` when you want one item.

## Current value → transformation → next value

JavaScript permits mutation. We are deliberately practising transformations that create a next array or object because application state becomes easier to inspect: you can compare the old and new value, know which branch changed, and avoid surprising another reference.

```js
const closeIssue = (issues, issueId) =>
  issues.map((issue) =>
    issue.id === issueId ? { ...issue, status: 'closed' } : issue,
  );
```

This returns a new array. It keeps unchanged issue objects as they were and creates a new object only for the issue being changed. It is an intentional, shallow update—not a claim that JavaScript itself is immutable.

## Try it

**Prediction:** `closeIssue` above returns a new array using `map`. Before running anything,
predict whether the *unchanged* issue objects inside that new array are the same references
as the ones in the original array, or new objects too.

**Run / inspect:**

```js
const issues = [
  { id: 1, status: 'open' },
  { id: 2, status: 'open' },
];

const closeIssue = (issues, issueId) =>
  issues.map((issue) =>
    issue.id === issueId ? { ...issue, status: 'closed' } : issue,
  );

const next = closeIssue(issues, 1);

console.log(issues === next);
console.log(issues[1] === next[1]); // the untouched issue, id 2
console.log(issues[0] === next[0]); // the changed issue, id 1
```

**What happened:** the outer array is a new reference (`issues === next` is `false`), the
untouched issue object is the *same* reference in both arrays, and only the changed issue is
a new object.

**Why:** `map` always returns a new array, but the callback above only builds a new object
for the id that matches — every other iteration returns the exact value it received. This is
not an accident worth losing: it is the reason `closeIssue` is cheap to call on a large list,
and it is exactly the signal a reference-comparing re-render check needs later — an unrelated
issue's row can skip re-rendering because its object identity provably did not change,
something a full deep clone of every issue would have destroyed without changing a single
visible value.

## Focused exercise — Issue triage report

**Mode: self-reported practice with your own Node or browser-console evidence. This exercise is not automatically verified.**

Create a small file such as `issue-triage.js` outside this repository, or use your browser console. Start with this fixture:

```js
const issues = [
  { id: 17, title: 'Broken search', status: 'open', priority: 'high', assignee: { name: 'Mina' }, labels: ['bug'] },
  { id: 18, title: 'Refresh docs', status: 'closed', priority: 'low', assignee: { name: 'Noah' }, labels: ['docs'] },
  { id: 19, title: 'Slow export', status: 'open', priority: 'medium', assignee: null, labels: ['performance'] },
  { id: 20, title: 'Invite email typo', status: 'closed', priority: 'high', assignee: null, labels: ['bug', 'email'] },
];
```

Write small functions that, from this one dataset:

- return open issues;
- find an issue by ID;
- report whether any issue is high priority;
- report whether every closed issue has an assignee;
- derive counts grouped by status;
- close one issue by ID without mutating the input array or its original issue object.

Then run this experiment before you repair it:

```js
const copiedIssues = [...issues];
copiedIssues[0].assignee.name = 'Changed by accident';

console.log(issues[0].assignee.name);
console.log(issues[0] === copiedIssues[0]);
```

Observe the unexpected result. Explain which reference is still shared. Repair the update so that only the relevant issue and its `assignee` branch receive copies—for example, when changing an assignee’s name. Finally, prove with `console.log` or assertions that the original `issues` fixture is unchanged after both updates.

### Hints

<details>
<summary>Hint 1: where should I inspect?</summary>

Before and after each operation, inspect the result and the original fixture. For the copy bug, compare both the outer array and the nested objects with `===`.
</details>

<details>
<summary>Hint 2: which concept matters?</summary>

`[...issues]` creates a new array, but it does not copy each issue object or each nested assignee object.
</details>

<details>
<summary>Hint 3: what small experiment can I perform?</summary>

Log `issues === copiedIssues`, `issues[0] === copiedIssues[0]`, and `issues[0].assignee === copiedIssues[0].assignee`. Each comparison answers a different question.
</details>

<details>
<summary>Hint 4: an implementation clue</summary>

For an assignee-name update, map over the array. For the matching issue, return `{ ...issue, assignee: { ...issue.assignee, name: nextName } }`; return the original issue for non-matches.
</details>

<details>
<summary>Reference explanation — reveal after a meaningful attempt</summary>

One valid approach uses `filter`, `find`, `some`, and `reduce` for the first five requirements, because each method names the needed result. An immutable close uses `map`: return a copied issue with a new status only for the matching ID.

The reference bug occurs because array spread copies only the array container. Its first element still refers to the original issue, whose `assignee` still refers to the original nested object. The repair copies each changed level: new array, changed issue object, and changed assignee object. Equivalent implementations are valid if they preserve the original fixture and express the same behavior.
</details>

## When this goes wrong

When a transformation surprises you, use this small loop:

1. Inspect the input before the transformation.
2. Inspect the returned value—not only the screen or final log.
3. Check whether the original changed.
4. Compare identities with `===` when references may be shared.
5. Reduce the transformation to one item.
6. Inspect what the callback receives and what it returns.

This is especially useful for a missing `return` in a callback, a predicate that selects the wrong items, or a nested update that copied too little.

## Common mistakes

- **“`const` makes the object immutable.”** It prevents rebinding, not property mutation.
- **Using `map` to select.** If the requirement is “only these items,” use `filter`.
- **Using `filter(...)[0]` for one item.** `find` communicates that intent directly.
- **Treating spread as deep cloning.** It copies one container level.
- **Calling a callback while trying to pass it.** `onSave` and `onSave()` are different values.
- **Using `reduce` when a named operation is clearer.** Clarity is the point.
- **Thinking a closure copies values forever.** It retains access to its surrounding lexical environment; the useful question is which binding the function can still access.

## In the project

This is **B01 — JavaScript readiness**. No issue-tracker code is written here; what carries forward is the reasoning.

Every one of these operations reappears with a name attached. `issues.filter(...)` becomes FS03.2's derived `visibleIssues`. `issues.map(...)` becomes FS03.1's list rendering and FS03.2's immutable status update. Functions passed as values become FS03.3's `onCreate` callback. And the reference rule becomes load-bearing: React compares the old and new state by identity, so mutating an array in place produces data that changed and a screen that did not.

TypeScript and React add vocabulary on top. They do not replace any of this.

## Closed-book checkpoint

Close the lesson and answer before reopening the details.

1. Why can a `const` variable still refer to a mutable object?
2. Explain the difference between `map` and `filter` using an issue list.
3. When is `find` clearer than `filter`?
4. What does object or array spread copy, and what does it leave shared?
5. What makes a closure possible?
6. A function receives an array of issues and must answer “does at least one issue have no assignee?” Which operation expresses that question, and why?
7. Why might an application deliberately create a new array or object instead of mutating the current one?

<details>
<summary>Check your recall</summary>

1. `const` fixes the binding, not the object it references.
2. `map` returns one transformed output per input; `filter` returns only inputs whose predicate is true.
3. Use `find` when one matching item (or no item) is the meaningful result.
4. Spread copies the outer array/object only; nested objects or arrays stay shared until specifically copied.
5. A function can access bindings in the lexical environment where it was created, even when called later.
6. `some`, because the result is an “at least one?” boolean.
7. New values make changes explicit, avoid accidental shared-reference effects, and make state easier to compare and reason about.
</details>

## Resources

- [MDN: Working with objects](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Working_with_objects) — references, properties, and object behavior.
- [MDN: Functions](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Functions) — function values, callbacks, and closures.
- [MDN: Indexed collections](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Indexed_collections) — array methods and transformation patterns.
- [MDN: Spread syntax](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Spread_syntax) — shallow-copy behavior and syntax.

## You are done when

- [ ] I predicted and ran the reference, shallow-spread, callback, transformation, and immutable-update examples.
- [ ] I can choose an array operation based on the result I need.
- [ ] I completed the issue triage exercise and used evidence to repair the shallow-reference mistake.
- [ ] I can explain functions as values and closures in practical terms.
- [ ] I attempted the closed-book checkpoint before seeing its answers.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_01.md`
- Official sources: MDN JavaScript Guide and JavaScript Reference links above
- Versions: MDN documentation consulted 2026-08-13; Node 25
- Consulted: 2026-08-14
- Curriculum authority: `CURRICULUM.md` §11 FS01.1 — topics and exercise style
- Laravel source: not applicable; this is language groundwork before framework work
