> **What exists when you finish:** a navigable issue tracker with refreshable resource URLs, a server-derived authenticated shell, and a small test suite that proves the frontend's important behavior without pretending to replace backend security tests.

## What you are building

Turn the protected application from B06 into an application people can navigate deliberately. Add a
small route table, meaningful project and issue addresses, an authenticated shell, login recovery,
and tests for the flows that are expensive to break. The API remains the source of truth for
identity and permissions. This milestone makes the browser experience dependable around that truth.

Use the project routes from the blueprint as a starting shape:

```text
/login
/workspaces/:workspaceId/projects/:projectId
/issues/:issueId
```

A URL is not decoration. It is durable application state: it survives refresh, participates in
history, and can be copied to another person. Build a route table small enough that each location
means something. Do not create a giant nested tree merely because a router can represent one.

## Why this milestone exists

Up to B06, the issue tracker can be correct while still behaving like a demo. A user opens an
issue from a list, refreshes, and loses the selection; Back restores whatever local state happens
to remain; an expired session produces a blank page; a failure looks exactly like an empty result.
Those are structural failures, not cosmetic ones. A real application must let the browser do its
part of the work.

This is also the right point to make frontend verification normal. Backend behavior tests prove
that crafted HTTP requests cannot bypass ownership and membership rules. Browser-facing tests
prove that a person can submit a form, discover a validation error, follow a route, and understand
an unavailable action. Neither tier subsumes the other. A passing component test cannot authorize
a request, and a passing API test cannot tell you a label is missing.

## Before you start

Complete FS07.1–FS07.3 and preserve B06's API behavior tests. Ensure the root project toolchain
runs before changing it:

```sh
npm run typecheck
npm run lint
npm run test
npm run build
```

Work in learner application files under `resources/`, `app/`, `routes/`, and `tests/` as
appropriate. Do not put issue-tracker source in `framework/`, `config/`, `public/`, or
`.dalt/`. Deleting `.dalt` must still leave the framework and learner application working.

## Stage 1 — Give application locations durable addresses

Install and configure the router in the learner application. Add login, project, issue-detail, and
not-found routes. Render issue links with router links rather than click handlers that mutate a
selected-id state. Read route parameters as strings, validate them before calling the API, and
make an invalid parameter visibly not-found.

**Working looks like:** opening an issue in a new tab, refreshing the page, and using Back and
Forward all produce meaningful screens. A copied project URL restores the same project view. An
unknown client location produces an intentional not-found page, not a blank root application.

**Check it yourself:** paste each route into a new tab, reload it, and trace Back/Forward through
two issue details and a project list. Try a malformed id and a missing id separately: one is a
client-side invalid-location decision; the other is a server-backed resource outcome.

## Stage 2 — Put shareable view state in the URL

Choose one useful project filter, such as open versus closed issues, and represent it as a query
parameter. Validate unknown values and select an intentional default. Keep unsaved form fields,
passwords, CSRF proof, and other sensitive or temporary values out of the URL.

**Working looks like:** `?status=open` and `?status=closed` can be copied, restored after a
refresh, and produce the documented filtered view. Changing the filter creates a browser-history
entry only when that feels useful for the interaction.

**Check it yourself:** copy both filter URLs into fresh tabs and compare their visible labels and
requests. Edit the query string to an unknown value and confirm the app does not send an accidental
or unsafe filter to the API.

## Stage 3 — Build an honest authenticated shell

Load current-user information from the server on application start. Model loading, anonymous,
authenticated, and failed states separately. Show useful navigation only after identity is known.
Recover a 401 by taking the visitor to login; show a distinct access-denied outcome for 403; keep
a network failure visible and retryable. On logout, call the server endpoint and then remove stale
client presentation.

**Working looks like:** a fresh protected route waits while the session is checked rather than
flashing login. Logging out in another tab or letting a session expire causes the next request to
recover honestly. A forbidden issue does not masquerade as a login problem.

**Check it yourself:** use a normal browser session and a second tab. Load a protected issue,
logout from the second tab, then perform a request in the first. Inspect the response status and
the resulting UI. Repeat with a user who is authenticated but not allowed to read the issue.

## Stage 4 — Keep frontend authorization in its proper place

Use server-provided facts to hide or disable controls that would be pointless for the current
user. Explain each control's UX purpose. Retain every B06 ownership, membership, CSRF, and session
test: a direct request is still the authority test.

**Working looks like:** an unavailable edit or delete action is not presented as if it will work,
while a crafted request for the same action still receives the documented server denial and
leaves the database unchanged.

