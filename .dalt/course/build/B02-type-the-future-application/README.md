> **What exists when you finish:** a typed domain model of the Team Issue Tracker —
> entity, create and update contracts, a reusable request state, and a parser that
> turns untrusted JSON into a trusted `Issue` or an explicit failure.

## What you are building

The types the next nine parts are built on, and one function that earns them.

```text
Issue, UserSummary, IssueStatus, Priority     the entity and its finite values
CreateIssue, UpdateIssue                      what a caller may send, and when
RequestState<T>                               idle | loading | success | error
parseIssue(value: unknown): Issue             the trust boundary
```

No React, no HTTP, no database. A TypeScript program you run with `node`, holding a
model precise enough that Part 05 can turn it into PostgreSQL columns without
renegotiating what an issue is.

Then you change one field and repair the fallout — because a model that has never
survived a change has not been tested.

## Why this milestone exists

Because this model outlives every other artifact in the course.

The components in Part 03 take these types as props. The request bodies in Part 04
are these types. The tables and constraints in Part 05 are these types with a
different syntax. The authorization checks in Part 06 read these fields. If
`status` is `string` here, then every one of those layers inherits a field that can
hold `'Done'`, `'don'`, and `''`, and each of them has to defend against it
separately — which means one of them will not.

And `parseIssue` is the lesson the whole course is organised around. A green
typecheck over `await response.json() as Issue` is the false green check in its
purest form: nothing ran, nothing was verified, and the program is one malformed
response from a crash. You are going to write the honest version once, by hand,
before a library ever offers to do it for you.

## Before you start

All five Part 02 lessons complete. Node and npm.

```sh
mkdir -p .dalt/workspace
cp -R .dalt/course/build/B02-type-the-future-application/starter .dalt/workspace/b02-future-application
cd .dalt/workspace/b02-future-application
npm ci
npx tsc --version    # 5.9.3
```

**The starter deliberately does not typecheck.** That is the first stage, not a
broken copy. Reset any time with `rm -rf .dalt/workspace/b02-future-application` and
copy again.

Two commands are your evidence throughout:

| Command | What it proves |
|---|---|
| `npm run typecheck` | your declared relationships agree with each other |
| `npm run test` | actual values passed or failed actual checks at runtime |

They answer different questions and neither substitutes for the other. That is the
whole of Part 02 in one table.

---

## Stage 1 — Complete the model

Finish the types in `src/model.ts` so the program typechecks.

**Working looks like:** `npm run typecheck` exits clean, `npm run run` prints the
sample issues.

**Check it yourself:** for every field, say out loud which of the three it is —
required, optional, or nullable — and why. If you cannot justify one, it is
guesswork, and guesswork here becomes a nullable column in Part 05 that nobody can
explain.

Two specifics to get right:

- `status` and `priority` are finite unions of literals. Not `string`.
- `description` is `string | null` — always present, `null` meaning deliberately
  empty. Not optional. There is a difference between "we did not send this field"
  and "this issue has no description", and Part 04's partial updates will make you
  care about it.

## Stage 2 — Separate entity, create and update

An `Issue` that exists has an `id` and a `status`. A create request has neither —
the server assigns them. An update request has an id and *any subset* of the rest.

Write three types, not one type used three ways.

**Working looks like:** three named contracts, and the compiler rejects an object
that mixes them up.

**Check it yourself:** try to pass a `CreateIssue` where an `Issue` is expected. It
must fail. If it compiles, you modelled create as "an issue with optional fields",
which lets a caller invent an id.

> Resist `Partial<Issue>` for the update type at first. Write the fields out, see
> what is genuinely optional and what must always be present, *then* decide whether a
> derived type says the same thing more clearly. Deriving is good; deriving before
> you know what you meant is how a model becomes unreadable.

## Stage 3 — One reusable state, four possibilities

Write `RequestState<T>` as a discriminated union covering idle, loading, success and
error. Success carries a `T`; error carries something a human can read.

**Working looks like:** a `switch` over the discriminant narrows correctly in every
branch, and you can access `.data` only in the success branch.

**Check it yourself:** add a fifth member to the union and run typecheck. Every
exhaustive `switch` must complain. If one does not, it has a `default` that is
swallowing the new case — which means in Part 04 you will add a state and silently
render nothing for it.

This is the type Part 04 fills with real fetches and Part 08 hands to TanStack
Query. Writing it yourself now is why you will recognise what the library is doing.

## Stage 4 — Change the model and repair the callers

The requirement changed. `assigneeId?: number` becomes `assignee: UserSummary | null`
— an issue always has an assignee field, and `null` means deliberately unassigned.

