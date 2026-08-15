# FS08.2 — Mutations, invalidation and optimistic UI

Lesson ID: FS08.2
Title: Mutations, invalidation and optimistic UI
Part: 08 — Server and application state
Order: 2
Status: Published
Estimated effort: 100–130 minutes
Difficulty: Integration
Prerequisites: FS08.1 — Client state versus server state
Project milestone: B08 — Intentional state architecture
Primary source dossier: FSO_PART_06.md
Last reviewed: 2026-08-15

## Why this matters

A query answers “what remote fact should this screen render?” A mutation answers “what request
changes remote fact, and how does every affected screen become truthful afterward?” Without that
boundary, each submit handler grows its own pending flag, error branch, list patch, and refetch
sequence. It may look correct on the current page while a detail screen, counter, or another tab
quietly remains stale.

The server remains the writer of record. A successful PATCH may normalize data, reject a now-invalid
transition, add timestamps, or apply authorization that the browser could not know. The client can
make an interaction feel immediate, but it needs a recovery story. Invalidation is the dependable
default: mark the relevant query snapshots stale and let their query functions obtain the answer.

## Before you start

Complete FS08.1 with a root QueryClient and at least one issue query. Preserve the B06 behavior
tests: a mutation's friendly client state never replaces CSRF, session, membership, or ownership
enforcement.

```sh
npm run typecheck
npm run lint
npm run test
```

Going deeper in DALT Core — optional:

- None. Server authorization is already taught inside this standalone Fullstack track.

## By the end

- define a mutation function that returns server-confirmed data;
- distinguish mutation pending, error, and success from query state;
- invalidate exactly the query keys a write can change;
- implement and roll back one justified optimistic interaction;
- reason about stale responses and concurrent writes without promising impossible certainty.

## Predict before reading

1. After closing issue 12, which cached screens might now be wrong?
2. Is a button click evidence that the database changed?
3. Which interaction is worse if it appears to succeed then snaps back: a status toggle or a long comment?

## Mental model

```text
event → mutation request → server validation / authorization / write → response
                   ↓                                      ↓
            pending or error                      invalidate or reconcile query cache
```

Query state describes a read snapshot. Mutation state describes one attempt to change remote state.
Keep them separate: a detail query can be successful while the status-change button is pending.
The mutation function belongs beside the API functions, not inside a presentational button.

```ts
export async function changeIssueStatus(issueId: number, status: IssueStatus): Promise<Issue> {
  const response = await fetch(`/api/issues/${issueId}`, {
    method: 'PATCH', credentials: 'include',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
    body: JSON.stringify({ status }),
  });
  if (!response.ok) throw new Error('Could not change issue status');
  return response.json() as Promise<Issue>;
}
```

Do not make an Effect watch a `status` state and issue the PATCH. A person initiated the write, so
the event handler invokes `mutate`. Effects synchronize with an external system after render; they
are not a replacement for an explicit command.

## Invalidate the affected facts

Use the same key factory from FS08.1. When a status changes, the issue detail can change and both
the open and closed lists may change. Invalidate broad enough keys that every related query can
refetch, but do not erase the entire cache because naming affected data felt inconvenient.

```tsx
const queryClient = useQueryClient();
const statusMutation = useMutation({
  mutationFn: ({ issueId, status }: { issueId: number; status: IssueStatus }) => changeIssueStatus(issueId, status),
  onSuccess: (issue) => {
    queryClient.invalidateQueries({ queryKey: ['issues', issue.id] });
    queryClient.invalidateQueries({ queryKey: ['projects', issue.projectId, 'issues'] });
  },
});
```

Invalidation does not claim the cache was updated; it marks matching snapshots stale. Active
observers can refetch, and inactive screens will check when they next need the data. That is safer
than hand-editing five caches when the server response may contain fields you forgot. When a small,
complete server response is available, `setQueryData` can make the exact detail instant, but it is
an optimization rather than a reason to skip reconciliation.

```tsx
onSuccess: (issue) => {
  queryClient.setQueryData(queryKeys.issue(issue.id), issue);
  queryClient.invalidateQueries({ queryKey: ['projects', issue.projectId, 'issues'] });
}
```

## Give the interaction an honest pending state

Disable only the action whose request is in flight, label the work, and render a mutation error
near the control. Do not disable the whole application because one comment is being posted; do not
allow a second identical click to race the first unless repeated commands are intentional.

