> **What exists when you finish:** a focused final test portfolio that proves the issue tracker’s important behavior and security boundaries without inflating count through duplicate or implementation-coupled tests.

## What you are building

Review the tests around the C02 workflow and C03 failure matrix. Keep, improve, add, or remove tests so each important boundary has direct evidence: backend API behavior; authentication; authorization; a critical transaction; role-based RLS tenant isolation; frontend component/integration behavior; and a small number of high-value Playwright flows. Produce a short test portfolio record explaining what each layer proves, what it intentionally does not prove, and how to run it.

Tests are not a trophy case. A test that reads a component’s internal state, asserts an incidental CSS class, or duplicates an end-to-end flow without adding a boundary can make refactoring harder while proving little. Conversely, a security rule tested only through a convenient mocked handler is a green check nobody earned. Choose the smallest test that can falsify the important claim.

## Why this milestone exists

The course taught backend behavior tests, frontend behavior tests, transaction/concurrency evidence, and RLS proof in separate parts. A finished application needs a deliberately layered set rather than a pile created chronologically. The question is not “did we write tests?” but “what production-relevant mistake would this test catch, and does it run at the boundary where that mistake can occur?”

The portfolio also creates a maintenance contract. A new contributor should know which command exercises PHP behavior, which runs browser-facing React behavior, which uses a restricted database role, and which one or two flows justify their cost in a browser automation suite. Honest gaps are acceptable when documented; a pass label that overstates a mocked or owner-role check is not.

## Before you start

Finish C03. Run all existing checks and collect their real output. Read C01’s maps and C03’s failure matrix before deleting any test; a test’s value is defined by its claim, not its filename.

```sh
php artisan test
npm run typecheck
npm run lint
npm run test
npm run build
```

If Playwright is configured, use its documented project command and a local Compose-backed environment. Do not add Playwright merely to satisfy a checkbox; the capstone asks for a small flow only where the project already has that toolchain or a justified implementation from prior work. Do not run RLS proof as superuser, table owner, or a role with `BYPASSRLS`.

## Stage 1 — Inventory claims and current tests

Make a table with columns: product/security claim, failure it would catch, narrowest suitable layer, test file/command, environment/role, and known blind spot. Start with C02’s success contract and C03’s matrix. Include authentication, membership/ownership, validation, conflict/transaction rollback, search/list correctness, session behavior, and tenant isolation. Mark duplicates that prove exactly the same claim at the same layer.

**Working looks like:** every retained test can be connected to an observable behavior or boundary, and every important claim has an identified proof or honest gap.

**Check it yourself:** pick three existing tests at random and describe the defect each would catch. If you cannot, inspect or remove/rewrite it only after confirming another test does not depend on its setup.

## Stage 2 — Harden backend behavior and database proof

Test API results from the request boundary: valid authorized behavior, invalid input, missing authentication, wrong-workspace authorization, and an important conflict/constraint response. Assert status, safe response shape, and durable result—not private helper calls. For a multi-write operation, deliberately make one write fail and query/observe that the first did not persist. For the concurrency invariant, retain a deterministic proof of the losing request’s honest outcome.

Run RLS assertions through the restricted application role and the actual tenant-context lifecycle. Prove own-workspace allowed behavior plus reciprocal cross-workspace read, insert, update, and delete refusal as your policy semantics require. Application authorization tests remain necessary; RLS is defense in depth, not a replacement.

**Working looks like:** a broken ownership predicate, missing rollback, or permissive policy causes a test to fail for the stated reason.

**Check it yourself:** temporarily make one safe local mutation to a policy/predicate or use a known-broken fixture and watch the right test fail, then restore the genuine implementation. Never commit a deliberately weakened policy.

## Stage 3 — Harden frontend behavior

Use React Testing Library or the project’s established tools to exercise what a user can perceive: form labeling and submission, pending state, success render, server validation message, permission/error state, and cache refresh/invalidation after mutation. Mock transport at the boundary where appropriate, but do not mock your own component tree into proving its own implementation. A test should use roles, labels, text, and user actions where that expresses the contract better than internal details.

**Working looks like:** the UI tests fail if a user cannot complete the C02 flow or distinguish its important C03 failures, while harmless component restructuring remains possible.

**Check it yourself:** make a temporary local regression such as removing the error render or skipping invalidation; confirm the targeted test—not an unrelated snapshot—fails. Restore it before proceeding.

## Stage 4 — Choose one or two browser flows

Keep browser automation narrow and valuable. A good flow signs in as a real local user, performs the completed workflow, reloads or navigates to prove persistence, and observes a result only the full stack can produce. A second flow may prove a visible permission denial if it cannot be represented sufficiently below. Avoid reproducing every validation permutation in Playwright; that is slower duplication of API/component tests.

**Working looks like:** each browser flow crosses real browser, HTTP, application, and database boundaries and catches a defect the lower-layer suite could miss.

**Check it yourself:** run it twice from resettable local data. Confirm its setup does not depend on a manually edited browser store, an existing production account, or test ordering. Record timing/flakiness observations rather than hiding retries.

## Stage 5 — Remove noise and document execution

Remove or consolidate low-value duplicate tests only after their claim has a better proof. Document exact commands, services, role/environment requirements, fixture/reset behavior, and the portfolio’s claim map. Keep typecheck, lint, and build as separate confidence checks; they do not replace behavioral tests.

**Working looks like:** a maintainer can run the portfolio from a documented local state and understand why each layer exists.

**Check it yourself:** follow the record from a clean reset or another shell. Deliberately break one backend rule, one frontend render, and one RLS rule locally; confirm the intended suite detects each. Restore every experiment.

## Decisions you have to make

- Which claim needs a real request test versus a component test versus a browser flow?
- Which existing tests duplicate a claim without increasing confidence?
- Which transaction/concurrency proof is deterministic enough for a normal suite?
- Which database role and context setup make RLS testing meaningful?
- Which browser flow catches a genuine integration risk worth its setup cost?

## Acceptance criteria

Nothing here is checked automatically. Read every item against software you actually ran.

- [ ] A portfolio record maps important product/security claims to tests, commands, environments, and known blind spots.
- [ ] Backend request tests prove valid behavior, validation, authentication, authorization, and a meaningful conflict or constraint result.
- [ ] A critical transaction failure proves that no partial durable effect remains.
- [ ] RLS proof uses a restricted non-owner, non-superuser, no-BYPASSRLS role and checks both tenant directions.
- [ ] Frontend tests exercise user-visible success, pending, validation/error, and refreshed server-state behavior.
- [ ] One or two resettable browser flows cross the real full stack and prove a high-value integration claim.
- [ ] Duplicate, implementation-coupled, or misleading tests have been removed or justified.
- [ ] Documented commands run successfully from the stated local environment.

## Prove it to yourself

For each layer say what it proves and what it cannot prove. Explain why an owner-role RLS test can lie, why a snapshot is not a transaction proof, and why a Playwright pass does not replace direct authorization tests. Then choose one test and describe the plausible broken implementation that makes it fail.

## What this unlocks

You now have a compact regression net around the final workflow. C05 can change a database query with a correctness proof, C06 can compare mechanisms without treating framework names as verification, and C07 can freeze a result with commands that mean something.
