> **What exists when you finish:** a small Node program, in two modules, that turns
> a raw issue list into a triage report — written with transformations rather than
> mutations, and with its failure paths visible.

## What you are building

A command-line triage report over a fixed list of issues. Run it and you get
something like:

```text
Issue triage — 7 issues
  open        4
  in_progress 2
  done        1
  high priority, still open:  ISS-3, ISS-7
  oldest open: ISS-1
```

Small. Deliberately small. The value is not the output — it is that every number on
that report is **derived** from the source list rather than counted into a variable,
and that the source list is never modified.

By the end you will have written the JavaScript that Part 03 turns into a screen.
`issues.filter(...)` here is `visibleIssues` there; `issues.map(...)` here is the
list rendering there. You are building the same program twice — once where you can
see it plainly, and once where React is watching.

## Why this milestone exists

Because the two habits that make React comprehensible are habits, not knowledge, and
you cannot acquire a habit from a lesson.

**Derive, do not store.** The moment you write `let openCount = 0` and increment it,
you have created a second copy of a fact that the issue list already contains. In a
script it drifts when you forget to update it. In React it drifts silently and the
screen lies.

**Transform, do not mutate.** React decides whether to re-render by comparing
references. `issues.push(x)` gives back the same array, React sees no change, and
your update vanishes. That failure is genuinely hard to debug in a component and
completely obvious here, which is why here is where you should meet it.

## Before you start

FS01.1 and FS01.2 complete. Node available (`node --version`).

Copy the course-owned workspace. It is not the future issue tracker; it is a
scratchpad you may reset at any time.

```sh
mkdir -p .dalt/workspace
cp -R .dalt/course/build/B01-javascript-readiness/starter .dalt/workspace/b01-issue-triage
cd .dalt/workspace/b01-issue-triage
```

You get one file, `issue-data.mjs`, holding the raw list. Everything else is yours.

To reset: delete `.dalt/workspace/b01-issue-triage` and copy again. Your work stays
inside `.dalt/workspace/`, which is gitignored and nowhere near the framework.

---

## Stage 1 — One module, one report

Create `main.mjs` beside `issue-data.mjs`, import the list, and print the total plus
a count per status.

**Working looks like:** `node main.mjs` prints the counts, and they add up to the
total.

**Check it yourself:** change one issue's status in `issue-data.mjs` and rerun. Every
count that should have moved, moved — and you did not edit `main.mjs` to make that
happen. If you had to, something is being stored that should be derived.

## Stage 2 — Choose the operation that states the intent

Add three more lines to the report: high-priority issues that are still open, a count
of issues per assignee, and the oldest open issue.

Each one wants a different operation. Before writing any of them, decide which and
say why:

| You want | The operation that says so |
|---|---|
| the same number of items, transformed | `map` |
| fewer items, unchanged | `filter` |
| one item | `find` |
| one value from many | `reduce` |
| a yes/no about the whole list | `some` / `every` |

**Working looks like:** each line is one expression whose shape matches its intent.

**Check it yourself:** read each line aloud as a sentence. `issues.filter(...)[0]`
reads as "narrow the list, then take the first", which is not what you meant — you
meant `find`. If the code does not read as the sentence you would say, change the
operation, not the comment.

> Reach for `reduce` last. It can express all of the above and communicates none of
> them. Use it where you genuinely need one value from many — the per-assignee
> counts are the honest case here.

## Stage 3 — Split the module

Create `issue-tools.mjs`. Move the derivation functions into it with named exports.
`main.mjs` keeps only the data import and the printing.

**Working looks like:** `node main.mjs` prints exactly what it printed before.

**Check it yourself:** this stage must produce **zero** output change. If the report
moved, the refactor changed behaviour and you should find out why before continuing.
A module boundary is a place to cross, not a place to redesign.

Notice what the split makes obvious: the functions you moved take a list and return a
value, and none of them print. That separation is what makes them testable later, and
in Part 03 it is what makes them components' props rather than components' secrets.

## Stage 4 — Add a change without mutating

Add an operation that marks one issue done, and print the report before and after.

**Working looks like:** the after-report shows one more `done`, and the original
array from `issue-data.mjs` is unchanged.

**Check it yourself — this is the important one:**

```js
const updated = markDone(issues, 'ISS-3');
console.log(issues === updated);              // false — a new array
console.log(issues[0] === updated[0]);        // true  — untouched issues are shared
console.log(issues.find(i => i.id === 'ISS-3').status);   // still the original
```

All three must hold. The second one surprises people and is not an accident: only the
issue that changed becomes a new object, so everything else can be reused. React
depends on exactly that, and you have now written it by hand once.

Then deliberately break it. Change your implementation to mutate in place, rerun the
three checks, and watch the first one become `true`. Put it back.

## Stage 5 — Make a failure visible

`issue-data.mjs` is trustworthy because you can read it. Pretend it is not.

Add a lookup for an issue ID that does not exist, and decide what your function does
about it: return `undefined`, return a default, or throw. Then make the caller handle
your decision.

**Working looks like:** asking for `ISS-999` produces something you deliberately
designed, not a `TypeError` three lines later.

**Check it yourself:** the error message names the thing that went wrong. `Cannot
read properties of undefined (reading 'title')` does not; `Unknown issue ISS-999`
does.

---

## Decisions you have to make

- **Missing issue: `undefined`, a default, or a throw?** All three are defensible.
  `undefined` pushes the decision to the caller; a default hides the problem; a throw
  is loud and needs handling. Write down which you chose and why — FS02.3's
  `RequestState` and FS02.5's parser are the same decision with types attached.
- **How far to split modules?** Two files is right for this. Six would be worse. The
  test is whether a file has a name you can say without "and".
- **Does the per-assignee count belong in `reduce` or a loop?** Either is fine.
  Choose for the reader.

## Acceptance criteria

Read these against what you actually built. Nothing here is checked automatically.

- [ ] `node main.mjs` prints a triage report with counts, high-priority open issues,
      per-assignee counts, and the oldest open issue.
- [ ] Changing `issue-data.mjs` changes the report with no edit to my code.
- [ ] No count is stored in a variable that I increment.
- [ ] Derivations live in `issue-tools.mjs` with named exports; `main.mjs` imports and
      prints. The split changed no output.
- [ ] My "mark done" operation passes all three identity checks from Stage 4.
- [ ] I broke it into a mutation on purpose, saw the first check flip, and restored it.
- [ ] A missing issue produces a failure I designed, with a message naming the ID.
- [ ] I can say, for each transformation, why that operation and not another.

## Prove it to yourself

Close the editor. In your notes:

1. Why is a stored count worse than a derived one, in one sentence?
2. `issues === updated` is `false` but `issues[0] === updated[0]` is `true`. Why is
   that the correct outcome rather than a half-finished copy?
3. Which operation communicates "I want one specific item"?
4. What does `spread` copy, and what does it share?
5. You mutate an array React is holding as state. What does the user see?

## What this unlocks

Part 02 puts types on every value here — the status union, the issue shape, the
missing-issue decision. Part 03 renders this exact report as a screen, and the
derive-don't-store rule stops being style advice and starts being the difference
between a filter that works and one that shows stale rows.
