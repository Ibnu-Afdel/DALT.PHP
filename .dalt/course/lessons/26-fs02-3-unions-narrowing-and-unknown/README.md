# FS02.3 — Unions, narrowing and unknown

Lesson ID: FS02.3  
Title: Unions, narrowing and unknown  
Part: 02 — TypeScript foundations  
Order: 3  
Status: Published  
Estimated effort: 80–105 minutes  
Difficulty: Foundation  
Prerequisites: FS02.2 — Modeling application data  
Project milestone: B02 — Type the future application  
Primary source dossier: TYPESCRIPT_HANDBOOK.md; FSO_TYPESCRIPT.md  
Last reviewed: 2026-08-14

## Evidence changes what TypeScript knows

FS02.2 asked which values the application allows. This lesson asks what we know about one particular value at this line of code.

~~~sh
mkdir -p .dalt/workspace
cp -R .dalt/course/fullstack/typescript-narrowing-lab/starter .dalt/workspace/fs02-3-typescript-narrowing-lab
cd .dalt/workspace/fs02-3-typescript-narrowing-lab
npm ci
~~~

Open src/narrowing.ts. A union declares several possibilities:

~~~text
string | number
        ↓ runtime evidence: typeof value === 'string'
string
~~~

TypeScript narrows because JavaScript has established evidence, not because we asked it to trust us.

## Start with all possibilities

In src/stages/union.ts, predict which operations work on string | number: toUpperCase, multiplication, String(value), and equality checks. Run:

~~~sh
npm run stage:union
~~~

Only operations safe for every remaining possibility work before narrowing. String(value) and equality comparison are safe; string-only and number-only operations are not.

In normalizeIssueIdentifier, hover value before the branch, inside the number branch, and after the early return. Then change the branch to test typeof value === 'string', typecheck, and put it back.

~~~sh
npm run typecheck
~~~

The early return matters: after the number path returns, TypeScript knows the remaining path is string. It follows ordinary JavaScript control flow, not formal theory.

## Narrow null, undefined, and truthiness deliberately

FS02.2 distinguished absent, undefined, and null. Retrieve that model here. Predict what each establishes:

~~~ts
value === null
value !== undefined
value == null
~~~

The loose equality example is deliberately narrow: value == null is true for null or undefined, so its false branch excludes both. It is not a recommendation to use loose equality generally.

Now run:

~~~sh
npm run stage:truthiness
~~~

The code compiles, but labelCount(0) returns No count. Truthiness is runtime evidence, yet it may not express the domain rule: 0 and empty string can be meaningful valid values. Use explicit equality when you mean existence rather than truthiness.

## Narrow object alternatives

An IssueLookup can be one of two object shapes:

~~~ts
type IssueLookup =
  | { issueId: number }
  | { issueKey: string };
~~~

Before writing a branch, ask what runtime evidence distinguishes them. Add a temporary function using 'issueId' in value, hover value inside each branch, typecheck, then remove it. The in operator establishes which object shape has the relevant member.

For built-in runtime classes, instanceof can also be evidence:

~~~ts
catch (error: unknown) {
  if (error instanceof Error) console.log(error.message);
}
~~~

Error exists at runtime. An interface or type alias does not. Therefore value instanceof Issue cannot work when Issue is only a TypeScript declaration; FS02.1’s erasure rule still applies.

## Unknown asks for proof

Predict the two lines in src/stages/unknown.ts, then run:

~~~sh
npm run stage:unknown
~~~

any permits the operation and disables much of TypeScript’s protection for that value. unknown blocks the operation until a check proves enough. Neither word changes JavaScript runtime; unknown gives the checker useful work to do.

In describeUnknown, hover value before each condition and inside the string, number, null, and user-summary paths. Add a temporary object literal such as { id: 7, name: 'Amina' } to src/exercise.ts and run npm run run.

Use narrowing, not as, !, or any, to make an operation safe. A non-null assertion such as value!.name merely tells TypeScript to assume; it supplies no runtime evidence. Treat it as an escape hatch after asking whether control flow or the model should handle absence instead.

## Extract evidence into a small guard

Repeated checks can become a reusable type guard. In isUserSummary:

~~~ts
function isUserSummary(value: unknown): value is UserSummary
~~~

the predicate tells TypeScript what a successful runtime check establishes. Run the valid and invalid examples:

~~~sh
npm run stage:guard
~~~

Now temporarily replace the guard body with return true. Will TypeScript reject it as dishonest? No. A type predicate annotation is not automatic validation; TypeScript trusts the predicate’s claimed relationship. Its implementation must actually prove id and name.

## Make contradictory state hard to express

Consider a weak state model with isLoading, hasError, issues?, and error?. It permits loading and error simultaneously, success without data, and other states the application did not design.

Before reading the replacement, list which states you actually want. Then run:

~~~sh
npm run stage:state
~~~

