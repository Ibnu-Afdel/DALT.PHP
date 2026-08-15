# FS07.1 — URLs and React Router

Lesson ID: FS07.1
Title: URLs and React Router
Part: 07 — React structure, routing and testing
Order: 1
Status: Published
Estimated effort: 90–120 minutes
Difficulty: Integration
Prerequisites: FS06.3 — Authorization and ownership
Project milestone: B07 — Navigable tested application
Primary source dossier: FSO_PART_07.md
Last reviewed: 2026-08-15

## Why this matters

An application becomes frustrating the moment a useful screen has no address. A selected issue
stored only in component state disappears on refresh, cannot be bookmarked, and makes Back mean
something accidental. A URL is durable, shareable state owned by the browser. Routing is the
translation from browser state into a React view; it is not just a menu component.

Your issue tracker already has durable resources and server-side permission rules. This lesson
gives those resources meaningful locations. The URL says which workspace, project, issue, or
filter a person intends to see. React renders the corresponding screen; the server remains the
authority for whether that screen's data may be read. Keeping those jobs separate makes refresh,
deep links, browser history, and later tests much easier to reason about.

## Before you start

Complete FS06.3 and keep its `/api/me` and protected resource behavior. Install the router in
your learner application at the version this course pins:

```sh
npm install react-router@7.18.2
npm run typecheck
```

**Install the exact version, and understand why.** React Router 8 requires React 19.2.7 or
newer; this repository pins React 19.2.3 under CR-08. A bare `npm install react-router`
resolves to the 8.x line and fails outright:

```text
npm error code ERESOLVE
npm error Found: react@19.2.3
```

That is npm protecting you, not obstructing you — a peer dependency is a library stating
which versions of React it was built against. The fix here is to pin the router, not to
force the install with `--legacy-peer-deps`. Overriding a peer conflict gets you a working
`npm install` and a runtime failure later, which is a far worse trade than a clear error
now. Nothing in Parts 07–09 uses an API that differs between the 7 and 8 lines.

Going deeper in DALT Core — optional:

- None. This is frontend application structure; Core is not a prerequisite.

## By the end

- distinguish a route, a link, a route parameter, and a query parameter;
- make URL state survive refresh and browser Back/Forward;
- build a small route table with a useful not-found state;
- read and validate route parameters before using them as application input;
- explain why a client route never grants access to protected server data.

## Predict before reading

1. What should happen when someone pastes `/issues/999999` into a new tab?
2. Which state belongs in `?status=open`: a visible filter, a form draft, or both?
3. If a React route hides a project, can a direct API request still fetch it?

## Mental model

```text
browser location → router match → route params + search params → screen → API request
                                                              ↓
                                                     server authorization
```

The History API changes the location without requiring a full document navigation. A router
listens to that location and selects a component. Use a normal link for application navigation
because it preserves browser semantics; do not attach a click handler to a generic div merely to
call navigate.

## Route state is application state

Start with a deliberately small, flat route table. It matches the project blueprint and avoids a
giant nested tree before you have a layout problem worth solving.

```tsx
import { BrowserRouter, Route, Routes } from 'react-router';

export function AppRouter() {
  return <BrowserRouter><Routes>
    <Route path="/login" element={<LoginPage />} />
    <Route path="/workspaces/:workspaceId/projects/:projectId" element={<ProjectPage />} />
    <Route path="/issues/:issueId" element={<IssuePage />} />
    <Route path="*" element={<NotFoundPage />} />
  </Routes></BrowserRouter>;
}
```

BrowserRouter owns location updates; Routes chooses exactly one matching route. A route describes
a screen location, while the API request made by that screen still handles loading, missing
resources, and denial.

## Links, parameters, and search

Use Link for a resource transition. It creates an address the user can copy and lets the router
intercept an ordinary browser navigation.

```tsx
import { Link } from 'react-router';

<Link to={`/issues/${issue.id}`}>{issue.title}</Link>
```

Read route values as untrusted strings. A URL can be typed, modified, or restored from history;
TypeScript's route declaration does not turn "oops" into a valid database id.

