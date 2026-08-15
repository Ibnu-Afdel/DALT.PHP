> **What exists when you finish:** a reproducible, documented, and explainable final issue tracker with one proven workflow, a bounded evidence pack, and a deliberate freeze point rather than an endless feature backlog.

## What you are building

Freeze the capstone result. Assemble a concise maintainer-facing record that points to the C01 audit, C02 workflow contract/evidence, C03 failure matrix, C04 test portfolio, C05 database review, and C06 Laravel comparison. Add a final runnable checklist that starts the application from its documented local state, migrates/seeds it, executes the high-value checks, and demonstrates the chosen workflow. Then explain the stack closed-book, including five real bug stories: frontend, backend, database, authentication/authorization, and container/runtime.

Freeze does not mean declare the application perfect or stop learning. It means choose an honest completion boundary: no new major technology, no speculative feature work, no unreviewed refactor, and no claim without evidence. Record known limitations and the next sensible changes separately from completion. A finished learning project is coherent, understandable, tested at important boundaries, secure enough for its stated target, reproducible, measurable, and explainable—not the one with the longest feature list.

## Why this milestone exists

Courses can teach permanent motion: after every solution comes another library, rewrite, or optimization. That makes it difficult to know whether the learner has integrated the stack or merely accumulated fragments. The capstone ends differently. It asks you to reconstruct a request, defend a few key choices, reproduce the system from evidence, and stop changing it once those claims are true.

The final explanation is deliberately technical but not theatrical. You do not need a polished presentation or an enormous architecture diagram. You need enough evidence that another developer can reproduce the app and that you can identify how a browser action becomes a durable, authorized database change and visible UI. Bug stories prove that your understanding includes diagnosis, not just the happy path.

## Before you start

Finish C01 through C06. Remove only disposable local artifacts from the learner project using its normal ignore/reset process; do not delete application work, database volumes, or unrelated repository files. Make sure no challenge transaction is running in the course repository. Start from a documented clean local state and collect baseline command output.

```sh
docker compose up -d
php artisan test
npm run typecheck
npm run lint
npm run build
```

If browser tests exist, run the documented command against resettable data. Check that the project’s README says how to configure safe local environment values without committing secrets. A build artifact, database dump, session cookie, `node_modules`, or an untracked generated file is not evidence of reproducibility; the documented commands are.

## Stage 1 — Assemble the final evidence index

Create `docs/capstone.md` or an equivalent project README section. Link or summarize the six prior capstone artifacts and state the final workflow, supported local environment, exact commands, expected high-level outcomes, known limitations, and freeze date/version. Keep the index short enough to read before changing code. It should lead a maintainer to evidence rather than duplicate every plan, test, and diagram.

**Working looks like:** a new maintainer can understand what the system promises, where each promise is proved, and what it explicitly does not claim.

**Check it yourself:** give yourself ten minutes with only the evidence index. Can you find route ownership, database role/RLS proof, test commands, the performance decision, and the workflow walkthrough? If not, improve navigation rather than adding more prose.

## Stage 2 — Reproduce the system from a clean local state

Follow the README exactly in a fresh shell, resettable volume, or clean copy. Configure local environment values, start Compose, install/build the frontend as documented, migrate and seed the database, and reach the application in a browser. Use the actual service names and commands of the learner project. Record a failure caused by missing documentation and repair the documentation or setup; do not silently use knowledge from your old shell.

**Working looks like:** the documented path produces running services, a usable local account/data setup, and the chosen workflow without an undocumented manual intervention.

**Check it yourself:** stop services and repeat the documented startup once. Confirm health/dependency assumptions and asset mode are explicit. Reload the browser after a build; stale assets or an unmentioned dev-server requirement are reproducibility defects.

```sh
docker compose ps
docker compose logs --tail=50
php artisan test
npm run build
```

## Stage 3 — Explain one request through the full stack

