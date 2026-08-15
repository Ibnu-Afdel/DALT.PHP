# FS07.3 — Test frontend behavior

Lesson ID: FS07.3
Title: Test frontend behavior
Part: 07 — React structure, routing and testing
Order: 3
Status: Published
Estimated effort: 100–140 minutes
Difficulty: Integration
Prerequisites: FS07.2 — Authentication in the frontend
Project milestone: B07 — Navigable tested application
Primary source dossier: FSO_PART_07.md
Last reviewed: 2026-08-15

## Why this matters

You have been promised this lesson twice. FS04.3 ended by saying that Part 07 tests
components against a faked client, and B04 told you not to reach for a mocking library
because the technique arrives here. This is where that debt is paid.

The reason it was deferred is worth stating. In Part 04 your components called `fetch`
directly, so the only way to test them was to intercept the network — and a test that
intercepts the network teaches you to think of a component as something with a wire
attached. Part 07 changed the architecture: your screens call an API module, and the API
module owns HTTP. That module is a **seam**, and a seam is something you can substitute.
Once a component receives its data source instead of importing it, faking that source is
ordinary programming rather than a special testing power.

The other reason is that most frontend tests are worthless, and the failure mode is
specific. A test that renders a component and asserts it rendered will pass forever. A
test that asserts `useState` was called with `[]` will fail every time you refactor and
never once when the product breaks. Both feel like testing. Neither would have caught a
single bug in the issue tracker you have built.

A frontend test earns its cost when it fails for the reason a user would complain.

## Before you start

Finish FS07.2 and keep its route table, authenticated shell and API client boundary.
Everything this lesson needs is already installed at the repository root — Vitest 4.0.18,
React Testing Library 16.3.2, `@testing-library/user-event` 14.6.1, `@testing-library/jest-dom`
6.9.1 and jsdom 27.0.1. There is nothing to install:

```sh
npm run test        # green, even before you write a test
```

Two details of the root configuration shape everything below. `globals: true` is set, so
`describe`, `it`, `expect` and `vi` are available without importing them. Test files are
discovered at `resources/**/*.{test,spec}.{ts,tsx}`, which means a test lives beside the
component it covers, not in a distant `__tests__` tree.

If `npm run test` reports *"Invalid Chai property: toBeInTheDocument"*, the jest-dom
matchers are not registered. That is a course defect rather than a mistake of yours —
report it, because `npm run typecheck` will stay green while it happens.

Going deeper in DALT Core — optional:

- `docs/hardening/` describes how the backend course verifies challenges. The standard it
  applies to itself is the one this lesson applies to your tests.

## By the end

- query the DOM the way a person perceives it, by role, label and text;
- drive a component with `user-event` rather than synthetic events;
- substitute a fake API client at the seam FS04.3 built, with no network and no library;
- wait for an outcome instead of waiting for a duration;
- render a route under test with `MemoryRouter` and assert on navigation;
- choose a test level by what the failure would cost, not by fashion.

## Predict before reading

1. A test renders your issue list and asserts "Loading issues…" is on screen, then passes.
   What has it actually proven?
2. If you fake `createIssue` to always succeed, which of B04's four states can that test
   still catch?
3. Your component imports `issueApi` directly at the top of the file. What has to change
   before a test can give it a different one?

## Mental model

```text
     user's intent          your test                       the seam
  "create an issue"   →   getByRole('button')     →    fake issueApi  →  (no network)
                          user.click()                        │
                          findByRole('alert')  ←──────────────┘
```

React Testing Library is built on one rule: **the more your test resembles the way the
software is used, the more confidence it gives.** Everything else in the library follows
from that. Queries are ordered by how closely they match human perception, `user-event`
simulates a person rather than a DOM event, and there is deliberately no supported way to
read a component's internal state — because a user cannot read it either.

The seam is the second idea, and it is architectural rather than a testing trick. Your
component does not know whether the thing answering `listIssues()` is talking to DALT or
returning a literal. That is the whole point.

## Queries are design feedback, not lookup syntax

Testing Library offers several ways to find an element, and the order is a
recommendation, not a menu of equals:

