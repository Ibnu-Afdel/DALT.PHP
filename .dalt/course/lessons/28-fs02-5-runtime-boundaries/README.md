# FS02.5 — Runtime boundaries

Lesson ID: FS02.5
Title: Runtime boundaries
Part: 02 — TypeScript foundations
Order: 5
Status: Published
Estimated effort: 105–130 minutes
Difficulty: Foundation
Prerequisites: FS02.4 — Functions, generics and reusable types
Project milestone: B02 — Type the future application
Primary source dossier: TYPESCRIPT_HANDBOOK.md; FSO_TYPESCRIPT.md
Last reviewed: 2026-08-14

## Why this matters

This is the load-bearing lesson of Part 02, and the one this whole course was built to make impossible to skip.

Everything so far has been about the compiler's model of your source. That model is genuinely useful and it has a hard edge: **it ends at the boundary of your program.** A JSON body from DALT, a form field, a URL parameter, `localStorage` — none of it was checked by anything when it arrives. Write `const issue: Issue = await response.json()` and you have not validated anything. You have declared something, and the declaration is a claim you did not earn.

That is the false green check in its purest form. The compiler reports success, the editor is quiet, and the program is one malformed response away from `Cannot read properties of null`. Every later part of this course crosses this boundary — Part 04 with real fetches, Part 05 with database rows, Part 06 with session data — so the habit has to be built here, before there is a server to blame.

## Before you start

Required:

- FS02.4 — Functions, generics and reusable types.
- Node and npm, and an editor with TypeScript support.

Recommended first:

- FS02.3's `unknown` and type-guard material. This lesson is that idea taken seriously.
- FS01.2's fetch sequence — the place the untrusted value actually arrives.

Going deeper in DALT Core — optional:

- [Validation and error contracts](/learn/lessons/02-routing) shows the server-side half of the same distrust. Optional; Part 05 teaches what it needs.

## By the end

You should be able to:

- state exactly where TypeScript's knowledge stops and why;
- explain why `as` is a claim rather than a check, and what it costs when wrong;
- hold external data as `unknown` and prove your way to a typed value;
- write a small structural check (`isRecord`) and say what it does and does not establish;
- choose between a type guard and a parser, and justify the choice;
- write a parser that either returns a trusted value or fails with a message naming the field;
- explain why frontend parsing does not replace backend validation.

## Predict before reading

Write answers down first.

1. `const issue = await response.json() as Issue`. What has been checked at runtime?
2. `typeof value === 'object'` is true. Which two values might you still be holding?
3. A guard is annotated `value is Issue` but its body only checks that the value is an object. Does the compiler complain?
4. Your parser rejects a bad response in the browser. Does the server still need to validate?

Question 3 is the one that decides whether your guards are evidence or decoration.

## Mental model

```text
       INSIDE your program              │        OUTSIDE
   the compiler read this source        │   values you never showed it
                                        │
   Issue, IssueDraft, RequestState<T>   │   JSON bodies, form fields,
   narrowing, generics, guards          │   URL params, localStorage
                                        │
   ─────────── proven ──────────────────┼─── unproven ───────────────
                                        │
                          the boundary ─┘

   crossing it correctly:   unknown → checks → typed value
   crossing it by claim:    as Issue   ← nothing happened
```

The rule, stated once: **`as` is a claim; a check is evidence.** `as` does not inspect the value, does not run at runtime, and does not appear in the emitted JavaScript — FS02.1's erasure, arriving where it costs something. When you write `as Issue`, you take personal responsibility for a fact nothing verified.

The alternative is not more types. It is a small amount of ordinary JavaScript, run at the boundary, once — after which the value is genuinely an `Issue` and everything downstream can rely on the model you built in FS02.2.

## Where TypeScript's knowledge stops

FS02.1 asked what the compiler can know. FS02.2 asked which values the application permits. FS02.3 asked what we know at this exact control-flow point. FS02.4 preserved those relationships through reusable code.

This final TypeScript foundations lesson asks: **where does TypeScript's knowledge stop?**

Set up a fresh, course-owned lab. It is local and deterministic: no React, HTTP server, API, or schema library is involved.

~~~sh
mkdir -p .dalt/workspace
cp -R .dalt/course/fullstack/typescript-runtime-boundaries-lab/starter .dalt/workspace/fs02-5-runtime-boundaries-lab
cd .dalt/workspace/fs02-5-runtime-boundaries-lab
npm ci
~~~

To reset the lab to its intended starting state later, return to the repository root and run:

~~~sh
rm -rf .dalt/workspace/fs02-5-runtime-boundaries-lab
cp -R .dalt/course/fullstack/typescript-runtime-boundaries-lab/starter .dalt/workspace/fs02-5-runtime-boundaries-lab
cd .dalt/workspace/fs02-5-runtime-boundaries-lab
npm ci
~~~

This replaces only this course-owned workspace copy; it does not touch the future application.

Keep the rhythm active: predict, run, inspect, change, rerun. `npm run stage:unknown` intentionally fails to typecheck; that diagnostic is evidence for the experiment. The normal finish is `npm run typecheck`, `npm run run`, and `npm test`.

~~~text
TRUSTED TYPESCRIPT CODE
        ↓
static reasoning works well
        ↓
EXTERNAL RUNTIME BOUNDARY
        ↓
unknown
        ↓ runtime evidence / parsing
TRUSTED APPLICATION VALUE
~~~

## Types are gone when the program runs

Retrieve FS02.1. At runtime, where is this?

~~~ts
type Issue = {
  id: number;
  title: string;
  status: 'backlog' | 'todo' | 'in_progress' | 'done';
  description: string | null;
};
~~~

It is gone. TypeScript erases type aliases, interfaces, and unions when it emits JavaScript. That is why a declaration cannot inspect an incoming JSON object: the runtime does not contain the `Issue` type to compare against.

Imagine the local fixture came from HTTP, local storage, a JSON file, an environment/config value, or a third-party JavaScript library. The source differs; the trust problem does not.

## The lie: a green compiler and bad runtime data

Open `src/unsafe.ts` and `src/fixtures.ts`. The malformed JSON is syntactically valid:

~~~json
{
  "id": "42",
  "title": null,
  "status": "banana",
  "description": 123
}
~~~

### Predict, then run

Before running, answer aloud or write down:

1. Will `const issue = parsed as Issue` typecheck?
2. Will the assertion inspect any fields?
3. Will the assertion itself throw because this object is malformed?
4. What could happen when `displayIssue` uses `issue.title` and `issue.id`?

First establish the static result:

~~~sh
npm run typecheck
~~~

It is green. Now run the exact same compiled program:

~~~sh
npm run unsafe
~~~

It fails when `toUpperCase()` reaches the runtime `null` title. The string id and unknown status also remain exactly what the JSON contained. The compiler was not broken; it was told a false claim.

`value as Issue` means approximately: **“Compiler, treat this value as Issue.”** It does not emit validation, transform malformed data, make another system obey a contract, or throw merely because the claim was false.

~~~text
COMPILER GREEN
      ≠
RUNTIME DATA VALID
~~~

`value as unknown as Issue` is not safer. It only changes what the compiler is willing to believe twice. It produces no runtime evidence.

### JSON syntax is not domain validity

Predict: is this valid JSON? Is it a valid `Issue`?

~~~json
{ "id": "hello" }
~~~

It is valid JSON syntax. It is not a valid Issue: `id` must be a number, and the other required fields are absent. `JSON.parse` tells us that text has JSON syntax; it does not establish our domain contract.

## Remove the lie: return to unknown

`JSON.parse` can be surfaced by TypeScript with an unsafe broad type such as `any`. That API typing must not choose our application trust policy. At the boundary, deliberately place the parsed value into `unknown`:

~~~ts
const payload: unknown = JSON.parse(text);
~~~

Open `src/stages/unknown.ts`. Predict: can `payload.title` be used before narrowing? Run it.

~~~sh
npm run stage:unknown
~~~

TypeScript refuses because we stopped lying. It is correctly saying: “You have not supplied evidence about this value’s shape.” This friction is useful. `any` would let the access through and remove that protection; `unknown` asks you to prove something first.

## Prove the outside shape in small steps

Before fields, establish the outer value. Predict the result of `typeof null`, and whether an array is also an object for `typeof` purposes. Then run:

~~~sh
npm run stage:object-shape
~~~

`typeof null` is `'object'`. Arrays also report `'object'`. Therefore this alone is not evidence for an Issue-shaped record:

~~~ts
typeof value === 'object'
~~~

The small helper in `src/parser.ts` establishes the useful fact instead: non-null object and not an array.

~~~ts
function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}
~~~

This is not a validation framework. It just establishes the first honest step before inspecting fields.

### A finite domain needs runtime evidence too

The TypeScript type says:

~~~ts
type IssueStatus = 'backlog' | 'todo' | 'in_progress' | 'done';
~~~

At runtime, an incoming status is only an unknown value. Even after `typeof status === 'string'`, it might be `'banana'`. Inspect `isIssueStatus` in `src/parser.ts`, then test it mentally with `'todo'`, `'banana'`, and `42`.