Choose the C02 action and explain it without the source open: click; React handler; local/URL/server state; HTTP request; DALT route; middleware/authentication; authorization; validation; query/transaction; PostgreSQL constraints/index/RLS; response; cache update/invalidation; and render. Name the data shape and failure outcome at the important boundaries. Then compare the explanation with C01 and correct errors in the evidence pack, not in your memory alone.

**Working looks like:** the explanation names real project mechanisms and makes clear which layer owns each decision.

**Check it yourself:** reproduce the action with DevTools and database inspection. Ask a peer—or record yourself—to interrupt at any arrow with “what happens if this fails?” Answer from evidence, then verify.

## Stage 4 — Explain five evidence-backed bug stories

Select one real bug or deliberately reproduced defect from each category: frontend, backend, database, authentication/authorization, and container/runtime. For every story record symptom, initial hypothesis, evidence gathered, root cause, repair, regression proof, and what you would inspect first next time. A bug story can come from course work, but it must be your project’s actual mechanism; do not use a generic story with names substituted.

**Working looks like:** each story demonstrates a diagnostic path, not a memorized fix or an assertion that “tests caught it.”

**Check it yourself:** hide the solution column and retell the investigation. For at least one story, recreate the safe failing condition locally or point to the failing test/transcript that did. Confirm secrets and personal data are absent from logs/screenshots.

## Stage 5 — Run the final evidence set and set the freeze boundary

Run the focused tests from C04, the high-value browser flow if configured, the C05 database/role proof, and all normal project checks. Record actual command output/date/environment assumptions. Make a final list of known limitations and next steps, but do not immediately implement them. Decide whether a finding blocks completion: a broken security boundary, non-reproducible setup, or failing core workflow does; a documented feature not in the C02 scope usually does not.

**Working looks like:** the repository has a clear point at which its current behavior and evidence were last known good, plus an honest list of what is outside that guarantee.

**Check it yourself:** read the acceptance criteria as a maintainer rather than author. If an item can only be answered “probably,” obtain evidence or mark the project not frozen. Do not compensate for a missing proof with a longer narrative.

## Decisions you have to make

- Which evidence belongs in the short index and which should remain linked detail?
- What clean-state procedure is realistic for local contributors and safe for their data?
- Which limitation blocks a trustworthy freeze versus belongs in a future backlog?
- Which five bug stories demonstrate distinct diagnostic boundaries?
- What exact version/date/environment scope does the freeze claim cover?

## Acceptance criteria

Nothing here is checked automatically. Read every item against software you actually built and ran.

- [ ] A final evidence index links the system audit, workflow, failure matrix, test portfolio, database review, and Laravel comparison.
- [ ] A documented clean local path starts services, prepares data, builds assets, and reaches the chosen workflow without hidden manual setup.
- [ ] The C02 workflow completes in the browser and remains durable after reload.
- [ ] Backend tests, frontend behavior tests, typecheck, lint, build, and the documented browser flow (when configured) have current recorded results.
- [ ] The role-based RLS proof, transaction/concurrency evidence, and C05 query correctness checks remain reproducible.
- [ ] A closed-book request explanation covers browser, React, HTTP, DALT, security, PostgreSQL, response, state update, and render.
- [ ] Five bug stories cover frontend, backend, database, authentication/authorization, and container/runtime with evidence and regression proof.
- [ ] Known limitations and future ideas are recorded separately from the defined freeze boundary, with no new major technology introduced.

## Prove it to yourself

Close every artifact. Draw the selected request and label each trust boundary. Explain which test catches a bad ownership predicate, which proof catches a permissive RLS policy, and which evidence says an index decision is correct. Then tell one bug story as hypothesis → evidence → cause → repair → regression proof. Reopen the documents only to check the gaps you discovered.

## What this unlocks

This is the end of the Fullstack build track: a project you can run, inspect, test, diagnose, and explain. Further work should begin as a new, explicitly scoped engineering decision—not as an attempt to make the capstone look larger. Preserve the evidence index as the handoff point for that next decision.
