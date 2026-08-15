# FS07.2 — Authentication in the frontend

Lesson ID: FS07.2
Title: Authentication in the frontend
Part: 07 — React structure, routing and testing
Order: 2
Status: Published
Estimated effort: 90–120 minutes
Difficulty: Integration
Prerequisites: FS07.1 — URLs and React Router
Project milestone: B07 — Navigable tested application
Primary source dossier: FSO_PART_07.md
Last reviewed: 2026-08-15

## Why this matters

Frontend authentication is where a server-side session becomes a humane application. It must make a
loading session, an anonymous visitor, a signed-in user, an expired session, and a forbidden
resource visibly different. Those distinctions prevent both security theatre and confusing UI.

## Before you start

Complete the preceding Part 07 lesson and keep the protected API from FS06. The browser may
remember a rendering decision, but the DALT session and API remain authoritative. Work in your
learner application, not in framework or course files.

```sh
npm run typecheck
npm run lint
```

Going deeper in DALT Core — optional:

- None. The DALT Core material is optional reference and never a gate for this track.

## By the end

- identify the boundary that owns a decision;
- represent loading, success, and failure explicitly;
- make a small, observable behavior change;
- choose evidence that could fail if the behavior regressed;
- explain why a convenient client state is not a security boundary.

## Predict before reading

1. What should a route do while current-user information is unknown?
2. What observable result distinguishes a failed request from an empty result?
3. Which important claim can only the server prove?

## Mental model

```text
browser event → client state → request → server session and authorization → response → rendered UI
                     ↑                                                ↓
                  tests observe labels, controls, navigation, and messages
```

The useful boundary is not “frontend versus backend”; it is authority versus presentation.
The frontend uses server facts to choose a helpful experience. The server makes the access
decision. A test should exercise the public behavior at the narrowest level that can prove it.

## Make state explicit

Avoid an initial render that accidentally means “logged out.” Unknown is a real state: the request
has not completed, so redirecting now would be a race. Model it honestly and let the screen decide
what to show.

```tsx
type CurrentUser = { id: number; email: string };
type AuthState =
  | { status: 'loading' }
  | { status: 'anonymous' }
  | { status: 'authenticated'; user: CurrentUser }
  | { status: 'failed'; message: string };
```

A discriminated union prevents a component from reading `user.email` before a user exists. It
also makes a failure visible instead of silently treating a broken network as a logged-out person.

```tsx
if (auth.status === 'loading') return <p>Checking your session…</p>;
if (auth.status === 'failed') return <p role="alert">{auth.message}</p>;
if (auth.status === 'anonymous') return <Navigate to="/login" replace />;
return <AppShell user={auth.user} />;
```

## Keep authority on the server

A protected route is a UX boundary: it avoids presenting a page that cannot work. It does not
stop a crafted request. On 401, recover to login; on 403, explain that this user lacks access;
on 419, preserve a safe draft and obtain fresh CSRF proof according to your API contract.

```ts
export async function getCurrentUser(): Promise<CurrentUser | null> {
  const response = await fetch('/api/me', { credentials: 'include' });
  if (response.status === 401) return null;
  if (!response.ok) throw new Error('Could not load the current user');
  return response.json();
}
```

Do not put a user record, password, or a session token in localStorage. The session cookie is
handled by the browser according to its security attributes; frontend state is a cache of the
safe response, not a second identity system.

```tsx
function IssueActions({ canEdit }: { canEdit: boolean }) {
  return canEdit ? <button type="button">Edit issue</button> : null;
}
```

Hiding an action is still useful: it reduces confusion. But `canEdit` must derive from safe
server data and its absence never authorizes a mutation. A server test from B06 remains the proof
that a forged PATCH cannot succeed.

## Put behavior where a user can see it

Use semantic HTML so both people and tests can find it by role and label. A named form, a real
button, and an alert for a failed request communicate the application contract more clearly than
a test-only attribute.