The starter’s IssueLoadState instead describes alternatives:

~~~ts
type IssueLoadState =
  | { status: 'idle' }
  | { status: 'loading' }
  | { status: 'success'; issues: IssueSummary[] }
  | { status: 'error'; message: string };
~~~

status is a discriminant. Checking state.status narrows to a variant and makes only that variant’s data available. success without issues, error without message, and loading with success data fail because they are not valid modeled states. This improves typed source; untrusted runtime input can still be wrong.

## Exhaustiveness finds an old assumption

describeLoadState handles every state and ends with:

~~~ts
const exhaustive: never = state;
~~~

At that point nothing should remain if every variant was handled. never is only useful here as an exhaustiveness check; do not generalize it beyond this job.

Predict what happens when a refreshing state retains existing issues. Then run:

~~~sh
npm run stage:exhaustive
~~~

The new variant makes the existing handler incomplete. The compiler exposes the old assumption: it believed the listed cases were all possible. Add the refreshing member first, typecheck before changing the switch, then deliberately add its handler.

## Focused exercise — prove, model, then evolve

**Mode: self-reported practice using your editor, tsc, and Node. This exercise is not automatically verified.**

Begin with npm run typecheck and npm run run. Then work in src/narrowing.ts and src/exercise.ts:

1. For normalizeIssueIdentifier(value: unknown), change the contract so positive integer numbers and numeric strings become numbers; null, objects, and invalid strings return null. Use typeof and explicit checks—no any or blind assertion.
2. Use isUserSummary to accept one valid local object and reject one invalid object. Temporarily make its predicate return true; explain why the compiler cannot prove that lie.
3. Write down why the old multi-boolean load state permits contradictions. Keep the discriminated union and add one intentional impossible object literal to observe the diagnostic, then remove it.
4. Add refreshing with issues to IssueLoadState first. Run typecheck before changing describeLoadState. Read the never error, then handle refreshing deliberately.
5. Run npm run typecheck and npm run run after repair. Hover values before and inside at least two narrowing branches.

### Hints

<details>
<summary>Hint 1 — look for runtime evidence</summary>

Ask what JavaScript can establish about this value: typeof, explicit equality, in, or instanceof Error.
</details>

<details>
<summary>Hint 2 — do not weaken the model</summary>

unknown needs proof. A new state variant means the switch is old, not that the exhaustive check should disappear.
</details>

<details>
<summary>Hint 3 — choose a discriminant</summary>

One literal status field can identify each state. Put issues and message only on the variants that require them.
</details>

<details>
<summary>Hint 4 — small shape clue</summary>

A refreshing member needs status: 'refreshing' and issues: IssueSummary[]. Its switch case can report how many issues remain visible.
</details>

<details>
<summary>Reference explanation — reveal after an honest attempt</summary>

The normalizer first proves a number or string case, then rejects invalid values explicitly. The guard’s checks—not its predicate annotation—make a user summary trustworthy. A discriminated union moves data into the state where it is meaningful, so contradictory boolean combinations cannot be expressed. Adding refreshing must break the never check until the handler understands it.
</details>

## Debug the remaining possibilities

When a narrowing error appears, ask: What is declared here? What possibilities remain at this line? What runtime evidence exists? Is truthiness excluding 0 or empty string? Am I about to use as, !, or any to silence feedback? Did a predicate truly prove its claim? Did a new union member make a handler incomplete?

## Closed-book checkpoint

Answer before revealing the comparison answers.

1. What does string | number mean before narrowing?
2. Why can typeof change what TypeScript knows?
3. Why does an early return narrow the remaining path?
4. What protection does unknown preserve that any gives up?
5. Why can if (value) be wrong for a count where 0 is valid?
6. Why is value instanceof SomeInterface not generally possible?
7. Why can a custom predicate lie?
8. Why can a discriminated union be better than multiple booleans?
9. An upload can be idle, uploading, success with a URL, or error with a message. Why is that union safer than isUploading, isSuccess, url?, and error??

<details>
<summary>Reveal comparison answers</summary>

1. Either possibility may be present, so only shared-safe operations work.
2. JavaScript’s runtime test is evidence that lets the checker eliminate alternatives.
3. The returned branch cannot continue, leaving only the other possibility.
4. unknown requires proof before unsafe use; any opts out of much checking.
5. Truthiness treats 0 as false even when it is a real count.
6. TypeScript-only declarations are erased and are not runtime constructors.
7. TypeScript trusts the predicate annotation; its implementation is responsible for truth.
8. Variants place required data with the state that needs it and remove contradictory combinations.
9. Each allowed upload state has exactly its required data; invalid combinations are harder to express.
</details>

## Carry this forward

Later these patterns will describe request/loading states, authentication, operation results, issue activity variants, errors, and runtime boundaries. Part 03 adds React; the union itself should already feel familiar. FS02.4 and FS02.5 remain unavailable.
