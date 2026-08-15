> **What exists when you finish:** an issue tracker with real users, where a second
> person's crafted HTTP request — not just a hidden button — provably cannot read or
> change what they are not entitled to, and a test proves it for every rule.

## What you are building

Turn the persistent issue tracker into a small multi-user application. It has users, cookie-backed server sessions, a current-user endpoint, CSRF-protected mutations, workspace membership, issue creators, and backend tests that prove critical rules. The React interface may remain modest; the API must enforce its rules when a request comes from an HTTP client rather than from the UI.

## Why this milestone exists

Authentication changes every mutation from "the browser says this is user 7" to "the server resolved this session to a known identity." Authorization then makes identity useful: users see only workspaces they belong to, and cannot perform actions their role or ownership rule forbids.

This milestone is relentlessly evidence-driven, and for a specific reason. Every other milestone so far fails visibly when you get it wrong — a broken list is a blank screen. Security fails *invisibly*. A login screen, hidden buttons, and a satisfied-looking UI are all perfectly compatible with an API that lets any authenticated user delete anyone's data. There is no screen you can look at to find out. The only way to know is to make the hostile request yourself and inspect the row afterwards, which is what most of this milestone asks you to do.

It is also the milestone with the highest cost of being wrong. A missing filter is an inconvenience; broken object-level authorization is the most commonly exploited API vulnerability there is, and it is exploited by people typing a different number into a URL.

## Before you start

Complete FS06.1–FS06.3. Keep the B05 API tests in place before you change their expected anonymous behaviour — you want to watch them fail and update them deliberately, not delete them and start over.

Work in application code, migrations, routes, and your tests. Do not place project code in `framework/`, `config/`, `public/`, or `.dalt`; deleting `.dalt` must still leave your application working.

Read DALT's real session, authenticator, and CSRF middleware before choosing your route setup:

```sh
less framework/Core/Authenticator.php
less framework/Core/Session.php
less framework/Core/Middleware/Csrf.php
less framework/Core/Middleware/Auth.php
```

Three of those return page-shaped responses that are wrong for a JSON API — `abort()` renders HTML, the CSRF middleware returns plain text with 419, and `auth` redirects to `/login`. Decide how your API routes handle each before you build on them, rather than discovering it through a parser error in Part 07.

Use a separate test database, or a rollback strategy, so test fixtures cannot touch development data. You will be creating and destroying users constantly.

## Stage 1 — Establish users and safe credentials

Create migrations for users, workspace memberships, and issue creators. Store a unique normalized login identifier and a PHP password hash, not a password. Decide a minimal role vocabulary only if it changes a real permission decision.

**Working looks like:** duplicate identifiers and impossible foreign keys fail at the database, while a created user has a verifier that `password_verify()` accepts and no public response includes.

**Check it yourself:** create a user through the documented path, inspect the database in development, and prove the stored value is not the submitted password. Attempt duplicate and invalid-relation cases in a test or rolled-back transaction.

## Stage 2 — Make identity persist and expire

Implement documented login, logout, and current-user behavior. On successful login, create a server-side session and rotate its identifier. On logout, invalidate server state and expire the cookie. Define your anonymous `/me` result and keep public user JSON free of password material.

**Working looks like:** a cookie-aware client can login, call `/me` later, logout, and then receive the anonymous result even if it replays the old cookie.

**Check it yourself:** perform that sequence with an HTTP client cookie jar, not only React state. Inspect response status and safe JSON at every step; never print a live session ID or password hash as debugging output.

## Stage 3 — Require intentional protected mutations

Apply authentication and CSRF protection to every state-changing issue route. Expose or bootstrap a CSRF token in a way your client can send as a header or form value. Derive issue creator from authenticated identity rather than accepting `creatorId` in JSON.

**Working looks like:** anonymous mutation receives 401; an authenticated mutation without valid CSRF proof receives 419 and changes no row; the correct authenticated request succeeds with its server-derived creator.

**Check it yourself:** make all three requests directly. Query the affected issue or count rows after each rejected case. A response error without unchanged database state is not enough evidence.

## Stage 4 — Enforce workspace and owner rules

Choose and document who can see each workspace and who can create, update, and delete issues. Implement checks from session identity, membership, creator, and any small role rule. Keep frontend hiding as usability, never security control.

**Working looks like:** a non-member cannot read workspace data; a denied authenticated user receives the documented response; an owner-only direct PATCH or DELETE cannot alter the row.

**Check it yourself:** use two separately authenticated cookie jars. Attempt reads and mutations across membership and ownership boundaries, then inspect PostgreSQL to prove the denied mutation had no effect.