~~~ts
function isIssueStatus(value: unknown): value is IssueStatus
~~~

This is FS02.3’s custom predicate in application work. The annotation lets TypeScript narrow after a `true` result. It does not make the predicate honest; its runtime membership check does. Recall the lying-predicate experiment: a function that simply returns `true` can still claim this annotation.

## Guard or parser?

~~~text
GUARD                         PARSER
unknown                       unknown
  ↓ runtime checks              ↓ runtime checks
true / false                  trusted value or explicit failure
  ↓                            ↓
caller narrows                caller receives a usable Issue
~~~

A guard is ideal for a small question. A parser is valuable at an application boundary because validation is centralized, a successful caller receives a useful domain value, and failure can explain what was wrong.

`parseIssue` is the boundary in this lab. It checks every field promised by `Issue`, then reconstructs a new object:

~~~ts
return { id, title, status, description };
~~~

That visible reconstruction matters. Do not validate one or two properties and finish with `return value as Issue`; the remaining fields would still be unproven.

## Try it

**Prediction:** `isRecord` exists specifically because `typeof null === 'object'` and arrays
also report `'object'`. Before running anything, predict the three results of calling
`isRecord(null)`, `isRecord([1, 2, 3])`, and `isRecord({ id: 1 })`.

**Run / inspect:**

```js
function isRecord(value) {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

console.log(isRecord(null));
console.log(isRecord([1, 2, 3]));
console.log(isRecord({ id: 1 }));
```

**What happened:** `false`, `false`, `true`.

**Why:** each of `isRecord`'s three conditions is load-bearing, not defensive padding. Drop
`value !== null` and `isRecord(null)` becomes `true`, because `typeof null` really is the
string `'object'` — one of JavaScript's oldest surprises. Drop `!Array.isArray(value)` and
`isRecord([1, 2, 3])` becomes `true` too, because an array genuinely is an object by
`typeof`'s accounting. Neither omission would be visible from reading `parseIssue`'s
happy-path output; both would let a JSON response shaped as `null` or as a bare array reach
field access code that assumes an `Issue`-like record exists. This is the same "prove it,
do not assume it" discipline the rest of the lesson applies to individual fields, applied
one level earlier, to the shape those fields are supposed to live inside.

## Focused exercise — establish the Issue trust boundary

**Mode: self-reported practice using TypeScript and Node. This exercise is not automatically verified by the course platform. Your evidence is the commands and outcomes below.**

Work through one evolving boundary in the supplied lab.

1. **Predict the unsafe assertion.** In `src/unsafe.ts`, answer the four questions above. Run `npm run typecheck`, then `npm run unsafe`. Preserve the failed runtime run as evidence; do not “fix” it with a safer assertion.
2. **Restore uncertainty.** Run `npm run stage:unknown`. Explain why the diagnostic is an improvement, then return to normal code where parsed external data is deliberately held as `unknown`.
3. **Check the outer shape.** Run `npm run stage:object-shape`. Explain why null and arrays mean `typeof value === 'object'` is insufficient. Keep an `isRecord`-sized check, not a generic validation system.
4. **Build evidence property by property.** In `parseIssue`, establish a finite number id, string title, allowed status, and string-or-null description. Do not use `any`, `as Issue`, `as unknown as Issue`, `!`, fixture identity checks, or JSON-string comparison.
5. **Parse, do not merely claim.** Make `parseIssue(value: unknown): Issue` either return a reconstructed Issue or throw a clear error naming the failed field/reason. If it returns `Issue`, it must have established every required property.
6. **Test the boundary.** Run `npm test`. It must accept the valid fixture and reject: string id, null title, unknown status, missing required title, array, null, and numeric description. Confirm that a valid null description is accepted.
7. **Use trust normally.** Run `npm run run`. After `parseIssue` returns, `issueLabel` receives an ordinary typed value with no repeated assertions or checks.
8. **Name the line.** Answer: at exactly which line/boundary did this runtime value become trustworthy enough to call an Issue? The intended answer is the successful return from `parseIssue`, after its runtime checks—not `JSON.parse`, not `as`, and not the type declaration.

### Hints

<details>
<summary>Hint 1 — object shape</summary>

Before checking properties, what do you actually know about the outer value?
</details>

<details>
<summary>Hint 2 — object shape</summary>

Remember that `typeof null` is surprising and arrays are also objects. Establish a non-null, non-array record before field checks.
</details>

<details>
<summary>Hint 3 — fields and status</summary>