Make the change in `src/model.ts` **first**. Run `npm run typecheck` **before**
touching anything else.

**Working looks like:** a wave of errors. Read them as a list. They are not damage —
they are every place in the program that assumed the old shape, located for free.

**Check it yourself, and this is the discipline of the whole milestone:** at each
error, decide whether *the caller* is wrong or *the contract* is wrong, then fix
that. Do not widen a type to make a diagnostic stop. `assignee: UserSummary | null |
undefined` will make everything compile and will have deleted the information the
change was carrying.

When typecheck is clean again, `npm run run` must still work.

## Stage 5 — The trust boundary

Write `parseIssue(value: unknown): Issue` in `src/parser.ts`. It either returns a
fully established `Issue` or throws an error naming the field and the reason.

The parameter is `unknown` and stays `unknown` until you have proved otherwise. No
`as`. No `any`. No predicate whose body checks less than its signature claims.

**Working looks like:** `npm run test` passes — the valid fixture is accepted, and
every malformed fixture is rejected: string id, `null` title, unknown status, missing
title, an array, `null` itself, a numeric description. A `null` description is
accepted, because that one is valid.

**Check it yourself:**

- Every property in the return type has a runtime check behind it. Walk the type and
  the function side by side; if `Issue` promises five fields and the parser checks
  four, it is lying about the fifth.
- `typeof value === 'object'` is not enough — `null` and arrays both pass it. Your
  `isRecord` must exclude both, and you should be able to say why each needed
  excluding.
- After `parseIssue` returns, downstream code uses the value with no `as`, no `!`,
  no re-checking. If it still needs a guard, the parser did not finish its job.

Then try to fool it. Write a fixture designed to pass a sloppy parser — an object with
the right keys and wrong value types. It must fail. A parser that only checks key
presence is a shape check wearing a validator's name.

---

## Decisions you have to make

- **`assignee: UserSummary | null` versus optional.** You are told which to use here,
  but be able to argue it. When does an API mean "absent" and when does it mean
  "explicitly nobody"? Part 04's `UpdateIssue` needs both meanings and cannot fake
  either.
- **Parser throws or returns a result union?** Throwing is simpler and matches this
  starter's tests. Returning `{ ok: true, value } | { ok: false, error }` composes
  better and forces the caller to handle failure. Pick one, write down why, and be
  consistent.
- **How specific should the error be?** "Invalid issue" is useless at 2am.
  `Issue.status: expected one of todo|in_progress|done, received "banana"` is
  actionable. There is a cost — error strings are a surface you maintain.
- **Derived types or written-out types?** `Omit<Issue, 'id' | 'status'>` versus
  spelling `CreateIssue` out. Deriving propagates changes; writing out reads plainly.
  Decide per type, not by rule.

## Acceptance criteria

Read these against what you actually built. Nothing here is checked automatically.

- [ ] `npm run typecheck` exits clean.
- [ ] `npm run test` passes, including every malformed fixture and the valid `null`
      description.
- [ ] `npm run run` prints the sample program.
- [ ] Every finite field is a literal union; there is no `status: string`.
- [ ] `Issue`, `CreateIssue` and `UpdateIssue` are three contracts, and the compiler
      rejects using one where another is required.
- [ ] `RequestState<T>` narrows in a `switch`, and adding a member breaks every
      exhaustive check.
- [ ] I made the assignee change before repairing callers, and repaired each by
      deciding caller-versus-contract — not by widening a type.
- [ ] `parseIssue` takes `unknown`, contains no `as` or `any`, and has a runtime check
      behind every property its return type promises.
- [ ] I wrote a fixture designed to fool a sloppy parser, and mine rejected it.
- [ ] I can state what `typecheck` proved and what it did not.

## Prove it to yourself

Close the editor. In your notes:

1. Describe the entity, create and update contracts — and where `null` versus absence
   is deliberate.
2. What relationship does `RequestState<T>` preserve that four separate booleans
   would not?
3. What did the assignee change expose that reading the code would not have?
4. Where exactly is the runtime trust boundary in your program?
5. `npm run typecheck` is green. Name something that could still be wrong.
6. Why is a type predicate more dangerous than a bare `as` when its body is weak?

## What this unlocks

Part 03 renders these types. `Issue` becomes a component's props, `CreateIssue`
becomes a form's draft, `RequestState<T>` waits for Part 04.

And in Part 04 `parseIssue` stops being an exercise: a real DALT response arrives as
`unknown`, and this function is the only thing standing between it and every
assumption your interface makes.