## Stage 5 — Make the rules executable

Update B05 tests for intentional authentication changes and add behavior tests for credentials, session lifecycle, CSRF, membership, ownership, and database effects. Run a focused failure while building, then the full suite. A test must make a specific claim; “request returned an error” does not distinguish authorization from a server bug.

**Working looks like:** removing an ownership check, CSRF middleware, or session invalidation makes the corresponding test fail for the named reason.

**Check it yourself:** deliberately remove one check at a time in a safe local branch or temporary edit, observe the relevant failure, restore it, and run `php artisan test`.

## Stage 6 — Try to break it on purpose

Spend twenty minutes attacking your own API as an outsider would. Use curl with two cookie jars
and work down a list, writing the expected result before each attempt:

```sh
curl -c alice.jar -X POST http://127.0.0.1:8000/api/login \
  -H 'Content-Type: application/json' --data '{"email":"alice@example.com","password":"..."}'

# Now, as Bob, go after Alice's issue directly.
curl -b bob.jar -i http://127.0.0.1:8000/api/issues/1
curl -b bob.jar -i -X DELETE http://127.0.0.1:8000/api/issues/1 -H 'X-CSRF-Token: ...'
```

Work through: incrementing an id you do not own; a mutation with no session; a mutation with a
session but no CSRF token; a create with `"creator_id": 1` smuggled into the body; a create with
`"is_admin": true`; and replaying Alice's cookie after she logs out.

**Working looks like:** every attempt is refused with the status you documented, and every
refused mutation leaves the database exactly as it was.

**Check it yourself:** query PostgreSQL after each attempt rather than trusting the response. For
the smuggled fields, read the stored row and confirm the value came from the session and the
default, not from the request. Anything that succeeds when it should not is the finding this
stage exists to produce — write it down, fix it, and add the test that would have caught it.

## Decisions you have to make

- What login identifier and normalization rule fit your application?
- Which safe user fields does `/me` return, and what anonymous status does it use?
- Which mutations can any workspace member make, and which require creator or owner?
- Will a non-member receive 403 or a deliberate 404, and how will every route stay consistent?
- How will React receive and send CSRF proof without treating it as identity?

## Acceptance criteria

Nothing here is checked automatically. Read each item against software you actually built and ran.

- [ ] Users have unique identifiers and password hashes; plaintext passwords and hashes never appear in public JSON.
- [ ] Login creates a rotated server-side session and `/me` resolves a safe current user later.
- [ ] Invalid credentials do not create an authenticated session or reveal whether the user exists.
- [ ] Logout invalidates the server session and an old cookie cannot recover identity.
- [ ] Cookie security attributes and HTTPS expectations are intentional and documented for deployment.
- [ ] Anonymous state-changing requests fail with 401 and do not write data.
- [ ] Authenticated state-changing requests without valid CSRF proof fail with 419 and do not write data.
- [ ] Issue creators are derived from authenticated identity, not accepted from request JSON.
- [ ] Workspace membership protects reads and mutations according to the documented rule.
- [ ] A direct owner/membership bypass attempt receives the documented denial and leaves the database unchanged.
- [ ] Every denial rule also has a passing test for its permitted case.
- [ ] A smuggled `creator_id` or `is_admin` in a create body provably cannot reach a row.
- [ ] I attacked my own API with two cookie jars and recorded the result of every attempt.
- [ ] I removed one check at a time, watched the named test fail, and restored it.
- [ ] Backend tests cover success and failure paths with isolated deterministic data.
- [ ] Deleting `.dalt` would leave my application working.
- [ ] `php artisan test`, `npm run typecheck`, `npm run lint`, and `npm run build` pass.

## Prove it to yourself

Close the editor and trace a malicious-looking request from a foreign page or a second user:
which cookie is attached, what token is missing, where authentication resolves identity, which
membership or ownership fact is checked, which status returns, and why no row changes. Then
trace a permitted create and name the exact line where the server — not the browser — selected
the creator id.

Then answer two questions without looking. First: for every rule you implemented, which test
proves the permitted case still works? If any rule has only a denial test, you cannot yet
distinguish a working rule from a broken endpoint. Second: if you deleted your entire React
application tonight and shipped only the API, which of your protections would still hold? The
answer should be all of them.

## What this unlocks

Part 07 can build a login view, authenticated application shell, routing, and frontend tests against stable server semantics. It should improve the experience around 401, 403, 419, and current-user state; it must not become the only place permission is enforced. Part 11 can later compare this application protection with database RLS.
