# FS02.4 — Functions, generics and reusable types

Lesson ID: FS02.4  
Title: Functions, generics and reusable types  
Part: 02 — TypeScript foundations  
Order: 4  
Status: Published  
Estimated effort: 95–120 minutes  
Difficulty: Foundation  
Prerequisites: FS02.3 — Unions, narrowing and unknown  
Project milestone: B02 — Type the future application  
Primary source dossier: TYPESCRIPT_HANDBOOK.md; FSO_TYPESCRIPT.md  
Last reviewed: 2026-08-14

## Preserve a relationship on purpose

FS02.1 asked what TypeScript knows before runtime. FS02.2 asked which values our application permits. FS02.3 asked what TypeScript knows at this exact control-flow point.

This lesson asks: **how do types preserve useful relationships through functions and reusable code?**

Set up a fresh, course-owned lab. It is not the future Issue Tracker.

~~~sh
mkdir -p .dalt/workspace
cp -R .dalt/course/fullstack/typescript-functions-lab/starter .dalt/workspace/fs02-4-typescript-functions-lab
cd .dalt/workspace/fs02-4-typescript-functions-lab
npm ci
~~~

Keep the loop small: read, predict, change, typecheck, hover, run, explain, then change a requirement and repair it. `npm run typecheck` and `npm run run` are clean checks. The `stage:*` commands intentionally show a compiler error; a non-zero result there is evidence for the experiment, not a final failure.

## A function is a contract

Open `src/functions.ts`. Start with the ordinary domain function:

~~~ts
function findIssueById(
  issues: Issue[],
  id: number
): Issue | undefined
~~~

Read it as a contract between caller and implementation:

~~~text
parameter types → values allowed in
function body    → assumptions the implementation may make
return type      → result promised to callers
~~~

The caller may provide an issue array and numeric id. The body may use those facts. Every return path must produce an `Issue` or `undefined`.

### Predict: does this branch satisfy the promise?

Before opening the diagnostic, predict whether a branch returning `'not found'` satisfies `Issue | undefined`.

~~~sh
npm run stage:return-contract
~~~

It does not. Do not immediately widen the return type to `Issue | undefined | string` just to silence the diagnostic. First choose behavior: should absence be `undefined`, a `Result<Issue>`, or a thrown error? Each is a different contract.

For the simple lookup, `undefined` is coherent. Later in this lab, `findIssueResult` deliberately changes the requirement to `Result<Issue>` so callers must narrow `ok` before reading `value`. That retrieves FS02.3’s discriminated-union reasoning. A generic result type does not prove anything about external data; FS02.5 owns that trust boundary.

Write one small local helper without a return annotation, hover it, and record its inferred return type. Then add a return annotation only if the boundary becomes meaningful: an exported/public contract, a useful guard against drift, a complex boundary, or an exercise requirement. Inference is often enough locally; “annotate every function” is not a rule.

## Functions are values too

Retrieve FS01.1: a function can be passed around like data. A function type expression describes the shape of a callable value:

~~~ts
type IssuePredicate = (issue: Issue) => boolean;

function filterIssues(issues: Issue[], predicate: IssuePredicate): Issue[]
~~~

Predict all three calls before running the stage: an inline callback that returns a boolean, a callback expecting a `number` instead of an `Issue`, and a callback returning a title string.

~~~sh
npm run stage:callbacks
~~~

The inline callback gets its `issue` parameter from context. The second callback asks for the wrong input; the third does not produce the required boolean. Keep callback contracts this practical: they make transformations and filters understandable now, and prepare you for later library callbacks without introducing React.

Optional parameters are appropriate only when callers may genuinely omit an argument. For example, `formatIssue(issue, prefix?: string)` can reasonably choose a default prefix. A required `id` should not become optional merely to avoid an error. Rest parameters are useful when a function intentionally collects a variable number of same-kind values:

~~~ts
function summarizeTitles(...issues: Issue[]): string[] {
  return issues.map((issue) => issue.title);
}
~~~

