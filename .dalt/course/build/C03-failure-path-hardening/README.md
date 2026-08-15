> **What exists when you finish:** an issue-tracker workflow whose important failure paths have deliberate, observed behavior from browser through DALT and PostgreSQL, with evidence that no failure silently becomes a success or partial write.

## What you are building

Harden the workflow completed in C02 by intentionally exercising its failures. Build a small failure matrix and repair the behavior that matrix exposes. Cover invalid input, an unauthenticated request, an authenticated user outside the workspace, a database constraint rejection, a network failure, a malformed API response, and a transaction that fails after its first intended effect. The work is not an elaborate error-page project. It is a set of explicit contracts: what failed, which layer owns the response, what the user sees, what was logged safely, and what durable state remains.

Use the real app and real boundaries. A mocked component that displays “error” cannot prove that middleware refuses a sessionless request. A controller test cannot prove the frontend reacts to a lost network. A migration that names a constraint cannot prove a transaction rolls back. The same workflow gives these checks a shared context and makes contradictions visible.

## Why this milestone exists

Happy-path demonstrations hide the most expensive defects. Validation that occurs only in React can be bypassed. A handler that performs the first write before the second error can corrupt records. A generic catch block can turn an authorization failure into an infinite spinner. A database constraint can be correct but surface as a raw internal error. Correct failure behavior is part of the product contract and part of the security boundary.

This milestone also separates evidence from reassurance. You are not asked to promise that the system handles errors. You will cause particular conditions, observe the exact status/UI/database result, and choose the smallest correction where evidence shows a defect. Leave unknown failures in the record rather than converting them into optimistic prose.

## Before you start

Finish C02. Preserve a resettable database and a test account in two workspaces. Run the normal checks first so a later failure has a useful baseline. Ensure your failure experiments use local data and do not put secret values in screenshots or notes.

```sh
docker compose up -d
php artisan test
npm run typecheck
npm run build
```

Create `docs/failure-matrix.md` in the learner project. For each scenario reserve fields for trigger, expected HTTP/UI result, observed result, database observation, owner layer, test/evidence, and repair decision. Do not add a new error-management library merely to fill this document; use the state, fetch, testing, transaction, and Compose tools already taught.

## Stage 1 — Define failure contracts before inducing them

For every scenario state what must not happen as well as what should happen. Invalid data must not write. A missing session must not impersonate a user. A wrong-workspace request must not disclose or mutate the record. A uniqueness/check/foreign-key rejection must not leave an earlier write. A network outage must not show a false success. A malformed 2xx payload must not poison server state. A failed transaction must make its atomicity visible.

**Working looks like:** the matrix names a concrete action, layer, and observable result for each failure rather than a generic “handle errors” task.

**Check it yourself:** compare every contract with C01’s request trace and C02’s workflow contract. If you cannot identify the route, query, state owner, or database assertion, inspect before writing a test; uncertainty is a finding.

## Stage 2 — Exercise request, identity, and authorization boundaries

Send invalid input directly to the API, then repeat the action from the form. Test no session and a session whose user belongs to another workspace. Observe status, response shape, log behavior, UI result, and rows before/after. Verify that authorization is enforced server-side before selecting or changing tenant data; client route guards are helpful navigation, not protection.

**Working looks like:** browser and direct-request evidence agree about which inputs are refused, while the database confirms no unauthorized row changed.

**Check it yourself:** use an actual second workspace and inspect the target rows before and after. Record whether your chosen semantics use 403, 404, redirect, or another response, and ensure the frontend handles that actual response rather than an imagined one.

```sh
curl -i -X POST http://localhost:8000/api/issues -H 'Content-Type: application/json' -d '{}'
```

## Stage 3 — Exercise persistence and transaction failures

Cause one real constraint failure safely: duplicate a unique value, violate a foreign key, or submit an invalid state transition backed by a check/constraint. Then cause the second effect of a multi-step operation to fail in a test or disposable transaction. Inspect every table touched by the workflow. If an earlier change remains, move the intended durable effects inside an appropriate transaction or revise the contract so it no longer falsely claims atomicity.

**Working looks like:** the selected constraint has a user-safe translation and the selected multi-write operation leaves either all intended durable changes or none.

**Check it yourself:** capture a test or SQL transcript that proves the before and after state. Do not treat a caught exception as rollback evidence; query the rows. Keep external calls outside the database transaction unless your design explicitly provides a reliable delivery mechanism.

## Stage 4 — Exercise transport and payload failures in React

Use browser DevTools or a focused test seam to simulate an offline/failed request and a syntactically valid response whose JSON does not satisfy the UI contract. Ensure pending state ends, an accessible error is shown, retry behavior is honest, and no local success update survives without server confirmation. Keep response parsing at the transport boundary; a component should receive a useful domain result or a typed error, not guess at arbitrary JSON.

**Working looks like:** network and malformed-response failures reach a visible recoverable state without stale optimistic data or a permanent spinner.

**Check it yourself:** throttle/offline once in the browser and run a component/integration test for the malformed payload seam. Restore the network, retry, and reload. Confirm the durable server result—not a cached optimistic object—determines the final render.

## Stage 5 — Repair, document, and rerun the workflow

Repair only demonstrated defects. Add behavior-level tests where they protect a boundary, adjust error mapping or UI state where that is the owner, and improve the matrix with before/after evidence. Rerun the C02 happy path because a hardening change that removes success is not a repair.

**Working looks like:** each matrix row has an observed outcome, a justified disposition, and repeatable evidence; the normal workflow still completes.

**Check it yourself:** restart services, reset/seed if appropriate, repeat one success, every chosen failure, and one reload. Review logs for safe messages: useful correlation/context is good; credentials, tokens, passwords, and private payloads are not.

## Decisions you have to make

- Which response semantics prevent both disclosure and misleading UI for cross-workspace access?
- Which failures deserve a user retry, and which require changed input or support?
- Which constraints belong in PostgreSQL in addition to application validation?
- Where is the transaction boundary for the selected workflow, and which side effects cannot join it?
- What is the smallest test seam that can prove malformed data without faking the entire application?

## Acceptance criteria

Nothing here is checked automatically. Read every item against software you actually ran.

- [ ] A failure matrix records trigger, expected/observed result, database observation, owner, and evidence for every required failure category.
- [ ] Invalid input is rejected at the server boundary and produces no unintended durable write.
- [ ] Missing-session and wrong-workspace attempts are refused by server-side authentication/authorization.
- [ ] A real PostgreSQL constraint rejection has an intentional API/UI result and no partial workflow state.
- [ ] A selected multi-write failure proves rollback by inspecting affected records.
- [ ] Network failure ends pending UI honestly and permits a deliberate recovery path.
- [ ] A malformed successful-looking API payload is rejected at the transport boundary without false success.
- [ ] The completed C02 workflow still succeeds after hardening and after reload.

## Prove it to yourself

Choose one failure and narrate it without source: browser action, request, boundary that detects it, HTTP result, React state transition, and database result. Then answer why a displayed error alone is insufficient evidence. Finally, name the difference between validation, authorization, a constraint, and RLS in this workflow. Use your matrix only after attempting the explanations.

## What this unlocks

The capstone now has behavior worth selecting and preserving. C04 turns this matrix into a focused test portfolio, C05 verifies that the successful path remains evidence-backed at the database, and C07 makes failures part of the final system explanation rather than an embarrassing exception.