```tsx
import { useParams } from 'react-router';

const { issueId } = useParams();
const id = Number(issueId);
if (!Number.isInteger(id) || id < 1) return <NotFoundPage />;
```

Use search parameters for a shareable view choice such as a status filter, not for a half-typed
password or an unsaved issue title. A small helper keeps the boundary explicit.

```tsx
import { useSearchParams } from 'react-router';

const [search, setSearch] = useSearchParams();
const status = search.get('status') === 'closed' ? 'closed' : 'open';
setSearch({ status: 'closed' });
```

## Layout and failure states

An application shell is useful when global navigation belongs around several authenticated pages.
It should not fetch a resource merely because it renders a header. Keep route screens responsible
for their resource boundary and render an intentional 404 for an unmatched client location.

```tsx
export function NotFoundPage() {
  return <main><h1>Page not found</h1><Link to="/login">Go to login</Link></main>;
}
```

A route match is not proof a record exists. A valid `/issues/42` may receive 404, 401, or 403
from the API. Display a clear state and never turn denial into a blank page. Do not redirect every
failure to login: an authenticated user forbidden from an issue needs a different explanation.

## Try it

Add one project route, one issue-detail route, links from the issue list, and a not-found page.
Open a detail page in a fresh tab, refresh it, use Back and Forward, and copy an `?status=open`
link into another tab. Then request an issue outside the signed-in user's workspace directly; the
server response must remain denied even if you manually navigate to its route.

```text
valid URL + missing record      → API 404 screen
valid URL + anonymous request   → login recovery
valid URL + forbidden request   → access-denied screen
invalid URL parameter           → client not-found screen
```

## Route design review

A route is a contract between a person, the browser, and the application. It should answer where
someone is without exposing irrelevant implementation details. A resource id is appropriate when
the resource is already meaningful to the product. A transient open panel, a hover state, or a
half-completed form is not automatically a route. Promote state into the URL when refresh,
bookmarking, sharing, or Back and Forward would make the experience clearer.

Design paths from stable nouns. Workspace, project, and issue are domain locations; an endpoint
name or React component filename is not. Prefer one intentional project location to a path that
encodes every layout container. Nested paths can describe containment, but nesting is not a prize.
If the current screen needs only an issue id to load its data, a direct issue URL is easier to
share and easier to test than a deeply repeated hierarchy.

Route parameters must have validation appropriate to their domain. A database integer needs a
positive safe integer check. A slug needs its allowed character form. An enum filter needs an
allowlist and a default. Parsing is not authorization: after a parameter becomes a valid id, the
API still resolves the current session and applies workspace membership and ownership rules. This
two-step reasoning explains why a valid-looking copied URL can result in 403 or a deliberate 404.

Search parameters have their own product costs. They become visible in copied links, analytics,
browser history, and sometimes logs. Use them for non-sensitive view choices such as status,
assignee, sort, page, or tab. Do not use them for a password, raw CSRF proof, an authorization
decision, or a large unsaved draft. Normalize unknown values so a manually edited URL cannot send
a surprising value to the API or leave the page in an impossible state.

History is observable behavior. A link normally creates a history entry because the person moved
to another location. A replacement navigation can be correct after successful login, because
returning to the login form with Back is often unhelpful. Filter changes depend on product intent:
a deliberate applied filter can earn a history entry, while rapidly changing a search field often
should not create twenty Back presses. Decide this deliberately and verify it with a real browser.

A shared shell should contain global navigation and a stable place for route content. It should not
become a hidden global data loader for every screen. Let a project route own project data and an
issue route own issue data; that keeps loading, error, and not-found states local to the resource
that caused them. If several routes genuinely share a current-user request, the authentication
shell is the appropriate common boundary.

Not-found states need careful language. An unmatched client route is different from a route whose
parameter is malformed, which is different from a valid parameter whose API resource is absent.
The UI may present a similar recovery link, but keeping the distinction in code and tests makes
diagnosis possible. Never show an API error payload that reveals a private resource merely because
the client wants more descriptive copy.

