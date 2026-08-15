# FS07.3 lab — Frontend testing

A small React + TypeScript issue screen with a test suite that is **deliberately failing on
the first run**. The failure is the lesson: five component tests cannot reach the component
they are testing, because the component imports its API client instead of receiving it.

Fixing that one import is the technique FS04.3 and B04 both deferred to Part 07.

## Set up

Copy the starter into your gitignored workspace. Never edit it in place — the copy is what
makes the exercise repeatable.

```sh
mkdir -p .dalt/workspace/fs07-frontend-testing
cp -R .dalt/course/fullstack/frontend-testing-lab/starter/. .dalt/workspace/fs07-frontend-testing/
cd .dalt/workspace/fs07-frontend-testing
npm install
```

## What you should see

```sh
npm run typecheck     # clean — the defect is not a type error
npm run test:parsers  # 4 passed — the cheapest level already works
npm run test          # 5 passed, 5 failed — this is the exercise
```

Read the failure before changing anything:

```text
Could not reach the server: TypeError: Failed to parse URL from /api/projects/PRJ-1/issues
```

That message is worth sitting with. The test wrapped `ProjectPage` in an `ApiProvider`
holding a fake that returns one issue and never touches the network. The component tried
the network anyway. **A provider only reaches a component that asks for it.**

Note also what `npm run typecheck` says: nothing. The defect is a wiring mistake, not a
type mistake, and the compiler has no opinion about which of two values of the same type
you chose. This is the same shape as the trap in B03 Stage 4.

## Stage 1 — Introduce the seam

In `src/ProjectPage.tsx`, replace the direct import with the context hook. Two lines, both
marked `STAGE 1`. Then run `npm run test` again: 10 passed.

Nothing else in the file changes, and `main.tsx` keeps working untouched — the context's
default value is the real client, so production wiring needs no provider.

## Stage 2 — Watch each test fail on purpose

A test you have never seen fail is a test you have no evidence about. Break each behaviour,
confirm the expected test goes red, then put it back:

```text
IssueList: return null for the empty case      → "shows the empty state" fails
ProjectPage: drop the trim() check             → "rejects a whitespace-only title" fails
CreateIssueForm: clear title on failure        → "keeps the draft on screen" fails
ProjectPage: swap the failed branch for []     → "distinguishes a failed request" fails
CreateIssueForm: <button> becomes <div>        → every test using getByRole fails
```

The last one is the argument for role queries in one line. A `getByTestId` suite stays
green while the button stops being a button for anyone using a keyboard or screen reader.

## Stage 3 — Add the two the lab does not ship

The suite covers the list, the empty state, a failed load, client-side validation, a
successful create and a rejected create. Add:

1. **A route test.** Render `MemoryRouter` with real route definitions, click a link to an
   issue, and assert the detail heading appears. You will need a small `IssuePage`.
2. **An authorization-sensitive control.** A `canDelete={false}` viewer must not see a
   Delete button — and remember the ordering rule: wait for a positive signal first, then
   assert the absence. Asserting it immediately after `render` passes for the wrong reason.

## What is worth stealing

`src/ProjectPage.test.tsx` shows the pattern to carry into your own project:

- `fakeApi(overrides)` — one typed factory, each test overriding only what it cares about.
- The fake is annotated `IssueApi`, so it cannot drift from what the real client returns.
- Unused operations **throw** rather than returning `undefined`, so an unexpected call
  names itself instead of failing somewhere confusing.
- `vi.fn()` only where the call itself is the contract — that `createIssue` receives the
  chosen `priority` and does not invent an `id`.

## Reset

```sh
cd .. && rm -rf fs07-frontend-testing
```

Then copy the starter again. The lab is disposable on purpose; your issue tracker is not.