```tsx
<button type="button" disabled={statusMutation.isPending}
  onClick={() => statusMutation.mutate({ issueId: issue.id, status: 'closed' })}>
  {statusMutation.isPending ? 'Closing…' : 'Close issue'}
</button>
{statusMutation.isError ? <p role="alert">{statusMutation.error.message}</p> : null}
```

For issue creation, use the resolved server record to navigate or render the new id. Do not invent
an id in the browser. For a comment, keeping the draft until the server succeeds is often clearer
than instantly clearing it: a failed request has not consumed the person's writing.

```tsx
createIssueMutation.mutate(formValues, {
  onSuccess: (issue) => navigate(`/issues/${issue.id}`),
});
```

## Four callbacks, and one that will not fire

A mutation has four hooks into its lifecycle. Their order is fixed, and choosing the wrong
one produces bugs that only appear under conditions you will not reproduce by clicking:

```text
onMutate    before the request leaves. Cancel refetches, snapshot, patch optimistically.
            Its return value becomes `context` in the two below.
onSuccess   the server accepted. Reconcile with the record it returned.
onError     the request or the server refused. Roll back using `context`.
onSettled   after either outcome. Invalidate here, so both paths reconcile.
```

Invalidate in `onSettled` rather than `onSuccess`. A failed write still means your cache is
suspect — the request may have reached the server and failed on the way back, and a rollback
restores what you *believed* rather than what is true.

Now the part that catches people. You can pass callbacks in two places, and they do not
behave the same:

```tsx
// A: on the hook — fires even if this component unmounts mid-request.
const createIssue = useMutation({
  mutationFn: api.createIssue,
  onSettled: () => queryClient.invalidateQueries({ queryKey: ['issues'] }),
});

// B: on the call — skipped entirely if the component unmounts first.
createIssue.mutate(draft, {
  onSuccess: (issue) => navigate(`/issues/${issue.id}`),
});
```

Put cache work in **A** and screen work in **B**. Invalidation must happen whether or not
the person navigated away — otherwise closing a dialog while its request is in flight leaves
a stale list behind it, which is a bug nobody reproduces because nobody clicks that fast on
purpose. Navigation belongs in **B** precisely because it should *not* happen if the person
already left; redirecting someone who has moved on to another screen is worse than doing
nothing.

The other reason to prefer **A** for cache work: `useMutation` callbacks receive the same
`context` from `onMutate`, so rollback logic lives beside the snapshot that created it.

## Not every write is a status toggle

Your project has four kinds of write, and they want different handling. The curriculum asks
you to decide, not to apply one recipe:

```text
issue status     toggle, reversible, frequent    → optimistic, invalidate detail + both lists
issue creation   server assigns id and status    → confirmed, then navigate to the new record
comment          long, expensive to retype       → confirmed; keep the draft until 201
assignee/label   small, reversible, list-visible → optimistic if you can name every list it
                                                    appears in; otherwise confirmed
```

Comments deserve a note because the instinct is wrong. Clearing a comment box on submit
feels responsive right up to the request that fails, at which point you have deleted
someone's paragraph. Clear it on success. If the box is empty and the request failed, the
person has no recourse and no reason to trust the application again.

Assignee and label changes look like the status toggle and are usually harder, because the
row may appear in a filtered list keyed by assignee. Changing the assignee removes the issue
from the list you are looking at. If you patch optimistically without handling membership,
the row vanishes and reappears — enumerate the affected keys or take the confirmed path.

## Optimism is a trade, not a default

An optimistic update changes a cache before the server confirms the write. It can make a reversible,
frequent status change feel excellent. It also adds a snapshot, a rollback, cancellation of an
in-flight refetch, and a conflict story. Do not use it automatically for creation, a long comment,
or a destructive operation merely because the API is fast.

```tsx
const closeMutation = useMutation({
  mutationFn: () => changeIssueStatus(issue.id, 'closed'),
  onMutate: async () => {
    await queryClient.cancelQueries({ queryKey: queryKeys.issue(issue.id) });
    const previous = queryClient.getQueryData<Issue>(queryKeys.issue(issue.id));
    queryClient.setQueryData<Issue>(queryKeys.issue(issue.id), (old) => old ? { ...old, status: 'closed' } : old);
    return { previous };
  },
});
```

