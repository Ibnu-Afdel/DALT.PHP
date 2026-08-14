# FS02.1 — The TypeScript mental model

Lesson ID: FS02.1  
Title: The TypeScript mental model  
Part: 02 — TypeScript foundations  
Order: 1  
Status: Published  
Estimated effort: 60–90 minutes  
Difficulty: Foundation  
Prerequisites: B01 / Part 01 complete  
Project milestone: B02 — Type the future application  
Primary source dossier: TYPESCRIPT_HANDBOOK.md; FSO_TYPESCRIPT.md  
Last reviewed: 2026-08-14

## Start with a JavaScript surprise

An issue title is meant to be text. JavaScript lets a program state that assumption only through the code it tries to run.

Set up one small, resettable lab. It is course-owned and is **not** the future Issue Tracker:

```sh
mkdir -p .dalt/workspace
cp -R .dalt/course/fullstack/typescript-lab/starter .dalt/workspace/fs02-1-typescript-lab
cd .dalt/workspace/fs02-1-typescript-lab
npm ci
```

Read `src/runtime-failure.mjs` before running it. What will the first call print? What do you think happens when the second call supplies `17` instead of text?

```sh
npm run runtime
```

The first call works. The second reaches JavaScript runtime and fails because a number has no string method such as `trim`.

Now inspect `src/checker-catches-it.ts`. It is deliberately the same small relationship expressed with a useful function contract. Predict which call TypeScript will reject, then run:

```sh
npm run stage-a
```

Do not copy the diagnostic wording into your notes; wording can change between TypeScript versions. Read its relationship instead: the value supplied at the call site is a number, while the parameter contract asks for a string.

TypeScript did not make JavaScript runtime magical. It identified an incompatible relationship **before** execution.

```text
TypeScript source
        ↓
checker compares claims and relationships
        ↓
diagnostic before execution

JavaScript
        ↓
runtime executes values that actually arrive
        ↓
behavior or runtime failure
```

The question for this lesson is: **what can TypeScript know before the program runs?**

## Let TypeScript show its work

Open `src/issue-summary.ts` in an editor with TypeScript support. Before adding any annotations, hover these names:

- `triageIssue`
- `inferredTitle`
- `summary`

Write down what you predicted first, then compare it with the editor. TypeScript can infer useful types from the object literal and from the call to `formatIssueSummary`; `inferredTitle` should not need an annotation merely to repeat what TypeScript already knows.

Try this temporary decoration:

```ts
const inferredTitle: string = triageIssue.title;
```

Hover it again. Did the annotation teach the checker anything it did not already know? Remove it.

An annotation earns its place when it communicates or constrains a boundary. The parameter of `formatIssueSummary` is one: callers need to know the shape they must supply. The return annotation is also a readable promise at an exported boundary. Local inference is usually clearer than annotations everywhere.

> [!TIP]
> Editor hover is evidence, not a convenience feature. When you are unsure what TypeScript inferred, inspect it before adding syntax.

## A type error is not a runtime error

The runtime program proved one thing: JavaScript can receive a number and only fail when it tries to use it as a string. The checker proved a narrower thing: given the types in the source, that particular call disagrees with its declared contract.

That distinction matters in both directions:

- A **type error** is a static disagreement between TypeScript's model of values and the relationships your source claims.
- A **runtime error** occurs when JavaScript actually executes and something goes wrong.

A green typecheck means the checker accepted the relationships it could see under this project’s rules. It is not a mathematical proof that no runtime failure can ever occur. JavaScript still receives actual values, performs I/O, and runs in a real environment. We will study untrusted runtime data later; do not solve that future problem here.

When a diagnostic appears, use this small protocol:

1. What type do I currently have?
2. What type is expected here?
3. Where do they stop matching?
4. Is my program wrong, is my type model wrong, or is evidence missing?
5. Am I about to silence useful feedback instead of understanding it?

The goal is not “make red text disappear.” A type error is information about assumptions that disagree.

## What survives into JavaScript?

Open `src/erasure.ts` and predict each item before compiling it:

- `IssueDraft` type alias;
- `IssueWithNote` interface;
- parameter and variable annotations;
- `draft`, `issue`, `printTitle`, and the `console.log` call;
- the `import type` line.

Then create and inspect real emitted JavaScript:

```sh
npm run emit:erasure
sed -n '1,220p' .tmp/erasure/erasure.js
npm run run:erasure
```

The aliases, interfaces, and annotations disappear. The objects, function, and call remain because JavaScript runtime needs values and executable code. The emitted file is temporary inside your resettable workspace; it is not a repository artifact to commit.

This is **type erasure**:

```text
TYPE INFORMATION helps the checker

JAVASCRIPT VALUES reach runtime
```

The emitter command is an experiment, not a preview of the future React build setup. Later a frontend tool can transform and bundle application assets while TypeScript is used primarily to typecheck. The useful distinction now is simple:

- **Typecheck:** are the static relationships accepted?
- **Build / transform:** how does source become runnable JavaScript or application assets?

## Shape, not membership

`triageIssue` has `id`, `title`, `status`, and `priority`. `IssueSummary` needs only `id` and `title`. The call `formatIssueSummary(triageIssue)` is accepted even though `triageIssue` was never constructed from a class named `IssueSummary`.

Why? TypeScript is primarily **structurally typed**. It asks whether the required shape is present, not whether a value has nominal membership in a named type.

```ts
type IssueSummary = { id: number; title: string };

const richerIssue = {
  id: 17,
  title: 'Broken search',
  status: 'open',
  priority: 'high',
};

const summary: IssueSummary = richerIssue; // required structure is present
```

This is useful in application code: a richer existing value can satisfy a smaller contract without conversion ceremony. We are not studying every assignability edge case yet; the practical rule is to compare the required structure with the value you have.

Because interfaces and type aliases disappear, they do not create runtime constructors either. JavaScript cannot do this:

```ts
// issue instanceof IssueSummary
```

There is no runtime `IssueSummary` value for `instanceof` to inspect. A TypeScript declaration tells the checker about a shape; it does not create an object, class, or runtime validator.

## A type-only module relationship

You already used ES module imports in FS01.2. In `erasure.ts`, inspect this line:

```ts
import type { HasTitle, IssueSummary } from './contracts.js';
```

Compare the source with `.tmp/erasure/erasure.js`. There is no ordinary runtime import for that line, because the imported names are used only by the checker.

`import type` communicates that the imported thing is compile-time information, not a JavaScript value this module needs at runtime. Keep the module lesson you already learned; this is only its TypeScript-specific distinction.

## Strictness is an intentional project choice

Open `tsconfig.json`. This file configures how TypeScript interprets and checks this project. It is configuration, not ordinary application code.

The lab enables `strict`. One effect is that a function parameter cannot quietly become an implicit `any`. Add this temporary function to `src/issue-summary.ts`:

```ts
const labelFor = (issue) => issue.title;
```

Run `npm run typecheck`, read what relationship TypeScript cannot establish, then remove the experiment. In a strict project, the checker asks you to supply enough information rather than silently accepting a weak contract. Do not disable strictness just to hide that feedback.

We are deliberately not walking every compiler option. The durable model is that a TypeScript project has rules, and those rules affect what the checker accepts.

## Focused exercise — What survives?

**Mode: self-reported practice using your editor, `tsc`, emitted JavaScript, and Node. This exercise is not automatically verified.**

Work in the same lab. First, without changing code, record predictions for these questions in your own notes:

1. Which declarations in `erasure.ts` affect runtime JavaScript, and which disappear?
2. Which values in `issue-summary.ts` are inferred without annotations?
3. Which call should TypeScript reject, and why?
4. Why is `triageIssue` accepted as an `IssueSummary` even though it has extra fields?
5. Does `import type` produce a runtime dependency?

Then work through the evidence:

1. Run `npm run typecheck`. The starter intentionally reports a mismatch for the incoming issue key.
2. Read `src/contracts.ts` and `src/issue-summary.ts`. The requirement changed: issue identifiers now use visible keys such as `ISS-19`.
3. Ask the real question before editing: is the caller wrong, or is the function contract wrong? Make the smallest coherent decision across the type and the affected values. Do not widen values merely until the error turns green.
4. Run `npm run typecheck` again. It should pass only after the model and values agree.
5. Run `npm run build`, inspect `dist/issue-summary.js`, then execute it with `node dist/issue-summary.js`.
6. Re-run the erasure activity and compare source/output one more time. Explain why neither `type`, `interface`, annotations, nor the type-only import became a runtime object/import.

Your repair can differ in formatting, but it must preserve a useful contract: one consistent issue identifier model, a typed function boundary, and a richer object structurally accepted where only an issue summary is needed.

### Hints

<details>
<summary>Hint 1 — what evidence should I inspect?</summary>

Read the compiler relationship, then inspect the relevant caller and the `IssueSummary` definition. Hover the values instead of guessing what TypeScript believes.
</details>

<details>
<summary>Hint 2 — compare actual and expected types</summary>

The current call has a string key such as `ISS-19`; the starter contract describes a number. Decide which side matches the changed domain requirement.
</details>

<details>
<summary>Hint 3 — which mental model applies?</summary>

Inference can describe local values without annotations. Structural typing accepts the richer `triageIssue` because it contains the required shape. Type aliases/interfaces/imported types are erased before runtime.
</details>

<details>
<summary>Hint 4 — a small implementation clue</summary>

If visible string keys are now the domain rule, update the identifier property in `IssueSummary` and make the numeric examples agree. Then use `npm run typecheck` to find any remaining disagreement.
</details>

<details>
<summary>Reference explanation — reveal after an honest attempt</summary>

The starter says `IssueSummary.id` is a number while the changed requirement supplies `ISS-19`, a string. If visible string keys are the intended domain identifier, changing the contract to `id: string` and updating the example IDs keeps the model coherent; widening to `string | number` would claim two valid identifier forms without evidence that the domain needs both.

`triageIssue` remains acceptable because it has the required `id` and `title` fields, even with extra fields. The emitted JavaScript contains values, functions, and calls, but no aliases, interfaces, annotations, or type-only import.
</details>

## Closed-book checkpoint

Close the source and answer these from memory before opening the comparison answers.

1. What language actually executes after TypeScript source has been processed?
2. What happens to a TypeScript `type` or `interface` at runtime?
3. What is the difference between a TypeScript type error and a JavaScript runtime error?
4. Why inspect inference before adding an annotation?
5. What does structural typing mean in practical application code?
6. Why can an object satisfy a named type without being constructed from that type?
7. What does `import type` communicate?
8. A typecheck passes for `formatTitle(title: string)`, then a server later sends `{ title: 42 }`. What did the green check establish, and what did it not establish?

<details>
<summary>Reveal comparison answers</summary>

1. JavaScript executes at runtime.
2. They are erased; they do not become runtime objects or constructors.
3. A type error is a static mismatch in TypeScript’s source model. A runtime error happens while JavaScript executes actual values.
4. Inference may already express the local relationship; an annotation should add a useful contract or constraint, not duplicate evidence.
5. Compatibility is based primarily on required members being present, rather than nominal membership in a named type.
6. TypeScript checks whether the required shape exists; the type name does not create a runtime membership relation.
7. The import supplies compile-time type information only and should not be treated as a runtime JavaScript value dependency.
8. It established that the checked source relationships agree under the project configuration. It did not prove that every future runtime value from outside that source will really be a string.
</details>

## Before you mark this lesson complete

You are ready to self-report completion when you have:

- predicted and observed the JavaScript runtime failure;
- seen TypeScript reject the related incompatible call;
- inspected editor inference;
- inspected emitted JavaScript;
- repaired the focused exercise’s deliberate contract mismatch;
- explained structural typing and `import type` from the lab;
- attempted the closed-book checkpoint without notes.

The next TypeScript lessons remain unavailable. You have established the model they will use; you have not yet studied all TypeScript syntax or runtime data validation.
