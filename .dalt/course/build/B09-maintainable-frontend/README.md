> **What exists when you finish:** a maintainable issue tracker frontend with intentional feature boundaries, an inspected production build, public client configuration, and safe unexpected-error containment.

## What you are building

Refactor the passing B08 application in small green steps. This is not a feature-count milestone. Preserve routes, authentication behavior, query-backed remote data, and B06 server tests. Extract only real repeated behavior into focused hooks, group code by ownership and change pattern, and leave the boundaries around transport, URL state, Query state, and local UI state visible. Produce and inspect a production build, then add an Error Boundary for unexpected rendering failures without hiding ordinary API errors.

Keep learner application source outside `.dalt`, `framework/`, `config/`, and `public/`. Deleting `.dalt` must still leave the framework and learner application working.

## Why this milestone exists

The B08 state inventory answered where values live. Use it as an architectural constraint. A hook makes code readable when it represents issue filters or project issues; it fails when it silently becomes another cache, router, or service locator. A feature directory helps when its files change together; it fails when it becomes doctrine that moves code away from real dependencies.

Build tooling has equally firm boundaries. Vite’s dev server, proxy, and Fast Refresh help local development; they are not deployment. TypeScript analysis, linting, tests, and a production bundle are distinct evidence. Client-exposed `VITE_*` values are public and usually baked into the build; DALT configuration remains server runtime configuration. Part 10 will containerize a known system, so B09 makes those assumptions explicit.

Finally, 401, 403, validation, empty, and retryable failures are normal interface states. An Error Boundary contains an unexpected descendant rendering failure. It should offer recovery and safe reporting without displaying raw internals.

## Before you start

Complete FS09.1 and FS09.2. Start from a B08 project whose checks pass and record their output.

```sh
php artisan test
npm run typecheck
npm run lint
npm run test
npm run build
```

Read the B08 state inventory. URL filters remain in the URL, remote issue data remains in TanStack Query, drafts remain local, and shared client state remains narrow. Do not install a new state manager, router, UI library, compiler, or bundler.

## Stage 1 — Map pressure before extracting

Inspect two related routes and list repeated behavior: filter parsing, query-key construction, current-workspace access, or a client-only interaction. State the existing owner of every relevant value. Select one behavior that is real and small enough to extract. Also identify one tempting extraction to leave alone because it merely renames a setter or hides a simple prop relationship.

**Working looks like:** the hook has a domain name and a written reason; a reader can still identify whether URL, Query cache, or a component owns the value.

**Check it yourself:** change a filter in a second tab, refresh both routes, and invalidate an issue query. Confirm state follows its existing owner rather than a copied local/store mirror.

## Stage 2 — Extract a focused hook in green steps

Extract the selected behavior and replace at least two real call sites. A URL hook may parse, default, and set parameters through intent-level operations. A Query hook may own typed transport, a complete key, and bounded options. It must return Query’s result, not copy server data into `useState`. A client-only hook may own a reducer or Context contract, but not API responses, permissions, or routes.

Keep the hooks linter enabled. A hook that subscribes to an external system must clean up. Do not move a command into an Effect to shorten a component. Do not add `useMemo` or `useCallback` as decoration; retain one only for an observed performance or API reason.

**Working looks like:** two callers are simpler, the API names domain operations, and ownership remains inspectable at its source.

**Check it yourself:** run typecheck, lint, and focused tests after each move. Temporarily remove a URL/query input that should drive the hook, observe the UI or test change, then restore it.

## Stage 3 — Arrange code around ownership

Choose a modest structure that fits the project. An `issues` feature may contain its page, components, query hooks/keys, API-contract types, and tests. Shared code must be genuinely shared: typed HTTP transport, public config, or visual primitives are plausible examples. Avoid an unbounded `utils`, `common`, or `services` folder that becomes a second architecture without product vocabulary.

Move one slice at a time and update imports. No circular imports should appear. A page composes UI; it must not invent a secret transport/cache layer.

**Working looks like:** a reader can follow an issue-list change through the feature boundary and its small shared infrastructure.

**Check it yourself:** search for one query from page to hook to API function. Confirm no feature imports another feature’s private implementation merely to reach a helper.

## Stage 4 — Make build and configuration explicit

