# FS04.2 — Mutating server data

Lesson ID: FS04.2  
Title: Mutating server data  
Part: 04 — React and server  
Order: 2  
Status: Published  
Estimated effort: 90–120 minutes  
Difficulty: Applied  
Prerequisites: FS04.1 — Fetching data and effects  
Project milestone: B04 — First full-stack loop  
Primary source dossier: `FSO_PART_02.md`  
Last reviewed: 2026-08-14

## Why this matters

Loading asks the server a question. Creating, changing, and deleting ask it to make a durable decision. That distinction changes what the screen is allowed to claim. A draft in an input belongs to the browser; an issue created by POST belongs to the server only after a successful response. A button click is not proof that the server accepted anything.

This is the first place the local-interface mental model breaks in a useful way. With an array in one component, adding a row and updating truth were the same action, performed at the same instant, with no possibility of disagreement. Across HTTP they are two actions separated by a delay, with at least four outcomes: accepted, rejected for a reason the user can fix, rejected for a reason they cannot, and no answer at all. Good UI makes the delay and each outcome visible rather than displaying confidence it has not earned.

## Before you start

Required:

- FS04.1 — Fetching data and effects.
- The fixture API running at `http://127.0.0.1:8034`.

Recommended first:

- FS03.3 — Forms and state design.
- FS01.2 — Modules, async JavaScript and failure.

Going deeper in DALT Core — optional:

- [01-request-lifecycle](/learn/lessons/01-request-lifecycle) — how a backend request crosses middleware and routing. It is not required for this fixture exercise.

Confirm the fixture's behaviour with curl before you attach any UI to it. You are establishing what the truth is, so that later, when the screen disagrees, you know which side to suspect:

```sh
curl -i -X POST http://127.0.0.1:8034/api/issues \
  -H 'Content-Type: application/json' \
  --data '{"title":"Inspect the POST response"}'

curl -i -X PATCH http://127.0.0.1:8034/api/issues/ISS-41 \
  -H 'Content-Type: application/json' --data '{"status":"done"}'

curl -i -X DELETE http://127.0.0.1:8034/api/issues/ISS-43
```

Expect 201 with an issue for POST, 200 with the changed issue for PATCH, and 204 with no response body at all for DELETE. Now provoke a rejection:

```sh
curl -i -X POST http://127.0.0.1:8034/api/issues \
  -H 'Content-Type: application/json' --data '{"title":"   "}'
```

```text
HTTP/1.1 422 Unprocessable Content
Content-Type: application/json; charset=utf-8

{"error":{"code":"validation_failed","message":"title is required"}}
```

422 is a response the UI must handle, not a broken network. The request arrived, was understood, and was refused — and the server told you why in a form you can put on screen.

## By the end

- send JSON request bodies with the appropriate HTTP method;
- distinguish success, validation failure, unexpected failure, and no response;
- hold a draft separately from a pending submission;
- use the returned resource as the update to displayed server data;
- disable only the specific action that is pending;
- explain why a 204 response cannot be parsed as JSON.

## Predict before reading

1. You append the draft locally before POST finishes. What should happen if the server returns 422?
2. Is `response.json()` valid after a 204 DELETE response?
3. While one issue is being marked done, should every button in the application be disabled?
4. A PATCH returns `{ id, title, status }`. Which copy should the list display: the value you sent or the value returned?

## Mental model

```text
browser draft ──POST/PATCH/DELETE──> server decision
      │                                  │
      └── pending / validation UI <── response status + body
                                         │
                                  returned representation
                                         │
                                  displayed issue state
```

Three kinds of state are in play, and collapsing any two of them causes a specific bug:

| State | Owner | Lives for | Collapse it and… |
|---|---|---|---|
| Draft | Browser | Until submitted successfully | a failed request wipes what the user typed |
| Pending | One operation | Duration of that request | one row saving freezes the whole page |
| Issue list | Server, cached here | Until the next response | the screen shows things the server rejected |

The draft changes on every keystroke and is never automatically server truth. The issue list is a *cached representation* of server state — a copy that can be stale, and that the server's response is always allowed to correct. Pending state belongs to a particular operation: "creating", or "saving ISS-41", never to the application as a whole.

