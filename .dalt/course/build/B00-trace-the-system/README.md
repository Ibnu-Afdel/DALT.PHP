> **What exists when you finish:** a written trace, in your own notes, of three
> browser/server interactions — reconstructed from evidence you gathered yourself,
> then recalled once with the evidence closed.

## What you are building

Not code. A **model**, written down, that you can reproduce from memory.

That sounds like the soft option. It is the opposite: for the next eleven milestones you will be debugging a system with a browser at one end and PostgreSQL at the other, and the single most expensive habit you can carry into that is saying "the app is broken" when you mean "I have no idea which of six boundaries failed."

Three interactions, three traces:

```text
1. loading a page             browser navigation → HTML document
2. an ordinary form submit    browser-owned POST → redirect → new document
3. a JavaScript submit        your code's fetch → JSON → no new document
```

For each one you will be able to name the method, the URL, the status, the content
type, what initiated the request, and whether the browser replaced the document.

## Why this milestone exists

Because interaction 2 and interaction 3 look identical to the user and are
completely different systems, and every framework you will ever use is a set of
opinions about that difference.

Part 03 builds a React interface that does #3 exclusively. Part 04 makes it talk to
a real server. If #2 is fuzzy — if you have never watched the browser do the whole
job by itself — then React's `preventDefault()` is a magic incantation rather than a
decision, and you will not be able to say what you gave up by calling it.

## Before you start

You need the two Part 00 lessons finished, a browser with DevTools, and somewhere to
write. A plain text file is ideal; this is a document you will reread.

The course provides a small fixture page with both kinds of form on it:

**→ [Open the observation fixture](/learn/fullstack/observe/forms)** in a second tab.

It is deliberately tiny and deliberately boring. It exists so that the traffic in
your Network panel is only the traffic you caused.

---

## Stage 1 — Predict, before you look at anything

Write three predictions now, before opening DevTools. Getting them wrong is the
point; a prediction you never committed to teaches you nothing when the evidence
arrives.

For each of the three interactions, write down:

- how many requests you expect;
- what started each one;
- whether the browser will end up on a new document;
- what kind of body comes back.

**Check it yourself:** you have three written predictions, and you wrote them before
opening DevTools. That ordering is the entire value — a prediction made after seeing
the answer is a transcription. If you already peeked, write what you now expect for
the *other* two and keep those honest.

## Stage 2 — Gather the evidence

Open DevTools, select **Network**, and enable **Preserve log** — without it the
ordinary form's redirect will wipe the entries you need.

**Load the page.** Find the document request. Then find at least one request the
document caused — a stylesheet or a script. Note that one address bar entry
produced more than one request.

**Submit the ordinary form.** Watch for three entries, not one: the `POST`, its
`303` response, and the `GET` the browser makes on its own for the new document.
That third one is the browser doing work you did not write.

**Submit with JavaScript.** One `fetch` request. Response `Content-Type` is
`application/json`. The document is not replaced — the URL does not change and the
page does not flash. Something on screen updated anyway.

For each request record: method, URL, status, `Content-Type`, initiator, and whether
a new document was requested.

**Check it yourself:** you can point at the request the browser made **by itself**
after the redirect — the one you did not write and no JavaScript triggered. If you
cannot find it, Preserve log is off and the entries were wiped by the navigation.

> The initiator column is the one people skip and the one that answers "why did this
> request happen?". Learn where your browser shows it.

## Stage 3 — Write the three traces

Now write the actual artifact. Use whatever notation you like — arrows are fine,
prose is fine — but each trace must name every hop:

```text
user action → what initiated the request → method and path
            → server response and status → representation
            → what the browser did next
```

**Check it yourself:** read each trace and search it for the word "app". A trace is
finished when it contains no step called "the app" — that phrase is where
understanding goes to hide. If you cannot name a hop, you have found the part you do
not understand yet, which is worth more than a complete-looking diagram.

## Stage 4 — Answer the state question

One more note, and it is the one Part 08 will cash in.

Submit the JavaScript form so the page visibly changes. Then reload the page.

What happened to the change? Write down: what was only ever in the browser, what the
server actually knows about, and what would have had to be different for the change
to survive the reload.

**Check it yourself:** your note distinguishes three things — what the browser
displayed, what the server actually received, and what would have had to change for
the update to survive. If all three collapse into one sentence, look at the fixture's
response again: it accepted your request and stored nothing.

You have just met the distinction between client state and persisted state, with
evidence, before anyone offered you a library for it.

## Stage 5 — Close everything and recall

Close DevTools. Close this page. From memory, write the trace for the **ordinary
form submit** — the one with the redirect.

Then reopen the evidence and correct what you got wrong, in a different colour.

**Check it yourself:** compare the recalled trace with the one you wrote in Stage 3.
Every difference is a real finding. If they match exactly, you either understood it or
you memorised the shape of your own note — test which by tracing the *JavaScript*
submit from memory too, which you did not rehearse.

The correction is not the failure part of the exercise. It is the exercise. You are
finding out which parts of the model you actually hold versus which parts you were
reading off the screen a minute ago.

---

## Decisions you have to make

Nothing in this milestone has one right answer:

- **How much detail is a useful trace?** Too little and it says "the app"; too much
  and you will never reread it. Find the level you would want if you were debugging
  this at 2am.
- **Which of the three interactions is the odd one out?** There is a defensible case
  for each. Being able to argue it is the point.

## Acceptance criteria

Read these against what you actually produced. Nothing here is checked automatically.

- [ ] I wrote predictions **before** opening DevTools.
- [ ] I have three written traces: page load, ordinary form, JavaScript form.
- [ ] Every trace names a method, a path, a status, a representation, and what the
      browser did next — with no step called "the app".
- [ ] I can point at the request the browser made *by itself* after the redirect.
- [ ] I recorded what the JavaScript submit changed, and what survived a reload.
- [ ] I recalled one trace with DevTools closed and corrected it against evidence.
- [ ] I can explain, out loud, why the ordinary form and the JavaScript form feel the
      same to a user and are not the same system.

## Prove it to yourself

Answer in your notes, without reopening anything:

1. Why can typing one URL produce several HTTP requests?
2. What is the difference between a navigation and a request initiated by JavaScript?
3. What does a browser do, unaided, when an ordinary HTML form submits?
4. Why can JavaScript change the page without a new HTML document?
5. Something disappears after a refresh. What does that tell you about where it lived?

## What this unlocks

Part 01 makes the JavaScript in interaction 3 yours to write. Part 03 builds an
entire interface out of it. Part 04 replaces the fixture with a real DALT server, and
the first question when it misbehaves will be the one you just practised: **what did
the browser ask for, and what came back?**