```tsx
screen.getByRole('button', { name: /create issue/i });   // best — role + accessible name
screen.getByLabelText(/title/i);                          // form controls
screen.getByText(/no issues yet/i);                       // static content
screen.getByTestId('create-button');                      // last resort
```

The first query finds the button the way a screen-reader user finds it. It fails if you
turn the `<button>` into a clickable `<div>`, if the label disappears, or if the accessible
name changes to something meaningless. Every one of those is a real regression that a
`getByTestId` would sail past.

This is why the accessibility work in FS03.4 pays off twice. A `<label>` correctly
associated with its input makes `getByLabelText` work. A `role="alert"` on your error
paragraph makes the failure assertable *and* announces it to assistive technology. When a
query is awkward to write, the usual cause is that the markup is not communicating —
treat the friction as a finding about the component, not an obstacle to route around.

A short vocabulary you will use constantly:

```tsx
getBy…     // must exist now, throws a helpful error if not
queryBy…   // may not exist; returns null. The only correct way to assert absence
findBy…    // will exist soon; returns a Promise. Use for anything after an await
getAllBy…  // several matches expected, returns an array
```

Choosing wrongly produces confusing failures. `getByText` for something that has not
arrived yet throws immediately instead of waiting. `queryByText` for something that should
be there returns `null` and gives you `expected null to be in the document`, which tells
you nothing about why.

## Drive the component like a person

`user-event` is a separate package from Testing Library because it does more than dispatch
events. Typing a character fires `keydown`, `keypress`, `input` and `keyup`, respects
`maxLength`, and moves the caret. Clicking checks that the element is not covered or
disabled first. `fireEvent.change` sets a value and none of that is true.

```tsx
const user = userEvent.setup();

await user.type(screen.getByLabelText(/title/i), 'Search returns stale results');
await user.selectOptions(screen.getByLabelText(/priority/i), 'high');
await user.click(screen.getByRole('button', { name: /create issue/i }));
```

Call `setup()` once per test, before rendering. Every method returns a promise and every
one must be awaited — an unawaited `user.click` is the single most common cause of a test
that passes locally and fails in CI, because the assertion runs before React has processed
the event.

The difference is not academic. A disabled submit button silently ignores
`fireEvent.click`, so a test written that way passes whether or not your pending state
works. The same test written with `user.click` fails, which is what you wanted.

## Fake the client, not the component

Here is the technique both earlier lessons pointed at.

Your components must receive their API rather than import it. React Context is the natural
mechanism, because it lets the whole route subtree share one client without threading a
prop through every layer:

```tsx
// resources/app/api/ApiContext.tsx
import { createContext, useContext } from 'react';
import { issueApi, type IssueApi } from './issueApi';

const ApiContext = createContext<IssueApi>(issueApi);

export const useIssueApi = () => useContext(ApiContext);
export function ApiProvider({ api, children }: { api: IssueApi; children: React.ReactNode }) {
  return <ApiContext.Provider value={api}>{children}</ApiContext.Provider>;
}
```

The default value is the real client, so production code does not have to wrap anything.
`IssueApi` is the type you already wrote in FS04.3 — the four operations and their return
types. That type is now doing a second job: it is the contract a fake has to satisfy.

A fake is then a plain object. No library, no `vi.mock`, no module interception:

```tsx
// resources/app/ProjectPage.test.tsx
const fakeApi: IssueApi = {
  listIssues: async () => [
    { id: 'ISS-1', projectId: 'PRJ-1', title: 'Search is slow', status: 'todo', priority: 'high' },
  ],
  createIssue: async () => { throw new Error('not used in this test'); },
  updateIssue: async () => { throw new Error('not used in this test'); },
  deleteIssue: async () => { throw new Error('not used in this test'); },
};

it('renders one row per issue the API returns', async () => {
  render(<ApiProvider api={fakeApi}><ProjectPage projectId="PRJ-1" /></ApiProvider>);

  expect(await screen.findByRole('listitem')).toHaveTextContent('Search is slow');
});
```

Three things are worth noticing.

**TypeScript is checking your fake.** Annotating it `IssueApi` means that when you add a
field to `Issue` in Part 08, this test stops compiling. A fake built with `vi.mock` and an
untyped object literal would keep passing while describing data your API can no longer
return — a test drifting away from reality without ever going red.

