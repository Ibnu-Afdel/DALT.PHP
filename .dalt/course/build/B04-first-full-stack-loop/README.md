> **What exists when you finish:** your issue tracker’s React UI reads and changes
> issues through a deliberately temporary server API. You can point to every hop from
> a click to an HTTP request to a JSON response to the changed screen.

## What you are building

Turn the B03 local issue tracker into the first full-stack loop. The fixture API is
course-provided and resettable; your React application is still your own project.

```text
React interaction → typed API client → HTTP → fixture API → JSON → React state → screen
```

The fixture supports `GET /api/issues`, `POST /api/issues`, `PATCH /api/issues/:id`,
and `DELETE /api/issues/:id`. It is deliberately not persistent. Part 05 replaces it
with your DALT and PostgreSQL implementation, so keep its simplicity visible.

## Why this milestone exists

Local state made the UI feel immediate because one process owned all facts. A server
introduces waiting, failure, validation, stale copies, and an agreement about data.
B04 makes those costs concrete before database design and backend implementation add
their own concepts. The outcome is not “a fetch call works.” It is the ability to
trace a real browser-to-server loop and identify which layer owns a failure.

## Before you start

Complete FS04.1–FS04.3 and keep your B03 project on its learner branch. Copy and run
the fixture from a workspace, never from `framework/`, `config/`, or `public/`:

```sh
mkdir -p .dalt/workspace/fs04-react-server
cp .dalt/course/fullstack/react-server-fixture/fixture-api.php .dalt/workspace/fs04-react-server/
cd .dalt/workspace/fs04-react-server
php -S 127.0.0.1:8034 fixture-api.php
```

Confirm the fixture answers before you attach any UI to it, so a later failure has one
possible cause rather than two:

```sh
curl -i http://127.0.0.1:8034/api/issues        # 200 and three seeded issues
curl -i http://127.0.0.1:8034/api/issues/ISS-1  # 404 with a JSON error envelope
```

In the application terminal, run your normal Vite command. The fixture reflects any
`http://localhost:<port>` or `http://127.0.0.1:<port>` origin, so both Vite's default
5173 and the Part 03 lab's 5174 work without editing anything. Note that `localhost`
and `127.0.0.1` are different origins to a browser, so pick one and stay with it. Never
solve a CORS problem by disabling browser protections — the protection is the thing you
are learning to work with.

Keep the fixture in the gitignored workspace. It is course scaffolding with a deliberate
expiry date: Part 05 replaces it with your own DALT routes, and anything you build that
depends on its specific behaviour — the `ISS-` id format, its exact error wording — is
work you will redo.

## Stage 1 — Load server truth

Replace the initial local issue array with `GET /api/issues`. Model loading, ready,
and failure as distinct states. An empty list belongs only to the ready result.

**Working looks like:** reload shows a loading status, then the fixture’s three seed
issues. Stopping the fixture produces a visible failure, not “No issues.”

**Check it yourself:** in Network, locate GET, read its URL, status, and JSON body.
Change the route to a missing path briefly; confirm an HTTP response failure differs
from a stopped-server failure, then restore the correct route.

## Stage 2 — Send a create request

Make the existing form POST JSON. Preserve its draft on a failed response; clear it
only after the server returns 201 and append the returned issue rather than a guessed
copy. Render a specific pending state while that form submits.

**Working looks like:** a valid title creates exactly one issue; whitespace gets a
422 message and leaves the typed title available for correction. The priority you
chose survives the round trip — the id, the status and the `projectId` do not, because
the server assigns those. Which fields are the client's to send is a real part of the
contract, and this is the first time you can see the line.

**Check it yourself:** inspect the POST request payload, `Content-Type`, 201 response,
and 422 response. Stop the fixture before one submission and prove the pending control
recovers to a network-failure message.

## Stage 3 — Update and delete through the server

Wire mark-done to PATCH and remove to DELETE. Use the server-returned issue for PATCH;
for the 204 DELETE response, remove the known ID without trying to parse JSON. If the
deleted issue is selected, clear or replace the selection deliberately.

**Working looks like:** one row enters a pending state, then updates; deleting a row
does not leave a detail panel displaying a ghost issue.

**Check it yourself:** Network shows PATCH 200 with a changed issue and DELETE 204 with
no response body. Verify unrelated controls remain usable while one action is pending.

## Your B03 tests break here, and that is the interesting part

The moment Stage 1 lands, two of the four tests B03 made you write stop passing. If
they rendered the whole page, they now render a component that fetches on mount, and
in a test there is no server — so they hang on the loading state and fail on an
element that never arrives.

Do not delete them, and do not go looking for a mocking library. Faking a client for a
component test is Part 07's subject, and reaching for it now means adopting a tool
before you have the problem it solves. Two moves get you green with what you already
know:

**Test the form through its prop, not through the page.** `CreateIssueForm` receives
its submit as a function. A test can pass any function it likes — including one that
returns the server's rejection — with no network anywhere:

