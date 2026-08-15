# FS06.3 — Authorization and ownership

Lesson ID: FS06.3
Title: Authorization and ownership
Part: 06 — Testing, users and authentication
Order: 3
Status: Published
Estimated effort: 120–150 minutes
Difficulty: Integration
Prerequisites: FS06.2 — Users, passwords, sessions and CSRF
Project milestone: B06 — Multi-user protected system
Primary source dossier: FSO_PART_04.md
Last reviewed: 2026-08-14

## Why this matters

After login, the server knows who sent a request. That is necessary, and it says nothing about
whether they may read another workspace, update someone else's issue, or delete a project.
Authentication answers "who?" Authorization answers "may this known identity perform this action
on this resource?" They are different questions, and a system that answers only the first is a
system where every user can do everything.

The failure mode here is specific and extremely common. It is called broken object-level
authorization, and it looks like this: the UI hides the Delete button for issues you do not own,
everyone is satisfied, and then someone sends the DELETE request directly. The button was never
the check. Nothing in a browser is a check — the browser is running on a machine the attacker
controls, and a hidden control is a suggestion.

The issue tracker becomes genuinely multi-user only when database relationships and application
checks express the boundary. Workspace membership limits visibility; ownership or a small role
rule limits mutations. These rules need behavioural tests with **two** identities, because an
authorization check that has only ever seen one user has not been shown to separate anything.

## Before you start

Complete FS06.2. You need two users who can log in and a working `GET /api/me`.

```sh
less framework/Core/Authenticator.php        # id(), user(), check(), guest()
less framework/Core/Middleware/Auth.php      # what the 'auth' middleware actually does
less framework/Core/functions.php            # authorize()
```

Two framework facts shape everything below. `Authenticator::id()` is the canonical server-derived
identity — it reads the session, never the request body. And `authorize()` is a one-line guard:

```php
function authorize(bool $condition, int $status = 403, string $message = ''): void
{
    if (!$condition) {
        abort($status, $message);
    }
}
```

Note that it routes through `abort()`, which renders **HTML** — the same content-type mismatch
you met in FS05.1 and again with the 419 in FS06.2. For JSON routes, return
`Response::json(...)` with the right status instead, or wrap `authorize()` for your API. Decide
once and apply it consistently.

Going deeper in DALT Core — optional:

- [Authentication](/learn/lessons/04-authentication) and [middleware](/learn/lessons/03-middleware) are optional reference; they are not gates for this track.

## By the end

- distinguish authentication from authorization, and 401 from 403;
- model users, workspace membership, issue creator, and a minimal role where needed;
- derive actor identity on the server instead of trusting request JSON;
- enforce read and mutation rules in the application layer;
- prove a direct HTTP bypass attempt fails with no database effect.

## Predict before reading

1. A user owns an issue but is removed from its workspace. Can they still edit it?
2. Why is `creatorId` in a POST body not evidence of ownership?
3. Which response is correct when an anonymous request deletes an issue: 401 or 403?
4. If React hides Delete, what stops a crafted `DELETE` request?

## Mental model

~~~text
request → session → authenticated actor
                    ↓
resource lookup → workspace membership → action rule → response / write
                   (can see?)             (can edit?)
~~~

Authorization is a decision made close to the resource, using server-derived facts. First find
the actor from the session. Then find the issue, with the workspace and creator facts needed to
decide. Membership answers the outer scope; creator or role answers the action. The client may
express intent — "delete issue 42" — but it never supplies the actor, the membership, or the
conclusion.

## 1. Model the relations you actually need

Add users to the domain deliberately:

```sql
CREATE TABLE workspace_memberships (
  workspace_id BIGINT NOT NULL REFERENCES workspaces(id) ON DELETE CASCADE,
  user_id      BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  role         VARCHAR(20) NOT NULL DEFAULT 'member'
               CHECK (role IN ('member', 'owner')),
  PRIMARY KEY (workspace_id, user_id)
);

ALTER TABLE issues
  ADD COLUMN creator_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT;

CREATE INDEX idx_memberships_user ON workspace_memberships (user_id);
```

