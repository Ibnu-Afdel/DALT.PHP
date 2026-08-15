# FS09.2 — Build pipeline, configuration and failure boundaries

Lesson ID: FS09.2
Title: Build pipeline, configuration and failure boundaries
Part: 09 — Advanced React and tooling
Order: 2
Status: Published
Estimated effort: 100–130 minutes
Difficulty: Integration
Prerequisites: FS09.1 — Custom hooks and feature boundaries
Project milestone: B09 — Maintainable frontend
Primary source dossier: FSO_PART_07.md
Last reviewed: 2026-08-15

## Why this matters

`npm run dev` is a productive editing environment, not a miniature production deployment. It can
serve source modules, provide Fast Refresh, and proxy requests in ways a deployed browser cannot.
`npm run build` starts at the application entry point, follows imports, transforms and optimizes the
module graph, and emits browser assets. A frontend that works only through a dev proxy has not yet
explained how production requests reach DALT. A frontend that places a secret in `VITE_*` has
published it to every browser that downloads the build.

Expected API outcomes—401, 403, validation errors, loading, and empty data—need intentional screen
states. An Error Boundary is different: it contains an unexpected rendering failure that would
otherwise take down its subtree. It does not replace error handling in an event callback or request.

## Before you start

Required:
- FS09.1 — Custom hooks and feature boundaries
- FS08.2 — Mutations, invalidation and optimistic UI

Recommended first:
- Review the learner project’s `package.json`, `vite.config.mjs`, `tsconfig.json`, and `.env.example`.

Going deeper in DALT Core — optional:
- None. Fullstack introduces its own build/runtime boundary before Docker.

```sh
npm run typecheck
npm run lint
npm run test
npm run build
```

## By the end

- distinguish Vite development, TypeScript analysis, and production-build responsibilities;
- trace an entry module through a module graph to emitted assets;
- use public build-time configuration without exposing secrets;
- inspect a build and explain why a dev proxy is not production routing;
- contain unexpected rendering failure without hiding expected API states.

## Predict before reading

If you change `VITE_API_BASE_URL` after a bundle has been deployed, what must you rebuild? Can a
`VITE_DATABASE_PASSWORD` be private because it lives in `.env`? If a click handler throws after an
`await`, will an Error Boundary necessarily render its fallback? Record an answer before proceeding.

## Mental model

```text
TSX source → imports form a module graph → Vite transforms/resolves/optimizes → browser assets
       ↑                 ↑                         ↑
 dev server + HMR    typecheck is separate     public VITE_* values are embedded

expected request failure → screen-level state
unexpected render failure → nearest Error Boundary fallback
```

Vite internals can change by major version, so do not memorize an old brand story. Inspect the
pinned version and use its official documentation as technical truth. The durable model is source,
graph, transform, assets, and output. `tsc --noEmit` is a static-analysis gate; transforming
TypeScript for a bundle does not make that independent gate redundant.

## 1. Development is a feedback loop

The development server resolves application modules while you edit and makes updates quick. Its
proxy can make `/api` look same-origin locally, but it does not deploy itself with `public/build`.

```json
{
  "scripts": {
    "dev": "vite",
    "typecheck": "tsc --noEmit",
    "build": "vite build",
    "preview": "vite preview"
  }
}
```

```ts
export default defineConfig({
  plugins: [react()],
  server: { proxy: { '/api': 'http://localhost:8000' } },
});
```

Production is a separate question: which server serves emitted assets, and how does it route `/api`
to DALT? Part 10 packages that topology. `vite preview` is a local check for built assets, not a
production server.

```sh
npm run build
npm run preview
```

## 2. Build and type check are different evidence

The build follows the entry point and its reachable imports. Bare package imports, CSS, images, and
lazy routes become a graph that produces deployable assets, often with hashed names for caching.
Source maps, when deliberately enabled, help map deployed code to source; they can also reveal
source information, so choose them consciously.

```text
src/main.tsx → App.tsx → routes → features/issues → shared/http
                     └→ CSS and static assets
                                  ↓
                        public/build/assets/*
```

```sh
npm run typecheck && npm run lint && npm run build
find public/build -maxdepth 2 -type f | sort
```

Do not use a successful bundle as proof that all types are correct. Keep type checking explicit in
local and CI gates. Lazy loading is useful only when a route or heavy dependency has a reason to
load later; it is not an architecture requirement.