```tsx
render(<CreateIssueForm onSubmitDraft={async () => 'title is required'} />);

await user.type(screen.getByLabelText(/title/i), '   ');
await user.click(screen.getByRole('button', { name: /create issue/i }));

expect(screen.getByRole('alert')).toHaveTextContent('title is required');
expect(screen.getByLabelText(/title/i)).toHaveValue('   ');
```

That is B03's "whitespace-only title is rejected" and B04 Stage 2's "preserves the
draft", in one test, without a server. If it is awkward to write, the component is
holding state it should have been given — which is the design feedback the test is
really for.

**Test the parsers directly.** FS04.3 makes the point: they are ordinary functions
over `unknown`, so they need no server, no browser and no component. Those are the
cheapest tests in the track and they cover Stage 4's "malformed data never reaches the
issue list".

What you should *not* have when the milestone ends is a test that quietly needs the
fixture running. A suite that fails when you close a terminal is a suite nobody trusts.

## Stage 4 — Extract the client boundary

Move URLs, methods, headers, JSON parsing, response validation, and normalized errors
into a small typed issue API module. Components should call domain operations such as
`listIssues` and `createIssue`, then decide how to render pending and error states.

**Working looks like:** all four operations work exactly as before, and searching the
application source finds `fetch(` only in the API module.

**Check it yourself:** temporarily point the module at a wrong base URL. Every flow
should fail through the same deliberate error path; restore it and confirm all flows.
Deliberately reject one response shape in the parser and verify malformed data never
reaches the issue list.

## Stage 5 — Trace and recover

Use the browser’s Network panel and application UI together. Trigger one success, one
422 validation failure, one HTTP failure, and one network failure. For each, state
which layer observed it and which layer made the next decision.

**Working looks like:** you can reproduce every state without editing random code and
can recover the application by restarting the fixture or correcting input.

**Check it yourself:** write a four-row trace: user action, request evidence, response
evidence, screen result. The status label is not evidence by itself; include what you
observed in Network.

Then throttle the connection to a slow preset and repeat the successful create. Loading
and pending states that are invisible on loopback become the whole experience at real
latency, and this is the only point in the milestone where you can judge them. Note
whether the page jumps when data arrives, and whether a spinner flashes for a request
too fast to need one.

## Stage 6 — Point at something else

Change only your base URL to a port with nothing listening, reload, and watch every
flow fail. Then change it back.

**Working looks like:** one edit, in one file, changes where the whole application
talks to — and every screen degrades into its failure state rather than into a blank
page or a console error nobody sees.

**Check it yourself:** count the files you had to touch. If the answer is more than one,
Stage 4's boundary is not finished, and Part 05 will make you pay for it when the
fixture is replaced by your own server. Fix it now while the only cost is moving a
string.

## Decisions you have to make

- Will your client module accept an `AbortSignal`, or will the effect own cancellation?
- How will an empty ready list differ visually from loading and failure?
- Which controls become pending for create, PATCH, and delete? Avoid a whole-page lock.
- How will you represent an API error without making the client own JSX?
- Where will the base URL live so Part 05 can replace the fixture without a search-and-replace hunt?

## Acceptance criteria

Nothing here is checked automatically. Read each item against software you ran.

- [ ] A reload makes a real GET request and renders loading before its successful list.
- [ ] The screen distinguishes an empty successful list, a network failure, and an HTTP failure.
- [ ] Create sends JSON POST, shows pending state, and renders the returned 201 issue once.
- [ ] Server validation 422 is visible near the form and preserves the draft.
- [ ] Mark done sends PATCH and uses the returned changed issue.
- [ ] Delete sends DELETE, handles its 204 body correctly, and clears stale selection.
- [ ] Each mutation has scoped pending feedback without freezing unrelated controls.
- [ ] A typed client module owns HTTP mechanics and no component reinvents `fetch`.
- [ ] Runtime checks reject a malformed response before it reaches UI state.
- [ ] I recorded Network evidence for a success, validation failure, HTTP failure, and network failure.
- [ ] Changing where the application points is a one-file edit.
- [ ] I saw the loading and pending states at throttled speed, not only at loopback speed.
- [ ] Nothing outside the gitignored workspace depends on the fixture.
- [ ] The B03 tests that broke when the fetch arrived are passing again, without a
      mocking library and without needing the fixture to be running.
- [ ] `npm run typecheck`, `npm run lint`, `npm run test`, and `npm run build` pass.

## Prove it to yourself

Close the editor and draw the exact path for “mark ISS-41 done,” including the method,
request body, success status, response shape, state update, and rendered change. Then
draw the same path for a blank-title create. Reopen the project only to correct the
parts you could not reconstruct. Finally explain why this is not persistence: restart
the fixture and observe what survives.

## What this unlocks

Part 05 replaces the temporary API with DALT routes, validation, queries, and
PostgreSQL. The React side keeps its client boundary but gains a backend whose behaviour
you own and can test. You have separated the two problems instead of debugging a new
database, a new routing layer, and a new fetch call all at once.