**The unused operations throw rather than return.** If the component calls something this
test did not intend, you get a loud error naming it, instead of `undefined` propagating
somewhere strange. Cheap, and it has saved many afternoons.

**There is no network and no server.** This is the property B04 asked for: a suite that
still passes when you close the terminal running the fixture.

When you need to assert that a call happened, or with what, `vi.fn()` gives you a fake that
records:

```tsx
const createIssue = vi.fn(async (draft: IssueDraft) => ({ ...draft, id: 'ISS-9', status: 'todo' }));

await user.click(screen.getByRole('button', { name: /create issue/i }));

expect(createIssue).toHaveBeenCalledWith({ title: 'Search is slow', priority: 'high', projectId: 'PRJ-1' });
```

Use that assertion sparingly. "The button called `createIssue`" is an implementation
claim; "a new row appeared" is a behavioural one. Reach for `toHaveBeenCalledWith` when the
argument itself is the contract — that the client sends `priority` and does not invent an
`id` — which is exactly the discontinuity B04 Stage 2 made you look at.

## Wait for an outcome, never for a duration

Anything that crosses an `await` boundary changes the DOM later. The wrong fix is a timer:

```tsx
await new Promise((resolve) => setTimeout(resolve, 100));   // do not do this
```

That is slow when the machine is fast and flaky when it is loaded. Wait for the thing
itself:

```tsx
expect(await screen.findByRole('alert')).toHaveTextContent('title is required');
await waitForElementToBeRemoved(() => screen.queryByText(/loading issues/i));
expect(await screen.findByRole('listitem')).toBeInTheDocument();
```

`findBy` polls until the element appears or times out, and its failure message shows the
DOM at the moment it gave up — which usually tells you immediately whether the request
never resolved or resolved into the wrong branch.

To assert something does **not** happen, you cannot wait for an absence. Wait for a
positive signal first, then assert:

```tsx
expect(await screen.findByRole('listitem')).toBeInTheDocument();
expect(screen.queryByRole('button', { name: /delete/i })).not.toBeInTheDocument();
```

Asserting the absence immediately after render proves nothing: the button was going to be
absent for a moment regardless. This is the shape of an authorization test — a read-only
member must not see Delete — and getting the order wrong produces a test that passes for a
reason unrelated to permissions.

## Routes under test

`BrowserRouter` reads the real address bar, which a test does not have. `MemoryRouter`
keeps history in memory and takes a starting location:

```tsx
render(
  <MemoryRouter initialEntries={['/issues/ISS-1']}>
    <ApiProvider api={fakeApi}>
      <Routes>
        <Route path="/issues/:issueId" element={<IssuePage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </ApiProvider>
  </MemoryRouter>,
);

expect(await screen.findByRole('heading', { name: /search is slow/i })).toBeInTheDocument();
```

Include the real route definitions, including the catch-all. A test that renders
`<IssuePage />` on its own cannot tell you that the path is registered, and a wrong `path`
is a common and completely invisible bug — the page works when you click a link and 404s
when someone pastes the URL.

The valuable route assertion is navigation, not configuration. Click a real link and check
you arrived:

```tsx
await user.click(screen.getByRole('link', { name: /search is slow/i }));
expect(await screen.findByRole('heading', { name: /search is slow/i })).toBeInTheDocument();
```

Assert on what the destination renders rather than on the URL string. The URL is a detail
the user does not read; the heading is the thing they came for. Route parameter validation
from FS07.1 deserves its own case: render `/issues/not-a-real-id` and prove the not-found
screen appears rather than a crash or an endless spinner.

## Choose the level by what the failure costs

The pyramid is a cost model, not a hierarchy of virtue. Each level answers a question the
others cannot:

```text
parser / pure function   milliseconds   "does this reject a malformed response?"
component + fake client  tens of ms     "can a person create an issue and see the error?"
DALT API behaviour test  hundreds of ms "is a forged PATCH actually refused?"
one browser journey      seconds        "are routing, assets and the server wired together?"
```

