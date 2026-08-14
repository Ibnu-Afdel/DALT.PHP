# FS02.2 — Modeling application data

Lesson ID: FS02.2  
Title: Modeling application data  
Part: 02 — TypeScript foundations  
Order: 2  
Status: Published  
Estimated effort: 75–100 minutes  
Difficulty: Foundation  
Prerequisites: FS02.1 — The TypeScript mental model  
Project milestone: B02 — Type the future application  
Primary source dossier: TYPESCRIPT_HANDBOOK.md; FSO_TYPESCRIPT.md  
Last reviewed: 2026-08-14

## Start with a model, not syntax

FS02.1 established that TypeScript reasons about source before JavaScript runs. Now give it something useful to reason about: the states our application allows.

Set up this course-owned lab. It is not the future Issue Tracker.

~~~sh
mkdir -p .dalt/workspace
cp -R .dalt/course/fullstack/typescript-modeling-lab/starter .dalt/workspace/fs02-2-typescript-modeling-lab
cd .dalt/workspace/fs02-2-typescript-modeling-lab
npm ci
~~~

Open src/modeling.ts. The opening object-shaped data carries assumptions: an identifier is a number, a title is text, a status means something, and a person may or may not be assigned. JavaScript alone accepts many values that would make those assumptions meaningless.

The work in this lesson is a repeated decision:

~~~text
real application concept
        ↓
decide which states are valid
        ↓
describe those states with TypeScript
        ↓
let the compiler compare code with that model
~~~

Types are not decoration on JavaScript. A good type is a decision about the application.

## Give an issue a required shape

An issue always needs an id, title, and status. In src/modeling.ts, predict what happens if one of those properties is omitted from an Issue value, then run:

~~~sh
npm run stage:required
~~~

The diagnostic identifies a missing required property. Read it as a model question: the value lacks title, while the required object shape promises title.

Now add a temporary Issue value in src/modeling.ts with id, title, and status, run this command, and then remove the temporary value:

~~~sh
npm run typecheck
~~~

The existing Issue type is a type alias. A type alias can name an object shape, a union, or another type expression. Its point here is not the keyword; it is the contract.

Primitive fields describe the simplest decisions:

- title: string means title text;
- id: number means numeric identity;
- isPinned: boolean would mean one of two states.

Do not add annotations merely because TypeScript has syntax. Add them where the application needs a stable contract.

### Try one primitive decision

Imagine the triage screen needs to know whether an issue stays at the top. Add isPinned: boolean as a required Issue field, add isPinned: false to triageIssue, and run npm run typecheck. Then predict whether true also works and whether 1 does. Change the value, inspect the feedback, and leave the model with an honest boolean.

### Model a collection by its members

The issue also needs a list of watcher ids. Add watcherIds: number[] to Issue and watcherIds: [4, 8] to triageIssue. Run npm run typecheck. The array decision is not “some list”: it is a list whose every member is a numeric user id. Try one non-number long enough to see the diagnostic, then repair it.

## Optional is not the same as empty

The current model says description? is optional. Before running anything, predict these three values:

~~~ts
{}
{ description: 'Explain the failure.' }
{ description: undefined }
~~~

Now run:

~~~sh
npm run stage:optional
~~~

This lab deliberately enables exactOptionalPropertyTypes. Under that rule, description?: string means the property may be absent; when it is present, it must be a string. So omission and an actual undefined value are different. The null example also fails: null is not a string.

This is not a universal style rule. It is a project choice made visible in tsconfig.json. The useful modeling question is always: what does the contract mean?

Add this allowed optional description to triageIssue, typecheck it, then remove it:

~~~ts
description: 'Search results need a clearer empty state.',
~~~

~~~sh
npm run typecheck
~~~

Now compare that with an assignee. Suppose every issue always has an assignee field, but an issue can have nobody assigned. That model is not optional:

~~~ts
assignee: UserSummary | null
~~~

Absent means the property is not there. Undefined can mean a present value is undefined, depending on the model and compiler rules. Null can deliberately mean “this field exists, and its application value is no person.” Neither is automatically better; the domain contract decides.

## Let an invariant resist reassignment

An issue id should not be casually reassigned. Predict the result, then run:

~~~sh
npm run stage:readonly
~~~

The checker rejects assignment through a readonly property. Add readonly to the id field of one object type you write in this lesson, then try the assignment yourself and remove it after reading the diagnostic.

~~~ts
issue.id = 99
~~~

readonly is a TypeScript usage restriction. It is not Object.freeze(), does not make nested values deeply immutable, and does not add runtime protection. JavaScript still receives ordinary objects after type erasure.

## Restrict a real domain

The starter says IssueStatus is string. Ask what that permits: done, banana, an empty string, and values the application never designed.

Replace its definition in src/modeling.ts with this finite domain:

~~~ts
export type IssueStatus =
  | 'backlog'
  | 'todo'
  | 'in_progress'
  | 'done';
~~~

Run typecheck. Then predict and run the deliberately bad source:

~~~sh
npm run stage:status
~~~

A literal union is useful here because the product has a finite status vocabulary. The narrower type removes meaningless states from the program. It is not prettier syntax; it records a domain decision.

## Use both object declarations

The starter uses an interface for UserSummary and a type alias for Issue. Both can describe ordinary object shapes. Neither becomes a runtime constructor, object, or class.

At this stage, use the declaration that makes the local contract clear:

- type is useful for object shapes and for the IssueStatus union;
- interface naturally describes an object contract and has interface-specific extension and merging behavior.

This is not a style war. Changing an equivalent interface to a type alias does not change emitted JavaScript. FS02.1 already showed why: both declarations are erased.

## Reuse a shape you already have