## 1. Build requests deliberately

For JSON requests, say what the body is:

```ts
const response = await fetch('http://127.0.0.1:8034/api/issues', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ title }),
});
```

`Content-Type` tells the server how to parse the bytes. `JSON.stringify` produces those bytes. Neither validates your draft. Omit the header and a server is entitled to refuse or misparse the body — and the failure will look like your JSON is wrong when the problem is that you never said it was JSON.

Keep client-side validation for fast feedback, but understand what it is for:

```ts
// Fast feedback, not authority. The server checks this again, and the
// server's answer is the one that decides whether the issue exists.
const trimmed = title.trim();
if (trimmed === '') {
  setFormError('Give the issue a title.');
  return;
}
```

Read the status before the body. For a create handler, the branches are genuinely different recovery paths and deserve to be written as such:

```ts
type CreateResult =
  | { kind: 'created'; issue: Issue }
  | { kind: 'invalid'; message: string }
  | { kind: 'failed'; message: string };
```

`created` appends a row. `invalid` shows a message beside the form and keeps the draft. `failed` covers everything else — a 500, a 404 on a URL you got wrong, or a rejected promise because nothing answered. The user can retry a `failed`; they must *change something* to recover from an `invalid`. A UI that renders both as "Something went wrong" has thrown away the only information that distinguishes them.

Written out, the whole function is short, and every branch corresponds to something you saw with curl:

```ts
async function createIssue(title: string): Promise<CreateResult> {
  let response: Response;

  try {
    response = await fetch('http://127.0.0.1:8034/api/issues', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title }),
    });
  } catch {
    // Rejected promise: nothing answered. There is no status to inspect.
    return { kind: 'failed', message: 'Could not reach the server.' };
  }

  if (response.status === 201) {
    const issue: unknown = await response.json();
    return { kind: 'created', issue: issue as Issue };
  }

  if (response.status === 422) {
    const body: unknown = await response.json();
    return { kind: 'invalid', message: validationMessage(body) };
  }

  return { kind: 'failed', message: `Server returned ${response.status}.` };
}
```

Notice the `try` wraps only the `fetch` call, not the whole function. A `try` around everything would catch the `SyntaxError` from a malformed JSON body and report it as "could not reach the server" — which is the opposite of what happened. Narrow the `try` to the operation whose failure you are actually describing.

`validationMessage` is where the error envelope gets read. The fixture returns `{"error":{"code":"validation_failed","message":"title is required"}}`, and the temptation is to reach straight through it with `body.error.message`. That is a runtime boundary, and FS02.5's rule applies: the server is a different program, and a 422 from a proxy or a load balancer will not have that shape at all.

```ts
function validationMessage(body: unknown): string {
  if (
    typeof body === 'object' && body !== null && 'error' in body &&
    typeof body.error === 'object' && body.error !== null &&
    'message' in body.error && typeof body.error.message === 'string'
  ) {
    return body.error.message;
  }
  return 'The server rejected this issue.';
}
```

That is verbose, and FS04.3 replaces it with a parser you write once. Write it the long way here so you can see exactly how many assumptions the short version was making.

## 2. Update only after the server answers

The simplest honest strategy is **confirm, then update**. Wait for a successful returned issue, then replace or append it immutably:

```ts
// Create: append what the server actually made.
setIssues((current) => [...current, created]);

// Update: replace by id, using the server's representation.
setIssues((current) =>
  current.map((issue) => (issue.id === updated.id ? updated : issue)),
);

// Delete: remove by id.
setIssues((current) => current.filter((issue) => issue.id !== deletedId));
```

The functional form matters because it computes from the newest committed list even when several updates are scheduled in the same tick. `setIssues([...issues, created])` reads `issues` from the render that scheduled it, so two quick creates lose one. Never mutate an existing issue object or call `splice` on state: React needs a new array to detect the change, and your later debugging needs a before-state that still exists.

Prediction 4 asks which copy to display, and the answer is always the returned one. The server may normalise a title, assign an id, set a `createdAt`, or clamp a status you did not expect. Displaying what you sent shows the user their request; displaying what came back shows them reality, and the two diverge exactly when it matters most:

```ts
// Wrong: shows the user's intent, not the server's decision.
setIssues((current) => [...current, { id: crypto.randomUUID(), title, status: 'todo' }]);

// Right: the server said what exists.
const created: unknown = await response.json();
setIssues((current) => [...current, created as Issue]);
```

You will hear about optimistic updates: change the screen immediately, then roll back on failure. They can make a product feel fast, but they turn every mutation into a rollback design problem, and they are not a default. This part teaches the slower correct loop first. Once you can trace the confirmed path, the tradeoff is yours to make consciously.

## 3. PATCH means "change this part"

The fixture takes `PATCH /api/issues/ISS-41` with `{"status":"done"}` and returns the whole updated issue. Two things there are conventions worth understanding rather than copying.

PATCH sends a *partial* update: the fields you name change, and the fields you omit are left alone. PUT sends a *complete replacement*: the resource becomes exactly the body you sent, and anything you omitted is gone. The difference shows up the first time two people edit one issue:

```ts
// PATCH: I am changing the status. I make no claim about the title.
await fetch(url, { method: 'PATCH', headers, body: JSON.stringify({ status: 'done' }) });

// PUT: the issue is now exactly this. If someone renamed it while my
// form was open, my stale title silently overwrites theirs.
await fetch(url, { method: 'PUT', headers, body: JSON.stringify(wholeIssue) });
```

For an issue tracker where several people touch the same row, PATCH is the safer default, and it is what you will implement in Part 05. Reach for PUT when the client genuinely owns the whole resource and replacing it wholesale is the intent.

The second convention: a mutation returns the resulting resource. It does not have to — 204 with no body is legal for PATCH too — but returning it saves the client a follow-up GET and, more importantly, gives it the server's version of what just happened. That is the same argument as prediction 4, and it is why the PATCH handler uses the response rather than the value it sent:

```ts
async function setStatus(id: string, status: Issue['status']): Promise<void> {
  const response = await fetch(`http://127.0.0.1:8034/api/issues/${id}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ status }),
  });

  if (!response.ok) {
    throw new Error(`Update failed with ${response.status}`);
  }

  const updated: unknown = await response.json();
  setIssues((current) =>
    current.map((issue) => (issue.id === id ? (updated as Issue) : issue)),
  );
}
```

## 4. DELETE is shaped differently, and that is the point

Prediction 2: no. A 204 means "no content", and calling `response.json()` on it throws a `SyntaxError` about unexpected end of input — an error that looks like a parsing bug and is actually a protocol misreading. Branch on the status before you touch the body:

```ts
async function deleteIssue(id: string): Promise<void> {
  const response = await fetch(`http://127.0.0.1:8034/api/issues/${id}`, {
    method: 'DELETE',
  });

  // 204 carries nothing. There is no body to read, so do not read one.
  if (response.status === 204) {
    setIssues((current) => current.filter((issue) => issue.id !== id));
    return;
  }

  if (response.status === 404) {
    // Already gone. The user's goal is satisfied; reconcile quietly.
    setIssues((current) => current.filter((issue) => issue.id !== id));
    return;
  }

  throw new Error(`Delete failed with ${response.status}`);
}
```

The 404 branch is worth arguing about, and you should decide rather than default. The user asked for the issue to not exist. It does not exist. Treating that as an error tells them their action failed when it did not. This is the same reasoning that makes DELETE *idempotent* — sending it twice leaves the world in the same state as sending it once, which is why a retry after a timeout is safe for DELETE and is not safe for POST.

## 5. Pending state has a scope

Give each operation its own pending marker rather than one page-wide boolean:

```tsx
const [isCreating, setIsCreating] = useState(false);
const [pendingId, setPendingId] = useState<string | null>(null);