A deployment that serves an SPA needs a server fallback to the application document for client
locations. This is deployment configuration, not permission to make every server request return
the same HTML. API routes must remain API routes with their documented JSON and statuses. When a
deep link fails only after deployment, first inspect the static asset and fallback configuration;
do not add route effects or duplicate components to hide a server mismatch.

Test route behavior like a user. Start at a project address, follow an issue link, refresh a detail
address, use Back, and paste a malformed parameter. Then compare the visible page with the actual
network status. A route test can prove that the application rendered an access-denied message; it
cannot prove that a forged request was denied. Keep that latter proof in the API suite.

Finally, maintain one source of truth for navigation labels and destinations where the shell needs
them. This is not an excuse for a generic navigation framework. A short data list is enough when
the routes are stable. The purpose is to prevent a header link, empty-state link, and not-found
link from drifting into three different spellings of the same product location.

## Working through route failures

When a route changes, observe it from both directions. Begin with a browser navigation: click a
link, copy the resulting address, reload it, and move through history. Then begin with a direct
address: paste it into a fresh tab and inspect the first request. The first experiment proves that
the app creates a useful location; the second proves that the location can be restored without
hidden local state.

A route screen should not assume that a successful match means successful data. It needs a loading
state while the request is active, an empty state when a permitted collection has no records, an
error state for transport failure, and a resource outcome for missing or forbidden detail. Those
states are product language. The developer gains diagnostics when each has a distinct status and
the person gains a useful recovery action rather than a silent screen.

Do not use an effect to redirect every time a prop changes. Redirect only for an intentional
navigation decision, such as an anonymous person reaching a protected screen after the session
has resolved. Effects that mirror route state into component state usually create loops, stale
closures, or a Back button that appears broken. Derive what can be derived from the current
location and retain local state only for genuinely temporary interaction.

Consider pagination and sorting before expanding a route. A page number and allowed sort can be
query parameters, but an arbitrary SQL column must never be interpolated because a route has it.
The client sends its chosen view state; the API validates and allowlists it. This is the same
boundary discipline as an issue id: a URL is input, not a command.

A testable routing design stays boring. Route components receive parsed values, call one client
boundary, and render semantic outcomes. Links have names that describe their destination. The
not-found page has a heading and recovery link. A test can then start from a memory location and
assert on a page heading or alert without knowing the router's internal implementation. The browser
still deserves a quick manual pass because scroll position, history, and deployed fallback behavior
are browser concerns.

Before moving on, make a small route map in your own words. For every path, write the valid
parameters, the API it calls, its normal title, and its recovery states. This map prevents a new
screen from quietly bypassing the conventions you have already established. It also makes future
state work in Part 08 easier: URL state is not a competing store; it is one of the places the
application intentionally keeps state.

## Practical route checklist

A route change deserves a short contract review. State the canonical path, the accepted parameter
forms, the query values, the page heading, and the expected server outcomes. This catches a common
mistake: designing only the happy-path screen and leaving every other result to accidental
conditional rendering. The contract also tells a future contributor which changes belong in the
router, the API client, or the backend.

Check keyboard behavior as well as mouse behavior. A link must be focusable and have a meaningful
name. A route change should move focus to a useful page heading when your application needs that
assistance, especially after an action that replaces the whole screen. Error and not-found states
need the same semantic structure as success states, otherwise a keyboard user reaches an address
with no understandable destination.

Think about browser reload as a boundary test. In a development server, client routing can appear
to work because the developer never asks the server for a nested document. A pasted detail URL is
the first honest test: the host must serve the application document, the router must match, the
screen must parse the id, and the API must respond. When one of those steps fails, fix that exact
layer instead of hiding it with a redirect to the root.

Route decisions should make observability better. If a customer reports a broken issue URL, its
path and query should let you reproduce the intended location without asking them to recreate
three local clicks. That usefulness is why URL state is durable state. It does not mean every
ephemeral interaction belongs in the address; a calm, small URL is easier to understand and safer
to share.

Finally, compare links in every recovery state. The not-found page, an access-denied screen, an
empty project, and a login recovery should all lead somewhere intentional. A recovery link should
describe its destination, not merely say “click here.” These small details turn routing from a
technical configuration into navigable product structure.