Inspect `package.json`, `vite.config.*`, `tsconfig.*`, `.env.example`, and all `import.meta.env` uses. Keep public browser configuration in one module. Use only harmless values such as an API base path or release label. Document who serves built assets and routes API requests in production; “the Vite dev proxy” is not an answer. Do not make a Dockerfile yet.

Run separate typecheck, lint, test, and build commands. Inspect the emitted directory and use `npm run preview` locally. Search output for a harmless release marker to prove client values are public. Never place credentials, signing keys, sessions, CSRF data, or private tokens under `VITE_*`.

**Working looks like:** the build succeeds, output is inspectable, public config is named, and the project distinguishes local proxy behavior from production routing.

**Check it yourself:** set a harmless marker, rebuild, find it in `public/build`, start preview, and compare its Network requests with the dev server. Remove any temporary marker that is not real configuration.

## Stage 5 — Contain unexpected rendering failure

Add a small app-level Error Boundary around application composition. Its fallback must offer safe recovery, such as refresh or return to the workspace, and omit raw stacks and response bodies. Add a feature boundary only when preserving the rest of the application is materially valuable. Keep pending/error, unauthorized, forbidden, validation, empty, and retry paths in normal components.

Use a deliberate throw component or focused test to prove the fallback. Restore the normal path. If client errors are reported, report only safe context, never credentials or sensitive user input.

**Working looks like:** an unexpected rendering error shows the fallback while ordinary API errors remain specific and retryable.

**Check it yourself:** force a descendant render throw, observe fallback and recovery, then restore it. Separately force an API failure and prove it does not become the boundary fallback.

## Stage 6 — Preserve behavior and explain it

Run all project gates. Preserve B06 direct API behavior tests: a polished client cannot prove CSRF, session, membership, or ownership enforcement. Update frontend tests at accessible boundaries; test a visible filter result, failure, or fallback rather than private hook implementation. Add an architecture note naming feature boundaries, focused hooks, configuration boundary, and expected-versus-unexpected failure paths.

**Working looks like:** behavior remains correct while future changes have clearer homes and the built frontend has an honest production story.

**Check it yourself:** narrate a filtered issue list from URL to query key to typed API call to UI; then narrate the build from entry module to `public/build` to preview. Explain which failure reaches the Error Boundary and which remains API state.

## Decisions you have to make

- Which repeated behavior deserves a named custom hook?
- What source of truth must the hook preserve?
- Which code changes with the issue feature, and which is shared infrastructure?
- Is memoization justified by measured work or a real API contract?
- Which harmless configuration is public, and which stays in DALT?
- Who serves assets and routes `/api` in production?
- Where does an Error Boundary contain damage without hiding normal failures?

## Acceptance criteria

Nothing here is checked automatically. Read each item against software you actually built and ran.

- [ ] An architecture note names one repeated behavior, hook boundary, and source of truth.
- [ ] One focused hook improves two call sites without duplicate URL or Query state.
- [ ] Hook inputs, returns, cleanup, and lint rules honestly describe behavior.
- [ ] Feature organization follows ownership/change patterns with no arbitrary dumping ground.
- [ ] Pages, queries, typed transport, and tests remain traceable through boundaries.
- [ ] Separate typecheck, lint, frontend-test, and production-build commands pass.
- [ ] A harmless public `VITE_*` value is centralized and documented as public/build-time.
- [ ] No credential, server secret, session/CSRF material, or identity data reaches the browser build.
- [ ] Build output was inspected and `vite preview` is understood as local preview only.
- [ ] Production asset/API routing is stated without relying on the dev proxy.
- [ ] A safe Error Boundary fallback contains a deliberate unexpected render failure.
- [ ] Loading, empty, validation, 401/403, and retryable API errors stay intentional UI states.
- [ ] B06 server behavior tests still prove authorization and CSRF independently of the UI.
- [ ] Deleting `.dalt` would leave framework and learner application working.

## Prove it to yourself

Without reopening lessons, draw two paths. First: a person opens a filtered issue route; show URL owner, filter hook, query key, Query cache, API boundary, and rendered result. Second: a TSX edit moves through development tooling, then a production build emits browser assets with public configuration embedded. Mark where ordinary API failure renders and where an unexpected exception reaches the Error Boundary. If a path is unclear, inspect that boundary rather than adding another abstraction.

## What this unlocks

Part 10 can containerize a frontend with known commands, output, public/runtime configuration boundaries, stated production routing, and safe failure behavior. The application grows through small architectural decisions rather than invisible framework layers.