async function handleCreate(event: React.FormEvent) {
  event.preventDefault();
  setIsCreating(true);
  setFormError(null);
  try {
    const result = await createIssue(draft);
    if (result.kind === 'created') {
      setIssues((current) => [...current, result.issue]);
      setDraft('');                      // cleared only on success
    } else {
      setFormError(result.message);      // draft survives
    }
  } finally {
    setIsCreating(false);                // ends on every path, including throws
  }
}
```

The `finally` is not stylistic. Without it, any early return or thrown error leaves `isCreating` true forever and the form permanently disabled — a bug that only appears when something else has already gone wrong, which is the worst time for the retry button to stop working.

In the markup, scope the feedback to the thing that is actually busy:

```tsx
<button type="submit" disabled={isCreating}>
  {isCreating ? 'Creating…' : 'Create issue'}
</button>

<li aria-busy={pendingId === issue.id}>
  <span>{issue.title}</span>
  <button onClick={() => void handleDelete(issue.id)} disabled={pendingId === issue.id}>
    Delete
  </button>
</li>
```

`aria-busy` on the row tells assistive technology that this region is mid-update, without claiming the whole page is. Prediction 3's answer: no — disabling every control because one row is saving is the interface equivalent of a global lock, and it is just as unpleasant to use.

Disabling the submit button while `isCreating` is true is not only a courtesy to the user; it is the cheapest defence against a double submit. A POST is *not* idempotent — two identical POSTs create two issues, and a user who clicks twice because the first click seemed to do nothing has told the server to make two. The button state and the pending state must therefore be the same fact, which is why `isCreating` drives both.

That defence is a client-side convenience and nothing more. A determined double-submit, a flaky connection that retries, or a user with two tabs open will all still reach the server twice. Real protection is server-side — a uniqueness constraint or an idempotency key — and Part 05 is where that argument is settled. Notice the shape of the reasoning, because it recurs for the rest of the track: the client makes the common case pleasant, and the server makes the guarantee.

After a successful create, clear the draft. After a failed create, keep it. That is a one-line rule with a large effect: discarding input turns a recoverable validation error into repeated work, and users who have been burned once start composing in a text editor and pasting. After a successful delete, clear the selection if the deleted issue was selected; otherwise the detail panel points at data the server no longer has.

## 6. Watch each outcome on purpose

```sh
# Success: read the request payload and the response body in Network.
# Validation failure: submit a whitespace-only title in the UI.
# Unexpected failure: point the create URL at /api/wrong and submit.
# No response: stop the fixture (Ctrl-C), then submit.
```

Four attempts, four visibly different screens. If any two look the same to a user, you have collapsed two facts into one, and §1's `CreateResult` union is the place to fix it — not the JSX.

There is a fifth case that people skip, and it is the one that teaches the most: a mutation that succeeds on the server while the client believes it failed. Provoke it by adding a delay to the fixture's POST handler and navigating away mid-request, or simply by stopping the fixture in the window between it writing state and the response reaching you. The issue exists. Your list does not show it. Nothing reloads, so nothing corrects it.

You cannot design that case away, only decide how to recover from it. The recovery available to you now is a re-fetch: after any `failed` mutation, the safest claim your UI can make is not "that did not work" but "I do not know what happened", and the way to find out is to ask the server again. Notice that "confirm then update" already limits the damage here — the screen is *behind* the server, which is recoverable, rather than *ahead* of it, which is a lie. An optimistic UI that rolled back would have shown the user their issue disappearing after they watched it appear.

## Try it

Use Network's request payload and response tabs for one create, one PATCH, and one DELETE. Confirm the DELETE row shows 204 with an empty response body. Change the title to whitespace and verify the page shows the fixture's 422 message *without* removing what you typed. Stop the fixture immediately before clicking create, and verify the pending state ends with a network error and an enabled button. Then restart it and retry; do not reload the page merely to hide a failure.

## Common mistakes

- Updating the list on click rather than after a confirmed response.
- Clearing a draft before knowing whether the server accepted it.
- Calling `json()` on a 204 DELETE response.
- Handling every non-2xx response as a generic "network error."
- Ending pending state outside a `finally`, so a thrown error disables a control forever.
- A single `isLoading` boolean for initial loading, every mutation, and every failure.
- Disabling every page control while one row is saving.
- Building the appended row from the draft instead of from the server's response.

## When this goes wrong

Expect to see requests you did not write. A PATCH or DELETE carrying `Content-Type: application/json` is not a "simple" cross-origin request, so the browser sends an `OPTIONS` preflight first and only sends your real request if the response permits the method and header. In Network you will see two rows per mutation. That is correct, and the fixture answers preflights with 204. If your mutation fails but the preflight is missing from Network, the browser refused before sending anything — check the URL's scheme and host. If the preflight is present and the real request never follows, read the preflight's response headers: the method or the `Content-Type` header is not in the allowlist. GET requests in FS04.1 needed none of this, which is why the problem appears for the first time now.

If the server receives no JSON, inspect the Network request headers and payload before editing PHP — a missing `Content-Type` looks exactly like a server-side parsing bug from the client side. If POST returns 422, read the response body; do not infer the reason from the button label. If a row reappears after deletion, check whether your state update filtered by the id the action returned or by a stale selected object captured in a closure. If a request is duplicated, look for two event paths — a submit handler and a click handler on the same button, or a missing `event.preventDefault()` — before blaming `fetch`.

## Exercise

**Goal:** Make your create form, mark-done action, and delete action operate through the fixture API.

**Starting state:** FS04.1 loads and displays remote issues.

**Requirements:** POST a title, PATCH an issue status, and DELETE an issue. Model pending state per operation. Preserve drafts on error, use the server's returned issue after success, handle 204 without parsing a body, and visibly distinguish 422, network failure, and unexpected HTTP failure.

**Verification:** In Network, identify methods, JSON bodies, statuses, and response bodies for all three actions. Deliberately submit whitespace, stop the fixture for a network failure, and retry successfully.

**Mode: manual browser evidence and TypeScript compiler output.** The platform does not inspect your implementation; your evidence is the requests and visible state transitions you observed.

**Hints:** Implement POST first. Make 422 visible before adding PATCH. For DELETE, branch before body parsing. Keep the operation's `finally` block responsible for ending its pending state.

## In the project

B04 now becomes a real full-stack loop: a React interaction creates a request, the fixture server decides, JSON returns, and React replaces its local representation. The PHP fixture is intentionally simple and temporary. Part 05 replaces it with your DALT routes and PostgreSQL; do not smuggle persistence, authentication, or retry logic into this lesson. What you are building here is the shape of the loop, and the shape survives the replacement.

## Closed-book checkpoint

1. What belongs to a draft, pending, and server-list state respectively?
2. Why is 422 different from a rejected `fetch` promise?
3. Why confirm before updating in this part?
4. Why must DELETE branch before `response.json()`?
5. What information should a pending control expose to a user?
6. Why is a retried DELETE safe when a retried POST is not?

## Resources

### Read

- [MDN: Using the Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch)
- [MDN: HTTP request methods](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods)
- [React: updating arrays in state](https://react.dev/learn/updating-arrays-in-state)

### Go deeper

- [MDN: 422 Unprocessable Content](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/422)
- [MDN: idempotent methods](https://developer.mozilla.org/en-US/docs/Glossary/Idempotent)
- [WAI: status messages](https://www.w3.org/WAI/WCAG22/Understanding/status-messages.html)

## You are done when

- [ ] Create returns a 201 issue that appears once in the list.
- [ ] Blank title shows a 422 validation message and preserves the draft.
- [ ] Mark done uses PATCH and renders the server-returned issue.
- [ ] Delete uses DELETE, handles 204 without parsing JSON, and clears stale selection.
- [ ] Each pending control explains its own operation without freezing unrelated controls.
- [ ] Pending state ends in a `finally`, so a failure never leaves a control disabled.
- [ ] A stopped fixture produces a recoverable network error.
- [ ] `npm run typecheck` and `npm run test` pass after the changes.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_02.md`
- Official sources: MDN Fetch API, HTTP method/status and idempotency references; React updating arrays in state; W3C WAI status-message guidance
- Versions: React 19.2.3; TypeScript 5.9.3; PHP 8.4 fixture server
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/course/fullstack/react-server-fixture/fixture-api.php`
- Curriculum authority: `CURRICULUM.md` §14 FS04.2
- Laravel bridge: optional only — Part 05 teaches the DALT implementation that will produce these HTTP results