The context returned by `onMutate` is the recovery receipt. Restore it only if the request fails;
then always invalidate at settlement, because another writer may have changed the resource while
this request was travelling.

```tsx
onError: (_error, _variables, context) => {
  queryClient.setQueryData(queryKeys.issue(issue.id), context?.previous);
},
onSettled: () => queryClient.invalidateQueries({ queryKey: queryKeys.issue(issue.id) }),
```

This example patches the detail only. A real optimistic list patch must handle membership in open
and closed filters as well. If you cannot enumerate every affected representation and rollback,
choose invalidation after confirmation. Correctly slower is better than briefly claiming a write
that the server refused.

Assembled, in one piece, so you can see the shape rather than four fragments:

```tsx
const closeMutation = useMutation({
  mutationFn: () => api.updateIssue(issue.id, { status: 'done' }),

  onMutate: async () => {
    await queryClient.cancelQueries({ queryKey: queryKeys.issue(issue.id) });
    const previous = queryClient.getQueryData<Issue>(queryKeys.issue(issue.id));
    queryClient.setQueryData<Issue>(queryKeys.issue(issue.id),
      (old) => (old ? { ...old, status: 'done' } : old));
    return { previous };
  },

  onError: (_error, _variables, context) => {
    if (context?.previous) queryClient.setQueryData(queryKeys.issue(issue.id), context.previous);
  },

  onSettled: () => {
    queryClient.invalidateQueries({ queryKey: queryKeys.issue(issue.id) });
    queryClient.invalidateQueries({ queryKey: queryKeys.issues(issue.projectId) });
  },
});
```

Read it once more as four claims rather than as code. *I am about to change this; stop any
refetch that would overwrite me; here is what it looked like before.* Then: *it failed, put
it back* — or nothing, if it succeeded. Then, either way: *ask the server what is actually
true now.*

The `cancelQueries` line is the one that gets deleted by someone tidying up, because
removing it breaks nothing that a click can demonstrate. It matters when a background
refetch was already in flight when the person acted: that response arrives carrying
pre-write data and quietly undoes the optimistic patch, producing a UI that flickers back
to the old value roughly one time in twenty.

## Race and staleness reasoning

Two people can act on the same issue. The browser cannot solve that with a cache. The API should
validate a status transition and return an error or current record as its contract specifies. After
settlement, invalidation asks for the authoritative snapshot. A later response should not silently
overwrite a newer decision simply because it arrived last; scope mutation state to the interaction
and use the server's result as the reconciliation point.

```text
Tab A closes issue → optimistic “closed”
Tab B reopens issue → server accepts later write
Tab A settles       → invalidate → fetch server's current “open” truth
```

That visual change is not a bug if it explains a real concurrent outcome. Provide a useful message
when the API can identify a conflict. Never infer authorization failure from any error string;
retain the explicit 401, 403, 404, and 419 handling designed in Part 07.

## A failed write is not one thing

"Show the error message" is where most mutation error handling stops, and it is why
applications tell people to check their spelling when their session has expired. Your DALT
API already distinguishes these; the frontend has to keep the distinction:

```text
422  the input is wrong          → message beside the field, keep the draft, let them fix it
403  this user may not do this   → explain the permission; do not offer a retry that cannot work
401  the session is gone         → recover to login, preserving the draft if you can
419  CSRF proof is stale         → refresh the token and retry once; never surface the raw error
409  someone else changed it     → show the current server state and ask what they want
5xx  the server broke            → apologise, offer retry; the input was probably fine
network  it never arrived        → offer retry; the write may or may not have happened
```

The `IssueApiError` you built in FS04.3 already carries a `kind`, so the component branches
on structure rather than parsing prose:

```tsx
{mutation.isError && (
  mutation.error instanceof IssueApiError && mutation.error.kind === 'validation'
    ? <p role="alert">{mutation.error.message}</p>
    : <p role="alert">Could not save that. <button onClick={() => mutation.reset()}>Try again</button></p>
)}
```

The last row of the table is the uncomfortable one. A request that never got a response may
still have been processed — the write succeeded and the reply was lost. This is why
invalidating in `onSettled` matters: after a network failure, the only honest thing you can
do is ask the server what actually happened rather than assume nothing did.

`mutation.reset()` clears the error state so the control returns to its resting appearance.
Without it, an error message can outlive the situation that produced it, sitting beside a
form the person has already corrected.

## Try it