```tsx
<form aria-label="Login" onSubmit={submit}>
  <label>Email <input name="email" type="email" /></label>
  <label>Password <input name="password" type="password" /></label>
  <button type="submit">Sign in</button>
  {error && <p role="alert">{error}</p>}
</form>
```

## Try it

Implement the state model above in your shell. Simulate anonymous, authenticated, denied, and
network-failure responses at the API boundary. Refresh an authenticated route, then expire the
session or sign out in another tab. The next protected request must recover rather than leaving
old private content on screen.

```text
unknown session       → loading message, no premature redirect
401 from current user → login route
403 from issue API    → access-denied screen, not login
network failure       → visible retryable error
```

## Session lifecycle and recovery details

A current-user request is the synchronization point between a browser cookie and a server that may
have expired its session. Loading means the server has not answered. Anonymous means it answered
that there is no usable identity. Failed means the client could not establish the fact at all.
These states cannot be aliases without causing premature redirects and misleading empty screens.

Keep status translation at one client boundary. If every component invents its own rule for 401,
the application develops incompatible login experiences: one page redirects, another shows stale
data, and a third calls an error an empty list. The shell and route screens should turn a typed
API result into UI, while request details remain in one reusable client module.

A 401 usually means the session is absent or expired, so login recovery is useful. A 403 means
the user did authenticate but cannot perform this action; redirecting to login loses useful
information. A 404 may mean a resource is gone or deliberately hidden. A 419 means CSRF proof no
longer matches server expectations. A network error is neither an identity nor permission decision.
These distinctions are part of the visible application contract.

A local logout covers only its tab. Other tabs learn the truth on their next request. Design the
client boundary so any later 401 follows the same recovery path. Cross-tab notification can make
the experience faster, but it is never proof authorization is synchronized; the server must still
reject every unauthorized request.

The shell needs a safe id, display name or email, and perhaps a server-derived capability. It
does not need a password hash, raw session identifier, or a speculative role model. A capability
can simplify presentation, but remains a UX hint; the mutation endpoint rechecks membership and
ownership. Do not put credentials, session data, or sensitive return paths in browser storage or
query parameters.

Login form errors have different origins. Empty required fields can be reported locally. Invalid
credentials are a generic server response so the endpoint does not reveal whether an account
exists. A transport failure says sign-in could not complete, not that credentials were wrong.
These messages lead to different next actions and should not become one generic error paragraph.

For a state-changing request, preserve a safe form draft after a CSRF or network failure where
that helps a person retry. Do not silently replay a mutation after a new session: identity or
permissions may have changed. Obtain fresh proof according to the documented contract and require
an intentional next action.

Global navigation is similar. An item can be absent because it is unhelpful for the current user,
but absence is not the only response to a forbidden deep link. A copied URL must still present a
403 outcome, and a direct request must still receive the denial. Controls are affordances, never
gates.

When debugging, capture only request URL, method, response status, and the selected state branch.
Never log cookies, passwords, CSRF secrets, or entire user objects. A stale UI is often a cache or
request-order problem; a successful forged write is a server-security problem. Calling both auth
bugs hides the boundary that tells you where to fix them.

Narrate four requests before you finish: current-user load, valid login, forbidden issue load, and
post-expiry mutation. For each, name what React displays, what the API returns, what the server
decides, and what data could have changed. If React were deleted, the server answers must remain
correct.

## Design review before integration

Review the decision at every boundary before adding another abstraction. The browser owns location,
history, focus, and the presentation of a useful result. React owns the mapping from explicit
state to accessible elements. The API client owns request construction and response translation.
DALT owns session resolution, validation, authorization, and the durable write. PostgreSQL owns
constraints and stored facts. A defect is easier to fix when its owner is named before code moves.

A durable feature begins with one scenario. Write the address a person visits, the identity they
have, the request that screen makes, the response it expects, and the visible result. Then write
the failure scenario beside it. For an issue detail page that means a valid route with a permitted
issue, a valid route with a missing issue, an anonymous request, and an authenticated forbidden
request. The four outcomes should not converge into one empty component.