FS02.1 introduced structural typing. Use it now. src/modeling.ts has a richerIssue with id, title, status, and priority, then assigns it to the smaller IssueSummary shape.

Before running, predict whether the extra properties prevent compatibility:

~~~sh
npm run stage:structural
~~~

It succeeds. TypeScript normally checks whether the required structure exists; it does not require named membership. Compare required members before inventing a conversion.

## Model related data, not just flat fields

An assignee is a small related object, not merely a string label. Predict what is wrong in each source example, then run:

~~~sh
npm run stage:nested
~~~

The first nested value has a string where UserSummary requires a numeric id. The second has no name. The compiler is comparing the nested contract too.

Do not turn that observation into runtime validation. These are source values TypeScript can see. Later we will treat values that arrive from outside the program as a separate problem.

## Focused exercise — evolve one honest issue model

**Mode: self-reported practice using your editor and tsc. This exercise is not automatically verified.**

Open src/exercise.ts. Its initial model is deliberately small and should typecheck:

~~~sh
npm run typecheck
~~~

Read the initial requirement before changing anything:

- every issue has a readonly numeric id and a title;
- description may be absent;
- status is one of the four stated literal values;
- an assignee, when present, is represented only by a numeric id.

Now the requirement changes. The application needs a displayable assignee name, and every Issue must contain an assignee field. An unassigned issue deliberately uses null.

Do this in order:

1. Predict which current Issue values will stop matching.
2. Change the model first: introduce UserSummary and replace assigneeId? with assignee: UserSummary | null.
3. Run npm run typecheck before repairing the values.
4. For every diagnostic, identify the old assumption it exposed: missing field, old numeric representation, or incorrect nullability.
5. Repair firstIssue with a complete UserSummary and unassignedIssue with assignee: null.
6. Run npm run typecheck again, then npm run run.
7. Add a small richer object and assign it to a smaller summary shape to demonstrate structural compatibility. Do not weaken the new assignee contract to make the old values pass.

Do not answer an honest model change by making everything optional, widening every property, or asserting a value into place. Ask which side reflects the requirement: is the caller old, or is the model false?

### Hints

<details>
<summary>Hint 1 — begin with the application decision</summary>

The new requirement says every issue carries assignee information. It also gives unassigned a deliberate meaning.
</details>

<details>
<summary>Hint 2 — separate the three absence ideas</summary>

The old property could be absent. The new property must exist. Its value is either a complete person summary or null.
</details>

<details>
<summary>Hint 3 — choose the TypeScript shapes</summary>

Define an interface or type for UserSummary, then use a required assignee property whose type is UserSummary | null.
</details>

<details>
<summary>Hint 4 — repair values coherently</summary>

The assigned example needs an object with numeric id and name. The unassigned example needs assignee: null, not a missing property.
</details>

<details>
<summary>Reference explanation — reveal after an honest attempt</summary>

One coherent repair defines UserSummary with readonly id and name, then gives InitialIssue a required assignee: UserSummary | null property. The example with assigneeId: 7 becomes an object such as { id: 7, name: 'Amina' }; the formerly missing field becomes assignee: null.

The diagnostics are useful because they identify places still expressing the old model. Making assignee optional again would make the code green while contradicting the new requirement.
</details>

## Read compiler errors as model feedback

When typecheck fails, work through this protocol:

1. What value/type do I currently have?
2. What type is required?
3. Which property differs?
4. Is the property missing?
5. Is optional, undefined, or null modeled differently?
6. Is a literal outside the allowed domain?
7. Did the requirement change?
8. Is the caller wrong, or is the model wrong?
9. Am I weakening the model just to remove useful feedback?

Common mistakes are symptoms of skipping those questions: using string for a finite domain, treating optional as nullable, assuming readonly freezes JavaScript, expecting named types to be nominal at runtime, over-modeling with dozens of tiny types, or using a tuple when named object fields would communicate better.

## Closed-book checkpoint

Close the lesson, then answer before revealing the comparison answers.

1. An Issue has description?: string. What is different about omitting description and assigning null?
2. In this lab, why does description: undefined fail for description?: string?
3. What does readonly protect, and what does it not do at JavaScript runtime?
4. Why can status: string be a weaker model than a literal union?
5. At ordinary object-shape depth, what practical job can both type and interface do?
6. Why can a richer issue satisfy IssueSummary?
7. After an assignee model changes, what do the resulting compiler errors tell you?
8. A Comment always contains an author field, but comments from deleted users deliberately have no active author. Would you choose author?: UserSummary or author: UserSummary | null? What different meaning does each express?

<details>
<summary>Reveal comparison answers</summary>

1. Omission means the property is absent; null is a deliberate value only if the type allows it.
2. exactOptionalPropertyTypes makes the optional property absent-or-string, not present-with-undefined.
3. It prevents typed source from assigning through that property. It does not freeze the runtime object or deeply freeze nested values.
4. string permits values the application has never designed; a literal union records the finite valid states.
5. Both can describe ordinary object contracts. The choice does not create different JavaScript runtime values.
6. Structural typing checks that the required id and title exist; extra members do not normally block that assignment.
7. They reveal source values and callers that still assume the earlier contract.
8. Use author: UserSummary | null when the field always exists and null deliberately means no active author. Optional means the property itself may be absent.
</details>

## Carry this model forward

Soon these decisions will appear as Issue, Project, User, component props, state, forms, and application data. Part 03 will introduce React, but you should not have to learn object modeling and React props at the same time.

Before marking completion, make sure you predicted the diagnostics, changed the model, observed required/optional/null distinctions, tried readonly and literal restrictions, repaired the exercise after its model change, and attempted the checkpoint from memory.

FS02.3 remains unavailable. It will deepen reasoning about unions and control flow; this lesson is only about deciding which application states are valid.