Push each claim to the cheapest level that can honestly prove it. Response parsing is a
pure function over `unknown`, so it needs no component at all — those are the cheapest
tests in the track and FS04.3 already had you write them. Whether Delete is *refused* is a
server fact; a component test showing a hidden button cannot prove it, and B06's backend
test can. Whether the bundle, the route and the session cooperate is only true in a
browser, and one journey is enough to know.

The mistake in both directions is claiming the wrong thing. A component test that hides
the Delete button and calls that "authorization" is the dangerous one, because it produces
a green check for a property nobody verified.

## Try it

There is a lab for this one, and it starts red on purpose:

```sh
mkdir -p .dalt/workspace/fs07-frontend-testing
cp -R .dalt/course/fullstack/frontend-testing-lab/starter/. .dalt/workspace/fs07-frontend-testing/
cd .dalt/workspace/fs07-frontend-testing
npm install
npm run typecheck   # clean
npm run test        # 5 passed, 5 failed
```

The five failures all say the same thing:

```text
Could not reach the server: TypeError: Failed to parse URL from /api/projects/PRJ-1/issues
```

Every one of those tests wraps `ProjectPage` in an `ApiProvider` holding a fake that
returns an issue and never touches the network. The component called the network anyway —
because it imports the client instead of asking for it. **A provider only reaches a
component that asks for it.** Two marked lines in `src/ProjectPage.tsx` fix it.

Notice what `npm run typecheck` said: nothing. Choosing the wrong one of two values with
the same type is not a type error. Green types and a red run is the same combination B03
Stage 4 warned about, and it is why the lab pins both.

Read `.dalt/course/fullstack/frontend-testing-lab/README.md` for the rest, including two
tests it deliberately does not ship for you.

Then, in your own project, write four tests in this order — cheapest first:

```text
1. parser        malformed response is rejected           no React at all
2. component     empty list renders the empty state       fake returns []
3. component     whitespace title shows a 422 alert       fake rejects, draft survives
4. route         clicking a row shows the issue detail    MemoryRouter + fake
```

Then make each one fail on purpose before you trust it. Break the component, watch the
test go red, read the failure message, put it back. A test you have never seen fail is a
test you have no evidence about — the same standard this course applies to its own
challenges, applied to your work.

Pay attention to the failure messages while you are there. A good one tells you what was
on screen instead; a bad one says `expected null`. If a failure would not help you at
three in the morning, improve the query before moving on.

## Common mistakes

- Asserting a component rendered, which is true of every component that did not throw.
- Reaching for `getByTestId` first, which makes the test blind to the markup regressing.
- Forgetting `await` on a `user-event` call, producing a race that hides until CI is busy.
- Using `getBy` for something asynchronous, which throws before the element can arrive.
- Asserting absence immediately after render, before the thing had a chance to appear.
- Faking the component under test instead of the boundary around it, which proves nothing.
- Writing a fake that returns a shape the real API cannot produce — a bare array where the
  server sends an envelope, or a 200 where it sends 422.
- Calling hidden controls "authorization" and treating a green frontend test as proof.
- Chasing a coverage percentage, which rewards testing trivial code and ignores the flows
  a user would file a bug about.

## When this goes wrong

**The test hangs on "Loading issues…" and times out.** Your fake never resolved, or the
component is not reading it. Print the DOM with `screen.debug()` and check whether the
provider actually wraps the component under test — a `render(<ProjectPage />)` with the
`ApiProvider` forgotten falls back to the real client and tries the network.

**"An update to X inside a test was not wrapped in act(…)".** Something resolved after the
test finished. Almost always a missing `await`, not a reason to add `act()` by hand;
Testing Library already wraps its own calls. Find the interaction you did not wait for.

**The test passes alone and fails in the suite.** Shared mutable state — a fake array being
pushed into by one test and read by another. Build fixtures inside the test, or in a
`beforeEach`, never once at module scope.

**A refactor broke thirty tests but the application works.** The tests were coupled to
structure. That is information: rewrite them against roles and text, and notice which ones
you cannot express that way, because those were testing something a user cannot see.

```tsx
afterEach(() => {
  vi.clearAllMocks();   // call counts do not leak between cases
});
```

## Exercise