Convert one status change to a mutation with a visible pending state and targeted invalidation.
Use a normal confirmed mutation first. Temporarily make the API return a failure and observe an
alert while the existing issue data remains truthful. Then implement optimism for that one status
change, force the request to fail, and watch the old status return. Remove the temporary failure
before keeping the result.

```text
click close → disabled “Closing…” → server success → relevant queries refetch
click close → disabled “Closing…” → server failure → alert; confirmed state remains
optimistic close → request failure → snapshot restored; query reconciles
```

Then produce three different failures from the same control and confirm the screen responds
differently to each: a 422 next to the field, a 403 explained without a retry button, and a
stopped server offering one. If all three render the same sentence, the taxonomy above is
not wired up yet.

Finally, write the rollback test. It is the one people skip, and it is the only evidence
that `onError` restores anything — an optimistic update whose rollback has never run is a
feature you are hoping works.

## Deciding whether optimism is worth it

"Do not make everything optimistic" is easy to agree with and hard to apply at three in the
afternoon when a button feels sluggish. Make it a decision you can defend, with four
questions:

```text
1. Is the action reversible?          closing an issue: yes.  deleting it: no.
2. How often does the server refuse?  a status toggle: rarely. a create with
                                      server-side validation: often.
3. Can you enumerate every cache
   entry the change affects?          detail + open filter + closed filter + count?
4. What does a rollback look like
   to the person who acted?           a row that silently moves back is confusing
                                      unless something explains why.
```

Optimism is worth it when the first two are *yes, rarely* and you can answer the third
completely. Toggling an issue between open and closed qualifies. Creating an issue usually
does not: the server assigns the `id`, `status` and `projectId` — the discontinuity B04
Stage 2 made you look at — so an optimistic row is a fiction with a fake identifier that
has to be reconciled the moment the real one arrives.

Destructive actions are the clear no. An optimistically deleted issue that reappears three
seconds later is worse than a half-second spinner, because the person has already moved on
believing the work is done.

The honest default is a confirmed mutation with a good pending state. Reach for optimism
when a specific interaction is measurably worse without it, and say in a comment which of
the four questions justified it.

## Testing a mutation

The same seam, once more. A mutation test asks a different question than a query test: not
"what does the screen show?" but "what did the client send, and what happened afterwards?"

```tsx
it('sends the status change and shows the confirmed result', async () => {
  const user = userEvent.setup();
  const updateIssue = vi.fn(async (id: string) => ({ ...seed, id, status: 'done' as const }));
  renderWithQuery(<IssueDetail issueId="ISS-1" />, fakeApi({ updateIssue }));

  await user.click(await screen.findByRole('button', { name: /close issue/i }));

  expect(updateIssue).toHaveBeenCalledWith('ISS-1', { status: 'done' });
  expect(await screen.findByText(/done/i)).toBeInTheDocument();
});
```

Here `toHaveBeenCalledWith` earns its place: the request payload *is* the contract with your
DALT API, and a component that quietly sends `{status: 'closed'}` when the server expects
`'done'` is exactly the bug this catches.

Rollback deserves its own test, and it is the one people skip:

```tsx
it('restores the previous status when the server refuses', async () => {
  const user = userEvent.setup();
  renderWithQuery(<IssueDetail issueId="ISS-1" />, fakeApi({
    updateIssue: async () => { throw new IssueApiError('conflict', 'http'); },
  }));

  await user.click(await screen.findByRole('button', { name: /close issue/i }));

  expect(await screen.findByRole('alert')).toBeInTheDocument();
  expect(screen.getByText(/todo/i)).toBeInTheDocument();   // never became 'done'
});
```

Remember `retry: false` on the test query client. Without it, a mutation that throws is
retried, and your test spends its timeout budget failing three times before reporting.

## Common mistakes

- Invalidating everything with a bare `invalidateQueries()` after every write. It works,
  which is why it survives, and it refetches screens nobody is looking at.
- Invalidating too narrowly and forgetting the list the changed row belongs to, so the
  detail updates and the list behind it does not.
- Patching the cache by hand with `setQueryData` *instead of* invalidating, rather than as
  an optimistic step that still settles with an invalidation.
- Returning nothing from `onMutate`, so `onError` has no snapshot to restore and the
  rollback silently does nothing.
- Forgetting `cancelQueries` in `onMutate`, so an in-flight refetch lands after the
  optimistic patch and overwrites it with pre-write data.