Write down the runtime evidence required for every `Issue` property. Validate one at a time. A status needs membership in allowed runtime values, not just `typeof status === 'string'`.
</details>

<details>
<summary>Hint 4 — parser shape</summary>

Put `unknown` on one side of one function and `Issue` on the other. Validate first, then return a new object from the validated local variables.
</details>

<details>
<summary>Reference explanation — reveal after an honest attempt</summary>

The unsafe assertion did not validate because assertions are erased with the rest of TypeScript’s types. Assigning parsed data to `unknown` made the missing evidence visible to the compiler. JSON syntax only says parsing succeeded; it does not say the values satisfy an Issue contract.

The parser earns trust from runtime checks: a non-null, non-array object; a finite numeric id; string title; status among the actual allowed values; and description either string or null. Reconstructing `{ id, title, status, description }` makes it clear that every returned field was checked. The parser is the one deliberate boundary: before it, data is unknown; after a successful return, ordinary application code can use Issue.

`tsc` proves source-level relationships in TypeScript’s model. The runtime test proves that actual fixture values passed or failed the checks. Frontend parsing protects frontend assumptions; it does not replace the backend validation and database constraints that later protect DALT.
</details>

## Common mistakes

Every one of these is a shortcut that looks like a check and is a claim. None establishes the `Issue` contract:

1. `return value as Issue` — no runtime proof.
2. `return value as unknown as Issue` — still no runtime proof.
3. `if (value) return value as Issue` — truthiness proves almost nothing about shape.
4. `if (typeof value === 'object') return value as Issue` — misses fields and types; null and arrays complicate it too.
5. `function isIssue(value: unknown): value is Issue { return true }` — a predicate annotation can lie.
6. A client function annotated `Promise<Issue[]>` — a return annotation does not validate raw runtime data it acquired.

The last case becomes concrete in Part 04. We are deliberately not building HTTP or a client architecture here.

Two more worth naming, because they are the ones that survive review:

**A guard whose body is weaker than its signature.** Answer to question 3: the compiler does **not** complain. `value is Issue` is a promise you make to it, and it believes you. That makes a sloppy guard more dangerous than a bare `as`, because it reads as diligence. Severity: high.

**`typeof value === 'object'`.** Answer to question 2: you may still be holding `null` or an array. Both are objects to `typeof`. This is why `isRecord` exists.

## When this goes wrong

When a typed path fails in reality, use this protocol:

1. Where did the value come from?
2. Did it cross a runtime boundary, or was it created entirely in trusted TypeScript code?
3. What does TypeScript currently believe, and why?
4. Is that belief runtime evidence or an assertion/generic annotation?
5. What does the raw value actually contain? Is its JSON valid but its domain meaning invalid?
6. Which property first violates the contract?
7. Where should the one deliberate trust boundary live?
8. What checks establish every field promised by the return type?
9. Do malformed fixtures fail and valid input succeed?
10. After parsing, can normal code use the result without `any`, `as`, or `!`?

## Transfer the boundary

Would a TypeScript declaration alone prove actual values from HTTP responses, JSON files, localStorage, user input after parsing, third-party libraries, messages/events, or database/API data crossing a process boundary? No.

One small configuration example: a deployment may declare `VITE_API_URL: string` to TypeScript. Does that prove the deployed environment supplied a present, well-formed URL? No. The declaration describes what code expects; deployment configuration is still runtime reality. Configuration design belongs later, but the trust model already applies.

A schema library can later reduce repetitive parser code. What would it replace? The real runtime validation and parsing work you just performed—not a magical compile-time proof. No library is installed in this lesson.

## Closed-book checkpoint

Answer before opening the reveal.

1. Why can `as Issue` typecheck when the runtime value is malformed?
2. Does a type assertion execute validation code?
3. What is the difference between valid JSON and valid application data?
4. Why is `unknown` safer than `any` at an external boundary?
5. What runtime evidence is needed before treating unknown as an object with fields?
6. Why is `typeof value === 'object'` insufficient alone?
7. Why can a custom type predicate lie?
8. What is the difference between a guard and a parser?
9. What must a function returning `Issue` have established about a runtime value?
10. A deployment declares `VITE_API_URL: string`. Does that guarantee a valid deployed URL? Why or why not?

<details>
<summary>Checkpoint answers</summary>