**Goal:** Prove the issue tracker's critical frontend flows with tests that fail when the
product breaks and survive a refactor that does not change behaviour.

**Starting state:** B07's routes, authenticated shell and API client boundary exist, and
the client is reachable through a provider your components read.

**Requirements:** Cover, at minimum — the login form rejecting an empty submission with a
visible alert; issue creation adding exactly one row; a server validation failure showing
a message and preserving the typed draft; an authorization-sensitive control being absent
for a user who lacks the permission; and a filter interaction narrowing the visible list.
Every one uses a typed fake at the API seam. No test may require the DALT server or the
Part 04 fixture to be running.

**Verification:** `npm run test` passes with the server stopped. Then, for each test,
break the behaviour it covers and confirm that test — and ideally only that test — goes
red. Record which test caught which break.

**Mode: tool-run — `npm run test`, `npm run typecheck` and `npm run lint`.** The platform
does not grade this exercise. Your evidence is the suite passing with nothing running, and
your record of each deliberate break.

**Hints:** Start with the empty-list case; it needs the least setup and shakes out your
provider wiring. Type every fake as `IssueApi` rather than leaving it inferred — the
compiler error you get in Part 08 is the point. If a test needs a comment to explain what
it proves, the assertion is probably too far from the user's experience.

## In the project

B07's acceptance requires meaningful frontend tests, and this lesson is where they come
from. The fake client you build here is reused immediately: Part 08 replaces your manual
fetching with TanStack Query, and the same seam lets you test components without a query
client reaching for the network. The tests you write now are also the safety net for B09,
where you extract custom hooks and rearrange files — a refactor is only safe if something
independent can tell you behaviour survived it.

These tests do not replace B06's backend tests and cannot. They complement them: yours
prove the person can work, B06's prove the server cannot be tricked.

## Closed-book checkpoint

1. Why does `getByRole('button', { name: … })` catch regressions that `getByTestId` misses?
2. What does `user.click` do that `fireEvent.click` does not, and when does it matter?
3. Why must a fake be typed with the same interface as the real client?
4. What is wrong with asserting an element is absent immediately after `render`?
5. Which claim can a component test never make about authorization, and what proves it?
6. You have a green frontend suite and a broken product. Name two shapes that failure takes.

## Resources

### Read

- [Testing Library: guiding principles](https://testing-library.com/docs/guiding-principles)
- [Testing Library: about queries and their priority](https://testing-library.com/docs/queries/about)
- [Vitest: mock functions](https://vitest.dev/api/mock.html)

### Go deeper

- [Testing Library: appearance and disappearance](https://testing-library.com/docs/guide-disappearance)
- [Kent C. Dodds: Testing Implementation Details](https://kentcdodds.com/blog/testing-implementation-details)

## You are done when

- [ ] Every test queries by role, label or text, and no flow depends on a test id.
- [ ] Components receive their API client rather than importing it directly.
- [ ] Each fake is annotated with the real client's interface and type-checks against it.
- [ ] `npm run test` passes with the DALT server and the Part 04 fixture both stopped.
- [ ] Asynchronous outcomes are awaited with `findBy…`, and no test contains a `setTimeout`.
- [ ] One route test renders real route definitions through `MemoryRouter` and asserts on
      what the destination shows.
- [ ] I broke the behaviour behind every test and watched the right one go red.
- [ ] `npm run typecheck` and `npm run lint` pass.

## Maintainer source record

Source dossier: `docs/dalt-fullstack/sources/FSO_PART_07.md`.

Official sources: Testing Library guiding principles, query priority and disappearance
guides; Vitest mock function API, linked above.

Versions: Vitest 4.0.18; React Testing Library 16.3.2; `@testing-library/user-event` 14.6.1;
`@testing-library/jest-dom` 6.9.1; jsdom 27.0.1; React Router 7.18.2; React 19.2.3.

Consulted: 2026-08-15.

DALT files inspected: `vite.config.mjs` (`globals: true`, jsdom environment, the
`resources/**` include pattern and the `resources/setup-tests.ts` registration),
`package.json` for the pinned testing toolchain.

Curriculum authority: `CURRICULUM.md` §18, FS07.3; `PROJECT_BLUEPRINT.md` §§40, 43–44.