Three decisions there are worth defending. The composite primary key says a user is a member of
a workspace once, with one role — no duplicate rows to disagree with each other. `CASCADE` on
both foreign keys is right here, unlike on issues: a membership has no meaning once either side
is gone, which is the test for CASCADE. And `RESTRICT` on `creator_id` means deleting a user is
refused while their issues exist, forcing you to decide what happens to that history rather than
silently destroying it.

Note that `CASCADE` on memberships makes prediction 1 concrete: removing a user from a workspace
deletes the membership row, so the ownership check and the membership check now disagree. They
are separate facts, and §2 is about composing them in the right order.

Choose a small role set only if it expresses a real product rule. Do not add a generic RBAC
framework, a permissions table, or a policy engine for two actions — that is the same
over-generalisation FS04.3 warned about, arriving in a more tempting costume.

The creator comes from the session, never from JSON:

```php
// Right: the server decides who is acting.
$creatorId = $auth->id();

$database->query(
    'INSERT INTO issues (project_id, title, creator_id) VALUES (?, ?, ?)',
    [$projectId, $title, $creatorId],
);
```

```php
// Wrong: the client asserts who it is, and the server believes it.
$creatorId = $input['creator_id'];
```

That is prediction 2. A request body is a proposal from an untrusted program; `creator_id` in it
is the client claiming an identity, which is the same category of mistake as trusting a hidden
form field. Your FS05.1 allowlist already excludes it — this is precisely the field that
allowlist existed to keep out, and the FS06.1 test that proves an unaccepted field cannot reach
the database is now protecting something that matters.

Foreign keys protect referential truth: they guarantee `creator_id` names a real user. They
cannot decide whether that user is currently allowed to act, because that is not a fact about
references. It is application authorization.

## 2. Place the checks at the server boundary

Compose the rules in an order that reads like the product rule, and stop at the first failure:

```php
// PATCH /api/issues/{id}
$actorId = $auth->id();
if ($actorId === null) {
    return jsonError(401, 'unauthenticated', 'Sign in to continue.');
}

$issue = $database->query(
    'SELECT i.id, i.title, i.status, i.creator_id, p.workspace_id
     FROM issues i JOIN projects p ON p.id = i.project_id
     WHERE i.id = ?',
    [$id],
)->find();

if ($issue === false) {
    return jsonError(404, 'not_found', 'That issue does not exist.');
}

$membership = $database->query(
    'SELECT role FROM workspace_memberships WHERE workspace_id = ? AND user_id = ?',
    [$issue['workspace_id'], $actorId],
)->find();

if ($membership === false) {
    return jsonError(403, 'forbidden', 'You do not have access to that workspace.');
}

// The action rule. State it; "authorized user" is too vague to implement.
$mayEdit = (int) $issue['creator_id'] === $actorId || $membership['role'] === 'owner';
if (!$mayEdit) {
    return jsonError(403, 'forbidden', 'Only the issue creator can change it.');
}
```

Notice that the single query loads the workspace alongside the issue. Looking the issue up
globally and then forgetting to check membership is the most common version of this bug, and
selecting `workspace_id` in the same statement makes the omission visible.

Do not use `WHERE creator_id = ?` as the only protection when membership is also a rule. A
creator who has left the workspace still matches that clause — prediction 1 again. Conversely,
membership alone does not enforce an owner-only edit. Both facts are needed, and the order
matters for the response: check membership first so a non-member gets a consistent answer
regardless of who created the issue.

Prediction 3's answer is 401. Use 401 when there is no authenticated identity at all — the
request has not yet said who it is. Use 403 when there is a real actor who is denied by policy.
Collapsing both into 403 tells an anonymous client to give up when signing in would have worked;
collapsing both into 401 tells a signed-in user to sign in again, which they will do, twice,
before filing a confused bug report.