Keep data models small at the edges. Parse a route id once. Translate an HTTP result once. Give
components values that already express their alternatives. Repeating parse logic or status checks
in several children is an early signal that the boundary is leaking. Extract a function because it
owns a decision, not merely because a file has reached an arbitrary length.

Accessibility makes this review concrete. A person should be able to identify a page heading,
the current navigation location, a form label, a submit action, and a changed error state. These
are also the stable handles a behavior test should use. A visual screenshot can be valuable for
design review, but it does not replace a semantic control or an assertion that a message exists.

Resist effects that copy one source of truth into another. Location is already state; do not mirror
a route parameter into local selected-id state unless there is a documented temporary editing
reason. Server current-user information is already state; do not make a second permanent identity
store merely to avoid passing it through a shell. A copied value has synchronization cost, and
that cost appears as stale UI after navigation, logout, retry, or browser history.

Failure copy should provide the next honest action. An anonymous person can sign in. A forbidden
person can return to a workspace list or contact a workspace owner. A missing issue can return to
the project. A network failure can retry. Do not disclose private resource details merely to make
an error more descriptive. The product decision about 403 versus 404 belongs to the backend
contract, and frontend wording must follow that decision.

When changing a route or auth behavior, keep the smallest feedback loop possible. Run typecheck
after type changes, lint after markup changes, a focused test after behavior changes, then the
whole suite before integration. Use browser inspection to compare the network status with the
rendered result. A green test that mocked a response incorrectly is not evidence; a browser view
that ignores a failed test is not evidence either. The two forms of feedback expose different
mistakes.

Finally, review the security boundary in plain language. If someone edits a URL, does the client
validate it and does the server independently validate resource access? If someone changes a
hidden field or invokes a mutation outside the UI, does the server derive identity from its
session and preserve the row on denial? If the answer invokes only React, the application has
usability rules but not protection. Keep the B06 behavior tests as the executable answer.

## Evidence and operational choices

Make each behavior observable before calling it complete. For a session load, inspect the status and
the heading that appears after it. For a rejected action, inspect both the visible recovery and the
server result. For a form error, identify whether it was local validation, a documented API
response, or a transport failure. This habit prevents a UI that appears polished while silently
misclassifying its inputs.

Keep asynchronous work cancellable in practice. A person can navigate away, sign out, or select a
different resource while a request is still in flight. Components should not overwrite a newer
screen with an older response merely because the older request finished later. Put identity and
resource requests behind boundaries with explicit loading and error transitions, then test the
case that formerly produced stale content.

Error messages must balance clarity and disclosure. “You do not have access to this issue” may be
appropriate when the product intentionally returns 403. A server that deliberately returns 404
for a private resource should be represented as not found rather than reconstructed by the client.
Never let convenience copy leak resource titles, membership details, or account existence.

Review logout as a complete transaction from a person’s perspective. The client asks the server to
invalidate the session, receives the result, clears safe cached identity, and moves to a public
location. If the request fails, do not pretend the session ended; say the action could not be
confirmed and let the person retry. A local state change alone cannot promise the server has
invalidated a cookie-backed session.

In tests, make failures legible. Name the scenario, arrange only the data it needs, interact
through accessible controls, and assert one or two meaningful outcomes. Avoid one enormous test
that logs in, creates a workspace, creates an issue, changes filters, and signs out. When it
fails, it provides a mystery rather than feedback. End-to-end coverage is intentionally small for
the same reason: it protects one high-value seam while component and API tests isolate the rest.

Before committing, run the exact commands the lesson names and read their output. Then use a real
browser for the route and session paths. Tool output, a visible screen, and a direct protected
request each answer different questions. Combining them is evidence; substituting one for another
is how false confidence enters an application.

## A deliberate final pass

Before you declare the screen reliable, reproduce each state from a clean browser session. Visit a
public route, a protected route, a permitted detail, and a denied detail. Refresh each location.
Then repeat one interaction using only the keyboard. Note the status code, page heading, message,
and available next action. These observations expose mismatches between a route table, API client,
and component branch that source reading often misses.