```tsx
const SettingsPage = lazy(() => import('./features/settings/SettingsPage'));
// Use this only when a route boundary and loading fallback justify it.
```

## 3. Vite client values are public build-time configuration

Only variables with Vite’s client prefix are exposed to source code. Referenced values are strings
substituted into the client artifact. `.env` is a file-management convention, not a confidentiality
boundary. Database credentials, signing keys, session material, and private tokens belong in DALT’s
runtime configuration, never a browser bundle.

```env
VITE_API_BASE_URL=/api
VITE_RELEASE_NAME=local
# DATABASE_PASSWORD=never expose this to React
```

```ts
export const config = {
  apiBaseUrl: import.meta.env.VITE_API_BASE_URL ?? '/api',
  releaseName: import.meta.env.VITE_RELEASE_NAME ?? 'development',
};
```

```ts
// Wrong: everyone who downloads JavaScript can recover this value.
const secret = import.meta.env.VITE_DATABASE_PASSWORD;
```

Changing DALT’s environment after deployment affects server runtime. Changing a value compiled into
frontend source requires a new build and deploy. Name these two configuration boundaries separately.

## 4. Bound unexpected rendering failure

An Error Boundary catches errors thrown while descendants render, construct, or run lifecycle work.
It does not catch most event-handler errors, async rejections, server responses, or errors inside
the boundary itself. React’s stable boundary API is class-based; isolate that small class.

```tsx
class AppErrorBoundary extends Component<PropsWithChildren, { failed: boolean }> {
  state = { failed: false };
  static getDerivedStateFromError() { return { failed: true }; }
  componentDidCatch(error: Error, info: ErrorInfo) { reportClientError(error, info); }
  render() { return this.state.failed
    ? <main><h1>Something unexpected failed</h1><p>Refresh or return to your workspace.</p></main>
    : this.props.children; }
}
```

```tsx
root.render(<AppErrorBoundary><RouterProvider router={router} /></AppErrorBoundary>);
```

Keep the fallback calm: no raw stack traces or response bodies. A root boundary catches catastrophic
failure; a smaller feature boundary is useful only when the rest of the application can survive.
Normal query errors remain retryable UI states in their route.

```tsx
if (issues.isError) return <ApiFailure retry={() => issues.refetch()} />;
if (issues.isPending) return <LoadingIssues />;
return <IssueList issues={issues.data} />;
```

## 5. Read a build failure as evidence

A build failure is not automatically a React failure. Classify it before changing code. The error may
name an unresolved import, a syntax transform, a missing static asset, a configuration assumption,
or a deployment-only route. The command that discovered it matters: `typecheck` reports a static
type relationship, lint reports a rule violation, test reports an observable behavior expectation,
and build reports whether the entry module graph can become browser assets. One green command does
not silently grant the evidence of the others.

```text
typecheck fails → inspect the declared TypeScript contract
lint fails      → inspect the rule and the underlying React/data-flow claim
test fails      → inspect the accessible behavior and setup boundary
build fails     → inspect import graph, config, transform, or emitted-asset assumptions
preview fails   → inspect the built artifact and static serving assumptions
```

For example, a path alias can type-check if TypeScript knows it but fail during a build if Vite does
not have the corresponding resolution configuration. Conversely, code can bundle while a separate
strict typecheck reveals an unsafe assumption. Keep configuration small and traceable rather than
solving every error with another plugin.

```ts
// A value imported by browser code must be browser-safe and resolvable by Vite.
import { config } from './shared/config';
fetch(`${config.apiBaseUrl}/issues`);
```

```sh
npm run typecheck
npm run lint
npm run test
npm run build
npm run preview
```

Run commands from a clean, known state when investigating. The course’s Vite helper prefers an
existing manifest over the dev server. If a learner has already built assets, a stale manifest can
make an edit appear ineffective even while `npm run dev` is running. Inspect the response and the
served asset names; remove generated build output only when you deliberately want to return to dev
mode. Do not paper over stale output by changing unrelated component code.

```text
edit TSX → browser still shows old behavior
        → inspect Network asset URL and public build manifest
        → decide whether the app is consuming dev modules or prior production assets
        → switch the serving mode deliberately
```

## 6. Configuration has an owner and a lifetime