There is a third defensible option, and it is a product decision rather than a technical one:
returning 404 instead of 403 for a resource the actor may not see. 403 confirms the issue exists,
which leaks information — an attacker can enumerate valid ids by reading status codes. 404 hides
that at the cost of a more confusing experience for legitimate users who genuinely lost access.
Pick one, write it in the API contract, and apply it consistently across every route; the worst
outcome is routes that improvise differently.

## 3. Middleware or handler?

DALT gives you an `auth` middleware, and it is tempting to reach for middleware for the whole
problem. The division is clean once you see it: **middleware knows about the request, the handler
knows about the resource.**

"Is anyone signed in?" depends only on the session, so it belongs in middleware and applies
identically to every protected route:

```php
$router->patch('/api/issues/{id}', 'api/issues/update.php')->only(['auth', 'csrf']);
```

"May *this* actor edit *this* issue?" depends on a row that has not been fetched yet. Middleware
would have to load the issue, and then the handler loads it again — two queries, and two places
that can disagree about what the issue is. That check belongs in the handler, next to the lookup
it depends on.

The rule generalises: a check that can be answered before touching the database is a middleware
concern; a check that needs the resource is a handler concern. Object-level authorization is
always the second kind, which is part of why it gets skipped — there is no single place to bolt
it on, so it has to be written deliberately at every resource.

That is also why the composite lookup in §2 matters so much. If every handler that touches an
issue selects `workspace_id` alongside it, the membership check has everything it needs and its
absence is conspicuous. If handlers select only what they render, the check needs an extra query,
and an extra query is exactly what a tired developer skips.

One caution about `only(['auth', ...])`, and it is not hypothetical. Read
`framework/Core/Middleware/Auth.php`:

```php
if ($this->auth->guest()) {
    $this->auth->rememberIntended($request);

    return Response::redirect('/login');
}
```

That middleware is written for pages. An unauthenticated JSON request gets a **302 to
`/login`**, not a 401 — so `fetch` follows the redirect, receives an HTML login page, and your
parser reports malformed JSON. The error will point at your client while the cause is in the
middleware, which is a particularly expensive way to lose an afternoon.

This is the third time in Part 05 and Part 06 that a framework helper has been correct for pages
and wrong for a JSON API: `abort()` renders HTML, the CSRF middleware returns plain text, and now
`auth` redirects. That is not a criticism of the framework — DALT was built for server-rendered
pages, and these helpers do the right thing there. It is a reminder that "a helper exists" and
"this helper fits my context" are different claims, and the second one has to be checked. Write
a small `api.auth` middleware that returns your 401 envelope, and use it on API routes.

## 4. What the frontend does with 401 and 403

The React client is not the security boundary, and it still has a job. The two statuses mean
different things to a user, and mapping both to "something went wrong" wastes information the
server went to some trouble to provide:

```tsx
if (error instanceof ApiError && error.status === 401) {
  // Identity is missing or expired. Signing in fixes this.
  redirectToLogin();
} else if (error instanceof ApiError && error.status === 403) {
  // Identity is fine; permission is not. Signing in again changes nothing.
  setBanner('You do not have permission to change this issue.');
}
```

Sending a 403 to the login page is a small bug with an unpleasant shape: the user signs in
successfully, is returned to the same screen, fails again, and concludes the application is
broken. That is FS04.3's error normalisation earning its keep — the status survived the transport
layer precisely so this decision could be made here.

Hiding controls is still worth doing. A Delete button that only appears for issues you may delete
is better interface design, and this lesson's point is not that the UI should stop doing it — it
is that the UI doing it is not a check. Both statements are true at once: hide the button *and*
enforce the rule, and never let the presence of the first substitute for the second.

There is one more consequence worth planning for. Once the frontend needs to know what the user
may do in order to render sensibly, it needs those facts from the server — typically as fields on
the resource, such as `"canEdit": true`. Send them as computed booleans derived from the same
server-side rule, never as raw role names the client interprets. If the client decides what
`role: "owner"` permits, you have duplicated the policy in a place you cannot trust, and the two
copies will drift.

## 5. Prove the browser is not the security boundary

This is the plausible-fake check for authorization, and it is the reason the lesson exists. Write
tests with two independent identities:

```php
test('a non-member cannot read another workspace', function () {
    $alice = signIn('alice@example.com');
    $issue = createIssue($alice, 'Alice private work');

    $bob = signIn('bob@example.com');            // a second, separate session

    $response = api('GET', "/api/issues/{$issue['id']}", as: $bob);

    expect($response->statusCode)->toBe(403);    // or 404 — whichever you documented
});

test('a direct PATCH by a non-member changes nothing', function () {
    $alice = signIn('alice@example.com');
    $issue = createIssue($alice, 'Original title');

    $bob = signIn('bob@example.com');

    $response = api('PATCH', "/api/issues/{$issue['id']}", ['title' => 'Bob was here'],
        as: $bob, token: csrfToken($bob));

    expect($response->statusCode)->toBe(403);

    // The assertion that makes this a real test. A handler that returns 403 after
    // writing passes every line above it.
    expect(issueRow($issue['id'])['title'])->toBe('Original title');
});

test('a member who is not the creator cannot edit', function () {
    $alice = signIn('alice@example.com');
    $issue = createIssue($alice, 'Alice work');
    $bob = signIn('bob@example.com');
    addMember($issue['workspace_id'], $bob, role: 'member');

    // Bob can now see it, and still cannot change it. Two rules, tested separately.
    expect(api('GET', "/api/issues/{$issue['id']}", as: $bob)->statusCode)->toBe(200);
    expect(api('PATCH', "/api/issues/{$issue['id']}", ['status' => 'done'],
        as: $bob, token: csrfToken($bob))->statusCode)->toBe(403);
});

test('the creator can edit their own issue', function () {
    $alice = signIn('alice@example.com');
    $issue = createIssue($alice, 'Alice work');

    expect(api('PATCH', "/api/issues/{$issue['id']}", ['status' => 'done'],
        as: $alice, token: csrfToken($alice))->statusCode)->toBe(200);
});
```

The last test is not optional padding. A handler that returns 403 to absolutely everyone passes
the first three perfectly, and a rule that denies everything is not authorization — it is an
outage. Every deny test needs its matching allow test, exactly as the CSRF pair did in FS06.2.

Two practical notes. Include valid CSRF proof in these requests: a test that dies at CSRF
returned 419 and never reached the authorization code, so it proves nothing about the rule you
were testing — and it will pass whether the rule exists or not. And assert the *durable state*
after every denied mutation. A UI-only implementation is very convincing when a button
disappears; only the unchanged row shows that the server made the decision.

## Try it

Create Alice, Bob, workspace A with Alice as creator and member, and one Alice-created issue.
Then walk the sequence deliberately, writing down the expected result before you run each one:
anonymous GET, Alice GET, Bob GET before membership, Bob PATCH before membership, Bob GET after
membership, Bob PATCH after membership, Alice PATCH.

Inspect the row after every rejected mutation. Then temporarily remove the ownership check,
confirm the relevant test fails, and restore it — a check you have never watched fail is a check
you are trusting rather than one you have verified.

## Common mistakes

- Using a submitted `userId`, `creatorId`, or role as an authorization fact.
- Returning 403 to every failure, including anonymous requests with no identity.
- Checking only the React UI, or relying on a disabled control.
- Looking an issue up globally, then forgetting workspace membership.
- Using `WHERE creator_id = ?` alone when membership is also a rule.
- Assuming a foreign key proves a user is currently allowed to act.
- Testing a denied request without CSRF proof, so middleware stops it first.
- Writing deny tests without the matching allow test, so a total outage looks secure.
- Asserting the status of a denied mutation without asserting the row is unchanged.

## When this goes wrong

Trace one request in order: session identity, resource lookup, membership lookup, action rule,
SQL write. Log safe ids and decision names in development, never a password or a raw session id.

If a test returns 419, supply valid CSRF evidence before diagnosing authorization — you have not
reached it. If it returns 401 where you expected 403, the session was not sent; if 403 where you
expected 401, you are denying before identifying. If it returns 404 unexpectedly, decide whether
the resource query was intentionally scoped or whether you have lost the distinction between
missing and denied.

