> **What exists when you finish:** one user-visible issue-tracker workflow that is coherent from browser action through authorization and PostgreSQL to an observable, tested result, with no temporary shortcut hidden on its critical path.

## What you are building

Choose one meaningful workflow already implied by your product and finish it across the existing stack. A good candidate is create issue → assign → comment → change status → search/filter, but choose a smaller contiguous slice if that is what your actual project supports. The result must be useful to a user, not a collection of endpoint demonstrations. Complete missing edges, replace placeholders, and remove temporary shortcuts only where they prevent this workflow from being genuinely end to end.

This is not permission to add a new framework, a dashboard, a generic abstraction, or an unrelated feature. The capstone measures integration: a request should move through the React feature, its state boundary, HTTP contract, DALT handler/middleware, validation, authorization, PostgreSQL constraints/transaction, response, and UI update in a way you can show and explain.

## Why this milestone exists

Earlier milestones made individual capabilities work. A project can still have a convincing issue list beside a half-real create form, server authorization that the UI never respects, a successful mutation that leaves stale data on screen, or a database write that bypasses the intended transaction. Finishing one complete workflow reveals these seams because the user encounters them as one action, not as chapters.

Scope is an engineering decision. A narrow, reliable workflow teaches more than an ambitious road map with seven unfinished branches. Use the C01 audit to identify the existing feature, route, tables, state owner, and known weak areas. Then make a written boundary: what the workflow includes, what it intentionally excludes, and why. That boundary prevents the common capstone failure of continuously adding “one more” capability.

## Before you start

Finish C01 and make a fresh branch or checkpoint in the learner project. Start services, apply migrations, seed representative data, and run the existing checks before changing anything. Record the baseline results in your own project notes.

```sh
docker compose up -d
php artisan test
npm run typecheck
npm run lint
npm run build
```

Choose a real user and workspace scenario. Identify the actor, their membership/role, input, expected database changes, response states, and rendered success state. Do not use administrator or database-owner access to make the happy path look correct. If your project needs a seed user, document a safe local-only setup rather than committing a password.

## Stage 1 — Define the workflow contract

Write a one-page workflow contract before implementation. State its starting screen/URL, actor and workspace, inputs, permitted transition, API request and response shape, database records changed, success UI, expected validation/permission/conflict outcomes, and completion evidence. Include a non-goal list. If “add comments” has no supporting schema or lesson-driven need in your project, do not invent it simply because it appears in the example chain.

**Working looks like:** another developer can tell what “complete” means without seeing your implementation, and can distinguish an intentional non-goal from a forgotten edge.

**Check it yourself:** trace the contract against C01’s route, feature, and schema maps. For every arrow, name the existing file/service/table it will use. Remove or revise an assertion when source inspection contradicts it.

```text
actor → input → request → authorize → validate → transaction/query → response → cache/state → render
```

## Stage 2 — Implement the server boundary deliberately

Make the server route/handler fulfill the contract. Authenticate before trusting an actor ID, resolve workspace membership before selecting tenant records, validate the input at the HTTP boundary, and let constraints reinforce invariants. Parameterize every query. If the operation has multiple durable effects, keep them inside the transaction boundary identified by Part 11; do not hold a transaction open across a remote request or a user wait.

Return a stable success shape and deliberate error shapes/statuses that the frontend can interpret. Avoid leaking exception text, schema details, or another tenant’s existence. A `404`, `403`, `409`, or validation response is a product/security decision; record which semantics your project uses and test it.

**Working looks like:** direct HTTP evidence shows a valid authorized request changes only the intended workspace data, while invalid and unauthorized requests do not produce partial writes.

**Check it yourself:** run the request outside the browser with a realistic local session/setup, then query the affected rows. Repeat with invalid input and a wrong-workspace identifier. Confirm the failure leaves the database in the promised state.

## Stage 3 — Complete the React interaction and state path

Build the user interaction around the published API contract. Keep transient form state local unless the audit gives a reason to share it. Use the project’s chosen server-state mechanism to expose pending, success, validation, network, and malformed-response behavior. After mutation, update or invalidate exactly the cached data that became stale; do not hand-copy an entire server response into a competing global state store.

Make the action accessible: label inputs, expose errors to assistive technology where appropriate, preserve useful user input after a validation error, prevent accidental duplicate submits, and give the user a visible result. The goal is not decorative polish; it is an interface whose actual request lifecycle is legible.

**Working looks like:** an authorized user can complete the flow through the browser, see a reliable pending/result state, and see the changed data after reload as well as immediately.

**Check it yourself:** use keyboard-only navigation, throttle or disconnect the network once, and reload after success. Inspect Network to confirm method, payload, credentials, status, and response match the contract. A UI that merely changes locally without a durable row is not complete.

## Stage 4 — Remove shortcuts and record evidence

Locate the temporary mechanisms that touched this workflow: hard-coded user/workspace IDs, fixture-only responses, disabled validation, direct state mutation, unguarded routes, stale manifest assets, or a database role that bypasses the boundary you claim to test. Remove only those on the chosen path. Document the final end-to-end evidence: a browser walkthrough, the relevant request/response, database before/after, and the tests that guard the contract.

**Working looks like:** the workflow remains usable after a fresh build, service restart, migration/seed reset, and browser reload.

**Check it yourself:** execute the walkthrough from a clean local state with the documented user. If a step relies on a manual database edit, a DevTools state override, or a one-time shell command that a real user cannot do, it is still a shortcut—either remove it or narrow the workflow honestly.

## Decisions you have to make

- Which workflow has the highest user value while fitting the existing domain and lessons?
- Which transitions and failures are in scope, and which are explicit non-goals?
- Should the server return not-found or forbidden for a cross-workspace reference, and what does that reveal?
- Which cached queries must update or invalidate after success?
- Which operation needs a transaction or conflict outcome rather than a best-effort sequence?

## Acceptance criteria

Nothing here is checked automatically. Read every item against software you actually built and ran.

- [ ] A written contract identifies actor, workspace, inputs, server contract, records changed, rendered result, failures, and non-goals.
- [ ] The selected workflow completes through the browser without a fixture-only response, hard-coded identity, or manual state override.
- [ ] The server authenticates, authorizes, validates, parameterizes database work, and returns deliberate success/failure responses.
- [ ] Invalid, unauthenticated, and wrong-workspace attempts have observable behavior and leave no unintended durable change.
- [ ] The React feature shows pending, success, validation, network, and malformed-response behavior appropriate to the chosen contract.
- [ ] A reload after success retrieves the durable result from the server rather than relying on local illusion.
- [ ] Relevant server and frontend tests execute against the completed path.
- [ ] Fresh migration/seed, build, service restart, and a browser walkthrough reproduce the workflow.

## Prove it to yourself

Without opening the code, explain the chosen workflow as an ordered chain. At each arrow state the data shape, owner, and failure outcome. Then answer: what prevents a user from changing another workspace’s record; what keeps a retry from duplicating work; where does a failed second database write roll back the first; and how does the UI learn that server data is stale? Reopen evidence only after you have attempted the explanation.

## What this unlocks

You have one complete product slice to harden rather than a list of disconnected capabilities. C03 deliberately breaks this slice at its boundaries, C04 selects the tests that demonstrate it, C05 examines one query it truly runs, and C07 uses it as the final explanation path.