Configuration answers “what is this deployment allowed to know?” It is not an application-state
store. Keep public frontend configuration in a small typed boundary so routes and components do not
each invent a base URL. Validate or default the small set of values your browser application can
reasonably consume. Values from `import.meta.env` are strings; a boolean-looking string is still a
string until you parse it deliberately.

```ts
function publicBoolean(value: string | undefined): boolean {
  return value === 'true';
}

export const config = {
  apiBaseUrl: import.meta.env.VITE_API_BASE_URL ?? '/api',
  showDiagnostics: publicBoolean(import.meta.env.VITE_SHOW_DIAGNOSTICS),
};
```

This module must not become a way to smuggle server decisions into React. Authorization is decided
by DALT, session identity is established by the server, and CSRF protection is a request boundary.
A client flag might decide whether to show a harmless experimental label; it cannot prove that a
person may edit an issue. The browser can be modified by its user, so it is never a policy authority.

```ts
// Fine: a public deployment label.
const label = import.meta.env.VITE_RELEASE_NAME;

// Not fine: treating a client value as a permission decision.
const mayDelete = import.meta.env.VITE_CAN_DELETE_ISSUES === 'true';
```

Separate public build-time configuration from server runtime configuration in your deployment note.
The first is read while Vite produces assets; changing it means rebuild and redeploy. The second is
read by DALT when handling requests; changing it follows the server’s runtime/restart rules. Part
10 will make this visible inside containers, but the conceptual boundary belongs here.

## 7. Choose boundary granularity by recovery

Put a boundary where its fallback can offer a useful recovery path. A root boundary can protect the
entire application from a catastrophic render failure. A boundary around an issue detail can preserve
the workspace navigation if a broken detail formatter throws. A boundary around every button is
noise: it adds fallback states without an independent recovery strategy.

```tsx
function IssueRoute() {
  return <FeatureErrorBoundary feature="issue detail">
    <IssueDetailPage />
  </FeatureErrorBoundary>;
}
```

An Error Boundary also should not reset a broken child forever in a retry loop. A user-triggered
retry or navigation action is honest. Consider what diagnostic context is safe to report: route name,
release label, and error category can help; request bodies, cookies, access tokens, and private issue
content must not be copied into a client report merely because an error occurred.

```tsx
function FeatureFallback() {
  return <section role="alert"><h2>This area could not render</h2>
    <button type="button" onClick={() => window.location.reload()}>Refresh application</button>
  </section>;
}
```

React boundaries do not catch a rejected mutation promise as a rendering exception. That mutation
needs its own pending/error UI, as taught in FS08.2. This distinction protects useful product
language: “You are not allowed to close this issue” is actionable; “Something unexpected failed” is
appropriate only when rendering itself no longer has a trustworthy explanation.

## 8. Debug a production-shaped failure

When something works under `npm run dev` but fails after a build, compare environments from the outside in. First identify what the browser actually received: dev modules or hashed output from `public/build`. Then compare document URL, asset URL, route path, request origin, and response content type. A single-page application can work at `/` while a direct request to `/issues/12` needs the production server to return the application document. That route fallback is serving configuration, not a React Router feature that Vite can permanently supply in development.

```text
browser URL → server chooses document or asset → React matches route → Query requests API → DALT responds
```

Ask for evidence at every arrow. Inspect a Network response before assuming a JSON parse error is a React error. A redirect or HTML error document can reach code that expects JSON, and the browser will report a parser failure far from the routing or API-contract defect. Transport code should detect an unexpected response, Query should own lifecycle, and the page should translate known outcomes into useful recovery language.

```ts
const response = await fetch(`${config.apiBaseUrl}/issues`, { credentials: 'include' });
if (!response.ok) throw new Error(`Issue request failed: ${response.status}`);
const issues = await response.json() as Issue[];
```

Do not display this raw diagnostic text to a user. Use it to distinguish a wrong public API path, missing credentials, bad CSRF request, forbidden membership, and a true render exception. A 401, 403, or 419 remains an expected application outcome with its own interface. Only code that throws during rendering should enter an Error Boundary. This separation retains both safe user language and useful debugging evidence.

```text
wrong public API path → inspect public config and production routing
missing credentials → inspect cookie/origin policy
bad CSRF request → inspect the request boundary and server evidence
forbidden membership → render access state, not boundary fallback
render bug → boundary fallback, safe report, recovery action
```