## One more verification pass

Compare an internal navigation with a pasted deep link. The first checks that Link creates the
correct browser location; the second checks the complete restore path. For both, record the page
heading and network request. Then make the resource unavailable and repeat. A route remains a
location even when the resource cannot be shown, so its recovery state must be intentional.

Use this final comparison to catch accidental redirects. An unknown route should not silently
become a different resource. A malformed id should not become zero. An authenticated forbidden
resource should not become a login page. Each shortcut hides information that a person and a
future test need to distinguish.

Write down the expected title, response status, and recovery link for each route outcome. That
small table makes a route contract reviewable before implementation and protects it during later
state-management changes.

## Common mistakes

- Keeping the selected issue only in useState when it is a page location.
- Treating every URL segment as a trusted number.
- Using `a href="#"` or clickable non-buttons instead of Link.
- Putting a sensitive form draft or token in a query string.
- Calling a client-side redirect an authorization check.
- Forgetting an unmatched-route state and presenting a blank screen.

## When this goes wrong

If a direct URL works in development but 404s after deployment, distinguish the server's static
asset fallback from React's route matching; do not change the route table blindly. If a page
refetches with NaN, inspect the parsed parameter at the component boundary and reject it before
calling the API. If Back feels wrong, identify state that belongs in the URL rather than adding
another effect that mirrors location into local state.

```tsx
const parsedId = Number(issueId);
const canLoad = Number.isSafeInteger(parsedId) && parsedId > 0;
return canLoad ? <IssueScreen issueId={parsedId} /> : <NotFoundPage />;
```

## Exercise

**Goal:** Give issue detail and project filtering durable URLs.

**Starting state:** The authenticated issue tracker can list data from its DALT API.

**Requirements:** Implement the routes shown above, one Link into issue detail, parameter
validation, a `?status=` filter, and an intentional not-found page. Keep authorization entirely
on the API.

**Verification:** Refresh each URL, use browser Back/Forward, open one copied link in a new tab,
and show that an unauthorized direct API request remains denied.

**Mode: tool-run — browser behavior plus `npm run typecheck`.** The platform does not grade this
exercise; the observable browser and API results are the evidence.

**Hints:** Build the not-found page first. Then make a single detail route work before extracting
a shared layout. Treat every useParams value as string or undefined.

## In the project

B07 turns a collection of components into an application with locations. Keep the route table
small: login, a project location, and an issue location are enough. FS07.2 adds an authenticated
shell around these routes; FS07.3 proves their observable behavior.

## Closed-book checkpoint

1. Why is an issue id in a URL still untrusted input?
2. Which kinds of state make good query parameters?
3. What is the difference between a client-side 404 and an API 404?
4. Why does a hidden route never replace server authorization?
5. What browser behavior does Link preserve that local state cannot?

## Resources

### Read

- [React Router: routing](https://reactrouter.com/start/declarative/routing)
- [React Router: URL values](https://reactrouter.com/start/declarative/url-values)
- [MDN: History API](https://developer.mozilla.org/en-US/docs/Web/API/History_API)

### Go deeper

- [React: You might not need an Effect](https://react.dev/learn/you-might-not-need-an-effect)

## You are done when

- [ ] Resource pages have stable, refreshable URLs.
- [ ] Links, route parameters, query parameters, and not-found behavior are intentional.
- [ ] Invalid route values do not reach the API as accidental ids.
- [ ] Browser Back and Forward restore meaningful screen state.
- [ ] The server still denies an unauthorized direct resource request.
- [ ] `npm run typecheck` passes.

## Maintainer source record

Source dossier: `docs/dalt-fullstack/sources/FSO_PART_07.md`.

Official sources: React Router routing and URL-values documentation; MDN History API, linked above.

Versions: React 19.2.3; React Router 7.18.2 (the 8.x line requires React >=19.2.7).

Consulted: 2026-08-15.

Curriculum authority: `CURRICULUM.md` §18, FS07.1; `PROJECT_BLUEPRINT.md` §§40–41.