1. Assertions change TypeScript’s static view; the compiler cannot inspect future/external runtime data.
2. No. Assertions emit no validation and do not throw for a shape mismatch.
3. JSON validity is syntax; application validity means the parsed values satisfy the domain contract.
4. `unknown` blocks property use until evidence narrows it; `any` opts out of that protection.
5. At minimum, establish a non-null, non-array object/record before checking each required property’s actual value.
6. Both `null` and arrays report `'object'`, and ordinary objects may still lack valid required fields.
7. The predicate annotation is only a static promise; its JavaScript implementation supplies—or fails to supply—the evidence.
8. A guard returns true/false so a caller can narrow. A parser centralizes checks and returns a trusted value or explicit failure.
9. Every property promised by `Issue`, with runtime evidence for its required shape and allowed domain values.
10. No. It documents the code’s expectation; deployment values may be absent, malformed, or wrong at runtime.
</details>

## The Part 02 conclusion

~~~text
FS02.1  STATIC MODEL       What can the compiler know?
FS02.2  DOMAIN MODEL       What values should exist?
FS02.3  CONTROL FLOW       What do we know here?
FS02.4  RELATIONSHIPS      How do types flow through reusable code?
FS02.5  RUNTIME REALITY    What happens outside the type system?
~~~

Soon the value will actually travel:

~~~text
React → fetch → DALT → JSON
                    ↓
       frontend runtime boundary → validated typed value
~~~

The browser may declare `type Issue = ...`; the PHP backend has its own validation, database constraints, and response contract. TypeScript cannot inspect the PHP program through the network. Part 04 will make that boundary real. B02 remains the unfinished Part 02 milestone; it is not implemented or unlocked by this lesson.

## In the project

This is the last piece of **B02 — Type the future application**, and the one that makes the rest of it worth having. B02's parser is the seam Part 04 plugs a real server into: `fetch` returns `unknown`, the parser turns it into an `Issue`, and every component downstream works with a value that was actually established.

Answer to question 4: **yes, the server still validates.** Everything you wrote here runs in a browser the user controls completely — they can edit it, disable it, or send requests that never touch it. Frontend parsing protects *your frontend's assumptions* and gives fast, specific feedback. It is not a security boundary and never becomes one. Part 05's DALT validation and database constraints, and Part 06's authorization, are the boundary that counts, and they trust nothing that arrives over the network.

Two distrust layers, two different jobs. Knowing which is which is the point of this lesson.

## Resources

### Read

- [TypeScript Handbook: Narrowing — type predicates](https://www.typescriptlang.org/docs/handbook/2/narrowing.html#using-type-predicates) — what `value is Issue` promises, and who is responsible for it.
- [MDN: `Response.json()`](https://developer.mozilla.org/en-US/docs/Web/API/Response/json) — note what its return type does and does not tell you.

### Go deeper

- [TypeScript Handbook: Type Assertions](https://www.typescriptlang.org/docs/handbook/2/everyday-types.html#type-assertions) — read it as documentation of a deliberate escape hatch.
- [MDN: `JSON.parse()`](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/parse) — valid JSON and a valid domain value are different questions.

### Reference

- [TypeScript Handbook: `unknown`](https://www.typescriptlang.org/docs/handbook/2/functions.html#unknown)

## You are done when

- [ ] I ran the unsafe assertion, watched it fail at runtime with a clean typecheck, and kept that evidence instead of patching it away.
- [ ] I can state where TypeScript's knowledge stops, in one sentence.
- [ ] External data in my lab is held as `unknown` until something proves otherwise.
- [ ] My `isRecord` check excludes `null` and arrays, and I can say why both needed excluding.
- [ ] `parseIssue` either returns a reconstructed `Issue` or throws naming the failed field — and every promised property was actually established.
- [ ] `npm test` passes: the valid fixture is accepted, and string id, null title, unknown status, missing title, array, null and numeric description are all rejected.
- [ ] After parsing, downstream code uses the value with no `as`, `any`, or `!`.
- [ ] I can explain why the server must still validate everything.
- [ ] I attempted the closed-book checkpoint without notes.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/TYPESCRIPT_HANDBOOK.md`; `docs/dalt-fullstack/sources/FSO_TYPESCRIPT.md`
- Official sources: TypeScript Handbook — Narrowing (type predicates), Type Assertions, `unknown`; MDN — `Response.json()`, `JSON.parse()`
- Versions: TypeScript 5.9.3, Node 25 (CR-08 pinned toolchain)
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/course/fullstack/typescript-runtime-boundaries-lab/starter/**`
- Curriculum authority: `CURRICULUM.md` §12 FS02.5 — recorded as a load-bearing lesson; the required outcome is that external data is untrusted until proven
- Laravel bridge: deferred to Part 05, where server-side validation is the honest comparison