**Check it yourself:** compare the UI with a direct HTTP request. Temporarily hide an action for
everybody and observe that the backend tests still prove the security rule; then restore the
intentional UI. This is the distinction the milestone needs you to internalize.

## Stage 5 — Make observable behavior executable

Configure Vitest, jsdom, React Testing Library, jest-dom matchers, and user-event in the root
application. Test accessible behavior: labels, roles, visible messages, navigation, and requests
at your one chosen client boundary. Mock network behavior deliberately; do not mock the component
you are trying to prove. Keep a small set of high-value tests rather than chasing a percentage.

**Working looks like:** a failing test names a user-visible regression: login validation is absent,
a denied control appears, an error alert is missing, or a filter no longer changes the rendered
list. The test passes only after the visible behavior returns.

**Check it yourself:** remove one label or change one expected denial state, run the focused test,
see it fail for the named reason, restore it, then run `npm run test`. A test you have never
observed failing is trusted rather than demonstrated.

## Stage 6 — Choose one browser-level flow carefully

If your environment supports it, add a single small Playwright flow for login, create issue, or
view issue. It should exercise a high-value seam that component tests cannot cheaply cover. Do
not turn the course into browser-test engineering; configuration, selectors, and environment
flakiness should not crowd out product work.

**Working looks like:** the chosen flow launches against a known local application, performs one
real workflow, and produces useful failure output. If browser automation is not yet available,
record the manual proof and keep the Vitest suite as the required automated evidence.

**Check it yourself:** run the flow twice from a known state. Break a visible step in a temporary
edit, observe the failure, restore it, and do not claim that this one flow covers authorization or
every possible browser state.

## Decisions you have to make

- Which project and issue URL pattern says the most about location with the least nesting?
- Which filters are worth sharing in query parameters?
- Which screen appears for 401, 403, 404, malformed input, and network failure?
- What safe current-user data does the shell need?
- Which authorization-aware controls improve clarity without implying security?
- Which three to five frontend behaviors are costly enough to keep under test?
- Is a browser-level test valuable in your environment, and which one flow earns it?

## Acceptance criteria

Nothing here is checked automatically. Read each item against software you actually built and ran.

- [ ] Login, project, issue-detail, and not-found screens have intentional routes.
- [ ] Refreshing and copying a resource URL restores the expected screen.
- [ ] `curl` against `/login`, `/workspaces/...`, and `/issues/...` — a request the app never
      routed, not a browser refresh inside a tab it already loaded — returns the built document,
      via the per-resource `{*}` fallback routes from FS07.1, not a DALT 404.
- [ ] Browser Back and Forward move through meaningful application locations.
- [ ] Route and query parameters are validated before they affect an API request.
- [ ] One useful filter is durable URL state and sensitive drafts are not in URLs.
- [ ] The authenticated shell distinguishes loading, anonymous, signed-in, and failed session states.
- [ ] A 401 recovers to login while a 403 is presented as an access decision.
- [ ] Logout and expiry do not leave stale private content presented as current.
- [ ] UI controls are authorization-aware for usability, while B06 backend tests still prove enforcement.
- [ ] Tests query accessible labels, roles, messages, or navigation rather than component internals.
- [ ] Tests cover login behavior, a validation failure, a route or filter outcome, and an authorization-sensitive control.
- [ ] Each mocked response is owned by the API client boundary rather than hidden in the component under test.
- [ ] I deliberately broke one tested behavior and watched the named test fail.
- [ ] One small browser-level flow exists or I recorded why manual proof is the honest current evidence.
- [ ] `php artisan test`, `npm run typecheck`, `npm run lint`, `npm run test`, and `npm run build` pass.
- [ ] Deleting `.dalt` would leave my application working.

## Prove it to yourself

Close the editor and trace a copied issue URL from browser location to route match, parameter
validation, API request, server session, authorization decision, response, and rendered screen.
Then repeat the trace after the session expires. Name which part of the experience React owns and
which part the server owns.

Next, choose one frontend test and say exactly what a person would observe if it failed. If the
answer is “a hook was called” or “a state setter received a value,” move the test outward until it
names an accessible outcome. Finally, identify one server rule that your frontend tests cannot
prove and point to the B06 test that does.

## What this unlocks

Part 08 can classify route parameters as URL state and stop hand-synchronizing remote data. Part
09 can extract route-aware behavior into focused hooks and improve the project toolchain. The
application now has a stable frontend structure worth maintaining rather than a single screen
that happens to fetch data.