Keep a clear boundary between an expected product state and an unexpected failure. An empty issue
list can be successful. A missing issue can be a documented response. A forbidden issue can be a
documented response. A broken connection is a failure that needs recovery. Tests and UI should
make this vocabulary visible; otherwise every unavailable value becomes an empty array and future
debugging starts from a false premise.

Review the evidence with a hostile question: could this check pass if the behavior disappeared?
If a test only confirms a component rendered, it may. If it finds a labeled button, triggers the
event, and observes the changed route or alert, it is much harder to satisfy accidentally. If a
server test makes a direct request and checks the unchanged row, it is harder still. Select the
smallest proof that makes the claim meaningful, then keep it running.

## Common mistakes

- Treating a request in flight as proof that the visitor is anonymous.
- Storing credentials or a long-lived token in browser storage by habit.
- Redirecting 403 to login and hiding a real authorization problem.
- Calling hidden controls “security.”
- Replacing a server error with a blank screen.
- Testing implementation details instead of a label, navigation, or enabled action.

## When this goes wrong

If users flash through login before their session appears, locate the branch that conflates
loading with anonymous. If every request is anonymous, inspect the actual request credentials and
cookie policy before changing React state. If an old screen remains after logout, invalidate or
refetch the client cache; do not claim a local `setUser(null)` invalidates a server session.

```tsx
async function logout() {
  await fetch('/api/logout', { method: 'POST', credentials: 'include' });
  navigate('/login', { replace: true });
}
```

## Exercise

**Goal:** Make authentication state a coherent, server-derived frontend experience.

**Starting state:** Routes and protected DALT API endpoints exist.

**Requirements:** Add loading, anonymous, authenticated, and failed states; an authenticated
shell; login recovery for 401; a distinct access-denied path for 403; and authorization-aware
controls. Keep server enforcement unchanged.

**Verification:** Refresh a protected route, test an expired session, inspect the network response
for 401 and 403, and demonstrate that a direct forbidden API mutation still fails.

**Mode: tool-run — browser behavior plus `npm run typecheck` and `npm run lint`.** The platform
does not grade this exercise; the API response and visible recovery are its evidence.

**Hints:** First make unknown distinct from anonymous. Then implement one protected route. Keep
network translation in a client module rather than scattering status checks across components.

## In the project

B07 gains a shell whose navigation tells the person who they are signed in as and whose route
screens react honestly to server outcomes. The next lesson tests these paths through accessible
behavior. It does not replace the B06 backend tests; it complements them.

## Closed-book checkpoint

1. Why is frontend auth state a cache rather than a security boundary?
2. What state must exist before an initial current-user request finishes?
3. Why should 401 and 403 lead to different recovery behavior?
4. What is one thing a hidden Edit button cannot prove?
5. Which session event can make a previously rendered client state stale?

## Resources

### Read

- [MDN: HTTP 401](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/401)
- [MDN: HTTP 403](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/403)
- [React: conditional rendering](https://react.dev/learn/conditional-rendering)

### Go deeper

- [OWASP: Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)

## You are done when

- [ ] Loading is distinct from anonymous state.
- [ ] The shell derives identity from a safe server response.
- [ ] 401, 403, and network failure have intentional UI outcomes.
- [ ] UI controls improve usability without claiming to authorize.
- [ ] Logout and session expiry do not leave private UI presented as current.
- [ ] `npm run typecheck` and `npm run lint` pass.

## Maintainer source record

Source dossier: `docs/dalt-fullstack/sources/FSO_PART_07.md`.

Official sources: React conditional rendering; MDN 401 and 403 references; OWASP authorization guidance, linked above.

Versions: React 19.2.3; TypeScript 5.9.3.

Consulted: 2026-08-15.

Curriculum authority: `CURRICULUM.md` §18, FS07.2; `PROJECT_BLUEPRINT.md` §§40, 42..