Hover `summarizeTitles`: callers pass zero or more `Issue` values, not an arbitrary array shape. This is the practical depth needed here.

## Reuse a relationship, not a word

Suppose we write these two functions:

~~~ts
function firstIssue(issues: Issue[]): Issue | undefined
function firstProject(projects: Project[]): Project | undefined
~~~

What actually differs? The element type. A weak abstraction loses that fact:

~~~ts
function first(items: unknown[]): unknown
~~~

Predict why `unknown[] → unknown` is weaker. It accepts the array, but callers no longer know that an `Issue[]` produces `Issue | undefined` or that a `Project[]` produces `Project | undefined`.

Now inspect the lab version:

~~~ts
function first<T>(items: T[]): T | undefined
~~~

`T` does not mean “anything.” It means “whatever the element type is here; preserve that relationship in the result.”

~~~text
Issue[]   → T = Issue   → Issue | undefined
Project[] → T = Project → Project | undefined
~~~

Call `first(issues)` and `first(projects)` in `src/exercise.ts` without writing `<Issue>` or `<Project>`. Hover both calls. What did TypeScript infer for `T`? Prefer inference when the argument already supplies enough evidence.

## Generic or concrete?

Before reading on, decide which candidates should be generic and why.

~~~ts
function findIssueByStatus(issues: Issue[], status: IssueStatus): Issue | undefined
function first<T>(items: T[]): T | undefined
function getIssueTitle<T>(issue: T): string
function findById<T extends { id: number }>(items: T[], id: number): T | undefined
~~~

`first` preserves a real input/output relationship. `findById` does too: it returns the same kind of item supplied. `findIssueByStatus` communicates a domain operation and is clearer as concrete code. `getIssueTitle<T>` is over-generic: `T` does not provide `title`, and no useful relationship is being preserved. Simplify it to `getIssueTitle(issue: Issue): string` if the operation is specifically about issues, or use a minimal honest constraint only if multiple shapes really need it.

Use these rules:

1. A type parameter should usually connect multiple meaningful positions.
2. Prefer fewer type parameters and inference.
3. Add a constraint only when the body needs a capability.
4. A domain-specific function may be clearer when concrete.
5. Do not generalize before a repeated relationship exists.

Ask: **what does `T` connect?** If there is no answer, the generic may be decoration rather than design.

## Constrain only the capability you need

`findById` should work for `Issue`, `Project`, and a future `UserSummary`, but it needs `item.id`. Predict whether an unconstrained `T` can access `.id`, then run:

~~~sh
npm run stage:constraint
~~~

The error says the generic promise is too broad. Repair the idea, not the symptom:

~~~ts
function findById<T extends { id: number }>(
  items: T[],
  id: number
): T | undefined
~~~

`T` still varies, but every allowed item guarantees the one capability the implementation needs. In `src/exercise.ts`, hover calls with issues and projects. Then try a local `type Label = { text: string }` array: it should be rejected. Do not over-constrain `T` to `Issue` when `id` is the only needed capability.

## One reusable container, plus familiar narrowing

The lab’s small application type is:

~~~ts
type Result<T> =
  | { ok: true; value: T }
  | { ok: false; error: string }
~~~

It preserves one useful relationship: `Result<Issue>` contains an Issue on success, while `Result<Project>` would contain a Project. The failure shape stays stable. In `exercise.ts`, hover `result` and then each branch of `result.ok ? result.value.title : result.error`. FS02.3 already taught why the boolean literal narrows the union.

This is an example, not a mandate to build a giant `Result` abstraction into every future file. A concrete `Issue | undefined` is still better when absence is ordinary and callers need no error explanation.

## Model related types from a real relationship

`Issue` has fields including `id`, `title`, `description`, `status`, `priority`, and `createdAt`. Describe the relationship in words before selecting a utility type.