Fix policy or contract explicitly. Never change a status code merely to make a test pass when the
underlying product rule is still unclear — that converts an open question into a silent decision
nobody made.

## Exercise

**Goal:** Make workspace and issue rules true at the API boundary.

**Starting state:** Login, logout, `/api/me`, and CSRF-protected mutations work.

**Requirements:** Add memberships and issue creators through migrations. Derive the creator from
the authenticated session on create. Define and document who may read a workspace, create an
issue, update an issue, and delete an issue. Implement the checks in the application layer, and
document your 401 / 403 (or deliberate 404) contract.

**Verification:** With two independently authenticated users, make a direct request for another
workspace and an owner-only mutation. Assert the chosen denied status *and* that the database row
is unchanged. Then demonstrate the permitted request for each rule, so that no rule is proven only
by denial.

**Mode: tool-run — Pest behavior tests and direct HTTP requests with separate sessions.** The
platform does not grade this exercise; your server-side tests are its evidence.

**Hints:** Begin with membership-protected reads before owner-only writes. Keep the lookup and the
decision near each other. Test a real permitted mutation last, with a valid CSRF token.

## In the project

B06 turns the issue tracker from a shared demo into a protected multi-user system. The frontend
may hide controls for clarity, but it must treat 401 and 403 as information from the server rather
than as states it decides for itself.

Part 07 adds routes, an authenticated shell, and frontend tests on top of this backend contract.
Part 11 may add row-level security as a further boundary — and it is worth saying now that it must
never be used as an excuse to remove these checks. Defence in depth means several independent
boundaries; replacing an application rule with a database rule is not depth, it is relocation.

## Closed-book checkpoint

1. What fact distinguishes 401 from 403?
2. Why must an issue creator come from the session rather than from JSON?
3. What two relationships can be required for an owner-only workspace action?
4. Why does a deny test need a matching allow test?
5. How does a direct HTTP test disprove UI-only authorization?
6. What does later row-level security add, and what does it not replace?

## Resources

### Read

- [OWASP: Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)
- [OWASP: Insecure Direct Object Reference Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Insecure_Direct_Object_Reference_Prevention_Cheat_Sheet.html)
- [PostgreSQL: constraints](https://www.postgresql.org/docs/current/ddl-constraints.html)

### Go deeper

- [OWASP API Security Top 10: Broken Object Level Authorization](https://owasp.org/API-Security/editions/2023/en/0xa1-broken-object-level-authorization/)
- [Laravel: authorization](https://laravel.com/docs/12.x/authorization)

## You are done when

- [ ] Users, workspace memberships, and issue creators have intentional relational constraints.
- [ ] Issue creator identity comes from the authenticated session, never from the request body.
- [ ] Anonymous and authenticated-but-denied requests have distinct documented behaviour.
- [ ] My 403-versus-404 choice is written in the API contract and applied consistently.
- [ ] Workspace reads and mutations are authorized by server-side facts.
- [ ] Direct bypass attempts leave protected rows unchanged, and I asserted the row.
- [ ] Every denial rule also has a test proving the permitted case still works.
- [ ] Tests use two identities and valid CSRF proof, so they reach the authorization rule.
- [ ] I removed one check, watched the right test fail, and restored it.
- [ ] `php artisan test` passes.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_04.md`
- Official sources: OWASP Authorization, IDOR and API Security Top 10 guidance; PostgreSQL constraints documentation
- Versions: PHP 8.4; PostgreSQL as configured by the learner
- Consulted: 2026-08-14
- DALT files inspected: `framework/Core/Authenticator.php`; `framework/Core/functions.php`; `framework/Core/Middleware/Auth.php`; `framework/Core/ExceptionHandler.php`; `tests/Unit/AuthenticatorTest.php`
- Curriculum authority: `CURRICULUM.md` §17 FS06.3
- Laravel bridge: Laravel policies and gates express comparable rules; authorization here stays an explicit DALT application-layer decision with a behavioural test, so the rule and its proof are both visible.