- Rolling back on error but not invalidating on settle, leaving a cache that is right by
  luck until the next write.
- A single `isPending` shared by every row, so clicking one issue disables the whole list.
- Optimistically creating a row with a temporary id and never reconciling it, producing a
  duplicate when the real record arrives.
- Treating any mutation error as a validation message and rendering an HTTP 403 next to a
  text input as though the user typed something wrong.
- Testing that the mutation function was called and stopping there, which proves the click
  wiring and nothing about what the person ends up seeing.

## When this goes wrong

If a mutation is permanently pending, inspect the promise returned by the mutation function; a
function that starts `fetch` but does not return it completes too early or loses errors. If rollback
shows `undefined`, make sure the snapshot and key describe the same resource. If a list remains
wrong, identify every query representation affected by the write and invalidate their shared key
prefix.

```ts
// Wrong: the caller cannot wait for this request or receive its rejection.
function saveIssue(input: IssueInput) {
  fetch('/api/issues', { method: 'POST', body: JSON.stringify(input) });
}
```

## Exercise

**Goal:** Make one real issue mutation synchronize its remote representations deliberately.

**Starting state:** An issue list or detail query is working from FS08.1.

**Requirements:** Add a mutation function at the API boundary, a pending control, a visible error,
and targeted invalidation. Choose one interaction for optimism only if you can write its rollback
and settlement path; otherwise record why confirmed invalidation is the better choice.

**Verification:** Observe the control while pending, force a server failure, check list and detail
screens after success, and run the existing API authorization tests along with frontend tests.

**Mode: tool-run — browser/network evidence plus `php artisan test`, `npm run typecheck`, `npm run lint`, and `npm run test`.** The platform does not verify this architecture; successful and failed requests are the evidence.

**Hints:** Begin with status, not creation. List affected query keys on paper before coding. Let the
server response define success, then add optimism only after the confirmed path is correct.

## In the project

B08 uses real issue creation, edit, status, and comment actions to make synchronization visible.
One optimistic status interaction is sufficient. The application does not need labels, assignees,
or a global store merely to demonstrate a library. FS08.3 returns to client-only coordination and
compares Context, reducers, and Zustand without moving remote data out of the query cache.

## Closed-book checkpoint

1. What does invalidation do, and what does it not promise?
2. Why does a mutation need its own pending state?
3. Which cache entries can a status change affect?
4. What must an optimistic update save for a failure?
5. Why should settlement reconcile even after rollback?
6. Which mutation callbacks still run if the component unmounts mid-request, and which do not?
7. Why does `cancelQueries` belong in `onMutate`, and what breaks roughly one time in twenty without it?
8. Name two HTTP failures that must not be rendered as a validation message beside a field.
9. A write times out with no response. What is the only honest thing the client can do?

## Resources

### Read

- [TanStack Query: Mutations](https://tanstack.com/query/latest/docs/framework/react/guides/mutations)
- [TanStack Query: Query Invalidation](https://tanstack.com/query/latest/docs/framework/react/guides/query-invalidation)
- [TanStack Query: Optimistic Updates](https://tanstack.com/query/latest/docs/framework/react/guides/optimistic-updates)

### Go deeper

- [React: Responding to Events](https://react.dev/learn/responding-to-events)

## You are done when

- [ ] At least one issue write uses a mutation function that returns server truth.
- [ ] Pending, failure, and success are visible at the relevant interaction.
- [ ] All affected remote representations are invalidated or explicitly reconciled.
- [ ] One optimistic update has cancellation, rollback, and settlement—or was consciously rejected.
- [ ] Cache work sits on the `useMutation` options and screen work on the `mutate` call.
- [ ] A failed write is distinguished by kind, not by matching text in an error message.
- [ ] A comment draft survives a failed submission.
- [ ] One test proves a rollback restores the previous value, not only that the call happened.
- [ ] Backend authorization tests and frontend checks remain green.

## Maintainer source record

Source dossier: `docs/dalt-fullstack/sources/FSO_PART_06.md`.

Official sources: TanStack Query mutation, invalidation, and optimistic-update guides; React event guidance, linked above.

Versions: React 19.2.3; TypeScript 5.9.3; TanStack Query 5.101.4.

Consulted: 2026-08-15.

Curriculum authority: `CURRICULUM.md` §19, FS08.2; `PROJECT_BLUEPRINT.md` §§47 and 50.