- `IssueSummary` intentionally selects `id`, `title`, and `status`, so `Pick<Issue, 'id' | 'title' | 'status'>` documents a subset.
- `NewIssue` initially omits generated `id` and `createdAt`, so `Omit` expresses that relationship in this tiny model. Ask whether server-owned `status` or another field would differ before assuming `Omit<Entity, 'id'>` is always a create contract.
- An update may edit only title, description, status, and priority. First make `EditableIssue` with `Pick`, then make `IssuePatch = Partial<EditableIssue>`. `Partial<Issue>` would incorrectly permit `id` and `createdAt` updates.

Independently writing `IssueSummary` can be clearer when it is an independent contract that merely happens to resemble `Issue`. Derive only when the relationship is true; do not make unreadable derivation chains to avoid a few clear lines.

`Required<IssueDraft>` is a small recognition experiment: it changes optional draft fields into required fields in the type system. It does not validate a runtime draft. `Readonly<IssueSummary>` retrieves FS02.2: it describes read-only use, not `Object.freeze()`, deep immutability, or runtime enforcement.

## Let a finite model expose its dependents

The status labels use a complete mapping:

~~~ts
const statusLabels: Record<IssueStatus, string> = { ... }
~~~

`Record<IssueStatus, string>` says every known status needs a string label. Add `'blocked'` to the stage’s `IssueStatus` union, then run this deliberately broken stage before repairing the mapping:

~~~sh
npm run stage:utility-model-change
~~~

The missing `blocked` label is a real old assumption, not compiler noise. Add a deliberate label, then typecheck again. This is different from FS02.3’s exhaustive switch: here a finite union is a dependency map for object keys.

## Keys and property types, at small depth

First, a `string` key can ask for nonsense. `keyof Issue` instead represents the union of Issue’s actual property keys. The bounded reusable helper is:

~~~ts
function readField<T, K extends keyof T>(value: T, key: K): T[K]
~~~

Predict which keys `readField(issues[0], ...)` accepts, then hover calls using `'status'` and `'notAField'`. The selected key also controls the return type. `T[K]` is indexed access: “the type of property K on T.” Recognize the simpler form too:

~~~ts
type Status = Issue['status'];
~~~

It reuses the property type. Stop here: this helper is useful precisely because it remains readable; no keyof puzzle or type-level machinery is needed.

## Focused exercise — evolve typed issue utilities

**Mode: self-reported practice using your editor, TypeScript, and Node. This exercise is not automatically verified.**

Start with `npm run typecheck` and `npm run run`. Work in `src/functions.ts` and `src/exercise.ts` as one evolving issue/project utility set.

1. **Function contracts.** Temporarily make a return path in `findIssueById` return `'not found'`; predict and inspect the error. Decide whether the function should keep `Issue | undefined` or use `Result<Issue>`. Restore the former, then use `findIssueResult` for the latter and update its caller by narrowing `ok`.
2. **Callbacks.** Add one valid inline `filterIssues` callback. Predict why a `(issue: Issue) => issue.title` callback fails. Do not turn the predicate into `unknown` or `any`.
3. **Generic judgment.** Write down why `first` is generic, why `findIssueById` stays concrete, and why `function titleOf<T>(value: T): string` is over-generic. Simplify the last function to an Issue-specific function.
4. **Useful constraint.** Use `findById` with issues and projects, hover both results, and try it with `{ text: string }[]` to observe the rejection. Explain which capability the constraint needs.
5. **Related models.** Use `IssueSummary`, `NewIssue`, and `IssuePatch`. Before writing `IssuePatch`, list fields that must remain protected. Confirm that a patch cannot include `id` or `createdAt`. Explain whether the `NewIssue` omission is truly the create contract for this model.
6. **Model change.** Add `'blocked'` to `IssueStatus` in the normal lab code. Run `npm run typecheck` before changing `statusLabels`. Classify the failure, add a label, and rerun typecheck. Do not weaken the mapping to `Record<string, string>`.
7. **Keys and indexed access.** Use `readField` with one valid key and one invalid key, then explain the inferred valid result. Add `type StatusAgain = Issue['status']` and say why it might avoid duplicated property unions.
8. Finish with `npm run typecheck` and `npm run run`. Keep no `any`, blind assertion, non-null assertion, conditional type, custom mapped type, overload, or class abstraction.