Source maps and asset bases are deployment decisions too. Source maps can help a team map deployed code back to source, but published maps can reveal source and naming information. Asset base configuration matters when an application is served beneath a subpath. Test actual built behavior, including a direct route and refresh, rather than only the root document from a dev process.

```sh
npm run build
npm run preview -- --host 127.0.0.1
# Open a direct route; inspect document, assets, and API requests after refresh.
```

### Read the output of a build that succeeded

A failed build demands attention. A successful one is the more informative artifact, and
almost nobody looks at it. Run the build and read what it says:

```text
public/build/.vite/manifest.json     0.20 kB │ gzip:  0.15 kB
public/build/assets/main-3SeVgSSV.css   40.32 kB │ gzip:  7.68 kB
public/build/assets/main-DHhzG0n-.js   277.67 kB │ gzip: 87.37 kB
```

Read the gzip column, because that is roughly what crosses the network. Three things are
worth knowing about that number.

**It is one file because you have not asked for more.** Every route in your application is
in that bundle, so someone opening the login page downloads the issue detail screen too. For
an issue tracker behind a login this is usually the right trade — one slightly larger
download, then instant navigation. It stops being right when one route pulls in something
large that most sessions never touch, which is the only justification for `React.lazy` the
curriculum accepts.

**Tree-shaking removes what is provably unused, and "provably" is doing the work.** An unused
named export from your own module goes. A library imported for one function may not, if it
has side effects at module scope or is published as CommonJS. This is why a bundle grows by
40 kB after adding one date helper, and why the fix is choosing a different library rather
than a different import statement.

**Growth is the signal, not the absolute value.** Record the gzip number when you finish
B09. If it doubles in Part 12, something arrived that you did not intend — a whole icon set
imported for three icons, or a library pulled in twice at two versions. A number written
down once makes that visible; a number nobody recorded never does.

```sh
npm run build
ls -la public/build/assets/     # what a browser will actually fetch
```

Nothing here is a reason to optimise now. You have no evidence of a performance problem, and
Part 09's rule is that you optimise only after you can name the cost. This is about being
able to *read* the artifact you ship, which is a different skill from making it smaller.

## 9. Keep the toolchain small and reviewable

The toolchain is part of the project’s executable specification. Keep versions pinned through the lockfile and avoid configuration merely to demonstrate a Vite feature. Before adding a plugin or build option, state the concrete problem, the command that proves it, and whether it affects development, production, or both. Tooling complexity has maintenance cost even when the initial change looks tiny.

```text
reproduce a need → read pinned-tool documentation → make smallest change → run relevant gates → record the introduced production assumption
```

Do not add a custom Vite plugin, second bundler, server-rendering framework, or new state library in this part. That changes the locked stack rather than improving the frontend that already exists. A small configuration is debuggable because the learner can trace `package.json` scripts through `vite.config` to emitted assets.

```json
{
  "scripts": {
    "typecheck": "tsc --noEmit",
    "lint": "eslint .",
    "test": "vitest run",
    "build": "vite build"
  }
}
```

These gates complement one another. Keep them readable, run them in the same order locally and in delivery evidence, and retain a failure’s output until you can name the contract it violated. Do not replace an inconvenient failure with an ignore flag. This discipline becomes especially valuable when Part 10 runs the same frontend workflow inside containers.

One final check is to inspect the artifact rather than trusting a command label. Open the built entry
document, verify that its asset references resolve, and view a direct client route after refresh.
Compare the request path used by the built application with the API path documented in configuration.
If an asset or request works only because the dev server silently rewrote it, write down the missing
production responsibility for Part 10. The correct fix may be server routing, a public base path, or
an API topology decision; it is rarely a new React component.

```text
build command green + direct route broken = serving configuration remains incomplete
preview page visible + API response wrong = inspect origin, cookies, and DALT contract
boundary fallback visible + request was 403 = expected failure was misclassified
```

This small review makes tool output actionable. It turns “the frontend built” into the stronger,
testable statement that the browser received the intended assets, booted the application, followed a
route, and made a request through a stated boundary.

Record the exact command, local URL, route, and observed response when this review finds a gap. That
record gives the Part 10 container work a concrete serving requirement instead of an assumption.

## Try it

Before: predict whether a production bundle contains the release name.

Do: set a harmless `VITE_RELEASE_NAME`, run `npm run build`, inspect `public/build`, then run `npm run
preview`. Compare an API request there with one from the development server.