### Hints

<details>
<summary>Hint 1 — generic judgment</summary>

What relationship are you trying to preserve?
</details>

<details>
<summary>Hint 2 — generic judgment</summary>

Does the type parameter appear in more than one meaningful position? Could a concrete domain type communicate this more clearly?
</details>

<details>
<summary>Hint 3 — constraint</summary>

Which property does the implementation need? What minimum shape guarantees it?
</details>

<details>
<summary>Hint 4 — utility types</summary>

Describe the relationship first: selected subset, omission, optional editable version, or complete keyed mapping. Then choose `Pick`, `Omit`, `Partial`, or `Record`.
</details>

<details>
<summary>Reference explanation — reveal after an honest attempt</summary>

`findIssueById` is concrete because it expresses an issue-domain operation; `first<T>` preserves the element/result relationship across many collections. `findById` needs only `{ id: number }`, so its constraint is honest and still returns the caller’s concrete item type. `Result<T>` preserves the success-value relationship and its `ok` field enables FS02.3 narrowing.

`Pick` fits an intentional summary; `Omit` fits this small create shape only while every remaining field is genuinely client-supplied; `Partial<EditableIssue>` protects generated fields better than `Partial<Issue>`; `Record<IssueStatus, string>` intentionally makes a new status break an incomplete label map. An independent type is clearer when the supposedly related model has its own contract.
</details>

## Debug reusable TypeScript deliberately

When a function or reusable type goes wrong, ask: What contract does this function claim? What does every return path produce? What did inference say before annotation? What relationship does each type parameter connect? Which minimum capability does the body require? Would concrete code be clearer? Is a derived type expressing a real domain relationship? Did a model change expose a dependent assumption? Am I about to add `any`, `as`, or `!` only to hide useful feedback?

## Closed-book checkpoint

Answer before revealing the comparison answers.

1. When is an explicit return type useful, and when can inference be enough?
2. What does `(issue: Issue) => boolean` describe?
3. What relationship does `T` preserve in `first<T>(items: T[]): T | undefined`?
4. Why is that stronger than `unknown[] → unknown`?
5. What does `T extends { id: number }` mean in `findById`?
6. Why can a reusable-looking issue function be better concrete than generic?
7. When do `Pick`, `Omit`, `Partial`, and `Record` express useful relationships?
8. Why can `Partial<Issue>` be a poor update contract?
9. What does `keyof T` represent, and why can a new union member intentionally break a `Record` mapping?
10. Transfer: a function accepts an object and returns its `name`. Should it be `function nameOf<T>(value: T): string`? Explain the constraint or concrete design you would choose.

<details>
<summary>Reveal comparison answers</summary>

1. Use one for a meaningful public/complex boundary or to prevent drift; local implementation can often rely on inference.
2. A callable contract: it receives an Issue and returns a boolean.
3. The array element type and returned item type.
4. `unknown` loses the caller’s element/result relationship.
5. T may vary, but every allowed value supplies a numeric id.
6. A concrete domain function can state its intent more clearly when no reusable relationship needs preserving.
7. They express a selected subset, a true omission, an intentionally optional shape, and a complete mapping over known keys.
8. It can make protected/server-owned fields writable.
9. It is a union of known keys; `Record` requires each union member as a key, so the new member exposes the missing mapping.
10. Not unconstrained: the body needs `name`. Use `<T extends { name: string }>` only if multiple shapes truly need it, otherwise use the concrete domain type.
</details>

## Carry this forward

Later you will meet typed React props, `useState<Issue[]>`, generic library APIs, TanStack Query result types, reusable hooks, and derived input/summary models. React is not involved yet. `<T>` is ordinary TypeScript for preserving a real relationship, not React magic. FS02.5 will separately address how runtime data becomes trustworthy.