Observe: public values are discoverable in a build; local proxy behavior is not production routing.

Explain: build-time browser configuration and DALT runtime configuration have different readers and
different deployment moments.

## Common mistakes

### Treating `.env` as a vault

Every referenced client value reaches the browser. Keep secrets entirely outside frontend source and
verify with a built-output search before claiming a value is private.

### Using the dev proxy as production architecture

It disappears with the development process. State an asset/API topology before Part 10.

### Turning every error into a boundary fallback

A 403 needs an access explanation. A boundary means rendering broke. Mixing them erases recovery.

### Relying on build for type correctness

Run the dedicated typecheck. A green bundle is different evidence.

## When this goes wrong

1. Compare exact dev and preview Network requests and origins.
2. Inspect scripts, Vite config, and `public/build` before changing application code.
3. Search built output for a harmless marker to understand what is public.
4. Identify whether failure is HTTP state, an event callback, or rendering.
5. Ensure the fallback logs safely for developers and gives users recovery without internals.

## Exercise

### Goal

Prove that your B08 frontend has a deliberate build/config boundary and unexpected-render fallback.

**Mode: Manual, tool-backed evidence.** The learner runs the named toolchain, inspects emitted files, and triggers a controlled render failure; this course does not pretend a structural checker can judge a deployment design.

### Starting state

Use the passing B08 project after FS09.1. Inspect rather than replace its Vite, TypeScript, and API
setup.

### Requirements

- Run separate typecheck, lint, test, build, and preview commands.
- Add or document one harmless `VITE_*` value and explain why it is public.
- State how production assets and `/api` will be served; do not cite the dev proxy as the answer.
- Add an Error Boundary with safe fallback and one small test or deliberate throw proving it.
- Keep loading, 401/403, validation, and retry states outside the boundary.

### Constraints

- Do not put a credential, CSRF token, identity, or server-only value under `VITE_*`.
- Do not call `vite preview` a production server.

### Verification

Run `npm run typecheck && npm run lint && npm run test && npm run build`, inspect `public/build`, and use
`npm run preview` to open built assets. Trigger the boundary deliberately and restore the normal path.

### Hints

<details><summary>Hint 1</summary>Use a tiny `config.ts` module so `import.meta.env` is not scattered.</details>

<details><summary>Hint 2</summary>An Error Boundary is class-based; keep it small and wrap composition.</details>

<details><summary>Hint 3</summary>A test-only throw component can prove the fallback safely.</details>

## In the project

Used in B09 — Maintainable frontend. This gives Part 10 known commands, inspectable output, a named
client/server configuration split, and expected error behavior before Docker makes serving real.

## Closed-book checkpoint

Close the lesson first.

1. Why does `npm run build` not replace `npm run typecheck`?
2. What makes `VITE_*` unsuitable for a secret?
3. Why is a dev proxy not production routing?
4. Which failures should a query route render rather than send to an Error Boundary?
5. What evidence distinguishes a built preview from a production server?

## Resources

### Read

- [Vite: Features](https://vite.dev/guide/features) — transforms and static handling.
- [Vite: Env Variables and Modes](https://vite.dev/guide/env-and-mode) — public configuration.
- [Vite: Building for Production](https://vite.dev/guide/build) — output and preview boundary.

### Reference

- [React Component: Error Boundaries](https://react.dev/reference/react/Component#catching-rendering-errors-with-an-error-boundary) — scope and fallback API.

## You are done when

- [ ] You can trace source modules to production assets.
- [ ] Client-visible configuration is harmless and server-only configuration stays server-only.
- [ ] Built assets run under preview and are not confused with deployment.
- [ ] Expected request failures and unexpected render failures take different UI paths.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_07.md` §§3–7, 89–155, 238–255.
- Official sources: Vite Features, Env and Modes, Build, CLI, and React Error Boundaries linked above.
- Versions: root `package.json` pins Vite ^8.0.12, React 19.2.3, TypeScript 5.9.3, and `@vitejs/plugin-react` 6.0.5.
- Consulted: 2026-08-15.
- Curriculum authority: `docs/dalt-fullstack/CURRICULUM.md` §20, FS09.2.
- DALT files inspected: `package.json`, `vite.config.mjs`, `tsconfig.json`, `framework/Core/functions.php` (`vite()` manifest behavior), and FS07.3.
- Laravel source: not applicable; this concerns browser assets and React render containment.
