# FS01.2 — Modules, async JavaScript and failure

Lesson ID: FS01.2  
Title: Modules, async JavaScript and failure  
Part: 01 — Modern JavaScript  
Order: 2  
Status: Published  
Estimated effort: 75–105 minutes  
Difficulty: Foundation  
Prerequisites: FS01.1  
Project milestone: B01 — JavaScript readiness  
Primary source dossier: FSO_PART_02.md  
Last reviewed: 2026-08-14

## Why this matters

Soon, an issue screen will ask a server for data. Before React and TypeScript add their own vocabulary, make the browser-to-server path visible in ordinary JavaScript:

```text
module → function → Promise → Response → body → application value
```

Each arrow is a boundary. A failure at one boundary is not necessarily a failure at another.

That last sentence is the lesson. Most broken data-loading code comes from collapsing those boundaries into one idea called "the request failed" — so a `404` is reported as a network problem, a `500` renders as an empty list, and a malformed body throws somewhere with no useful message. Keeping the boundaries distinct here is what makes Part 04's loading and error states honest instead of a single spinner and a shrug.

## Before you start

Required:

- FS01.1 — Data, functions and transformations.
- Node available (`node --version`) and a browser with DevTools.

Recommended first:

- FS00.2's distinction between a document response and a JSON response. `fetch` is where that becomes your problem rather than the browser's.

Going deeper in DALT Core — optional:

- None.

## By the end

You should be able to:

- split a small program with named ESM exports and imports;
- explain the difference between a Promise and its fulfilled value;
- use `async`, `await`, and `try`/`catch` without pretending asynchronous work became synchronous;
- trace `fetch` from `Promise<Response>` through status inspection and JSON parsing;
- distinguish a network rejection, an HTTP error response, invalid JSON, and an application-domain failure.

## Predict before reading

Write answers down first.

1. `const data = fetch(url)`. What is in `data`?
2. A request returns `404`. Does the returned Promise reject, or fulfil?
3. An `async` function returns `42`. What does its caller receive?
4. `await` appears in the middle of a function. Does the rest of the page stop?
5. You call `response.json()` twice on the same response. What happens the second time?

Question 2 is the one that produces silent bugs in real applications.

## Mental model

A single `fetch` is four separate things that can go wrong, at four separate boundaries:

```text
1. TRANSPORT     fetch(url) ──▶ Promise<Response>
                 rejects only if the request never completed
                 (offline, DNS, CORS, aborted)

2. HTTP STATUS   response.ok / response.status
                 404 and 500 are SUCCESSFUL transports
                 carrying an unsuccessful answer

3. BODY          await response.json()
                 rejects if the bytes are not valid JSON
                 (an HTML error page is the classic case)

4. DOMAIN        the parsed value
                 valid JSON that your application cannot use
```

The rule to carry: **`fetch` rejects only at boundary 1.** Answer to question 2 — a `404` *fulfils*. The Promise's job is "did I get an answer?", not "was the answer good news?". Code that only wraps `fetch` in `try`/`catch` handles one boundary out of four and reports the other three as success.

The second idea, underneath all of it: **a Promise is not its value.** It is a handle on work that will finish later. `await` unwraps it — and only inside the async function that wrote it. Answer to question 4: the rest of the page keeps running; `await` suspends this function's continuation, not the browser.

## Start with a tiny module boundary

Create an empty scratch directory **outside this repository**. Make these two files, then run `node issue-report.mjs` from that directory.

```js
// issue-titles.mjs
export const openTitles = (issues) =>
  issues
    .filter((issue) => issue.status === 'open')
    .map((issue) => issue.title);
```

```js
// issue-report.mjs
import { openTitles } from './issue-titles.mjs';

const issues = [
  { title: 'Broken search', status: 'open' },
  { title: 'Refresh docs', status: 'closed' },
];

console.log(openTitles(issues));
```

You just ran the same ordinary array transformation from FS01.1. The new part is the boundary: `issue-titles.mjs` exports a value; `issue-report.mjs` imports that binding using a path to that module.

**Try it:** rename the exported function to `titlesForOpenIssues` but leave the import unchanged. Run it and read the error. Then repair either side so the exported name and imported name agree. Next change the path to `./issue-title.mjs`, observe that this is a *module-location* error, then restore it.

Each module has its own scope. `openTitles` is not automatically visible in `issue-report.mjs`; exporting and importing makes that relationship explicit.

### Recognize a default export

Named exports are the default in this course because their names stay visible at the import site. You will still encounter this form:

```js
// format-title.mjs
export default function formatTitle(title) {
  return title.toUpperCase();
}

// issue-report.mjs
import formatTitle from './format-title.mjs';
```

A default export has no braces at import. It is one designated export from that module; named exports use braces and must match their exported names. Prefer a named export unless one primary export genuinely makes the file clearer.

## An operation that finishes later

Add this to `issue-report.mjs`. Before running it, predict the order of the three logs.

```js
console.log('1. Start');

setTimeout(() => console.log('3. Later'), 0);

console.log('2. Continue now');
```

Run it. The usual order is `Start`, `Continue now`, then `Later`. A synchronous statement completes before the next statement continues. The timer callback represents work that may run later, so JavaScript continues with the synchronous code already available.

This is enough event-loop depth for now: JavaScript execution remains ordered, while browser and timer work can complete outside the immediate call flow. A callback, or the continuation after an `await`, does **not** mean the whole JavaScript runtime stopped.

## Meet the Promise before `await`

Replace the timer example with a controlled future result:

```js
const loadIssueTitle = (shouldFail = false) =>
  new Promise((resolve, reject) => {
    setTimeout(() => {
      if (shouldFail) {
        reject(new Error('Issue preview is unavailable'));
        return;
      }

      resolve('Broken search');
    }, 300);
  });

const pendingTitle = loadIssueTitle();
console.log('Returned now:', pendingTitle);

pendingTitle
  .then((title) => console.log('Fulfilled value:', title))
  .catch((error) => console.error('Rejected reason:', error.message));
```

**Try it:** before you run this, decide whether `pendingTitle` already contains the string. Then run it and inspect the first log. Call `loadIssueTitle(true)` and observe the rejection handler.

A Promise is a representation of a future outcome. It starts **pending**, then settles exactly once as either **fulfilled** with a value or **rejected** with a reason/error.

```text
loadIssueTitle()
        ↓ returns now
Promise (pending)
        ↓ later
fulfilled with a title  OR  rejected with an error
```

Calling a Promise-returning function is not receiving its eventual value. The `.then(...)` and `.catch(...)` calls make the two outcomes visible; they are not a second kind of asynchronous work.

## Rewrite the same consumer with `async` / `await`

Keep `loadIssueTitle` exactly as it is. Replace only the consumer:

```js
const showIssueTitle = async (shouldFail) => {
  console.log('Before await');
  const title = await loadIssueTitle(shouldFail);
  console.log('Fulfilled value:', title);
};

showIssueTitle(false);
console.log('This synchronous log can happen before the title');
```

**Predict, then run:** which logs occur before the title? Change the call to `showIssueTitle(true)`. Where does the failure appear?

`async` means this function returns a Promise. `await` suspends the continuation of **this async function** until the awaited Promise settles. It does not turn the underlying work into a normal immediate value, and it does not pause all JavaScript.

On fulfillment, `await` gives the fulfilled value. On rejection, it behaves like an exception thrown at the `await` expression. Put the failure handling where the pressure exists:

```js
const showIssueTitle = async (shouldFail) => {
  try {
    const title = await loadIssueTitle(shouldFail);
    console.log('Fulfilled value:', title);
  } catch (error) {
    console.error('Could not load preview:', error.message);
  }
};
```

**Try again:** run the success and failure calls. What changed? The syntax and readability changed; the Promise and the asynchronous behavior did not disappear. The `catch` handles thrown errors and rejected Promises that are awaited inside its `try` block.

## Fetch is a sequence, not “get data somehow”

Part 00 established that browser JavaScript can initiate HTTP and a server can return a response. Now inspect the code path. Open this lesson in your browser, open DevTools → Network, then paste this into the Console:

```js
const inspectIssuePreview = async (path) => {
  try {
    console.log('Before fetch:', path);
    const response = await fetch(path);
    console.log('Response:', response);
    console.log('Status / ok:', response.status, response.ok);
    const body = await response.json();
    console.log('Parsed body:', body);
  } catch (error) {
    console.error('Pipeline failed:', error.name, error.message);
  }
};

inspectIssuePreview('/learn/fullstack/observe/async/issue-preview');
```

**Observe:** find the request in Network. Inspect its URL, method, status, response headers, and response body. Then compare that browser evidence with the `Response` object and parsed body in the Console.

The stages are deliberate:

```text
fetch(url)
  ↓
Promise<Response>
  ↓ HTTP exchange completes
Response object (status, ok, headers, body)
  ↓ response.json()
Promise for parsed JavaScript values
  ↓
application value
```

`Response` is not parsed JSON. `response.json()` reads the response body and parses JSON into JavaScript values. A response body can be read once, so do not casually call `response.json()` twice.

## Make an HTTP failure visible

Change only the path:

```js
inspectIssuePreview('/learn/fullstack/observe/async/missing-issue');
```

Before running it, predict: does the `catch` run automatically because the status is 404? Run it. `fetch` fulfilled with a `Response`; the server successfully answered at the HTTP transport level. Your current code then parses its JSON and follows the apparent success path.

Repair the working example by adding an explicit HTTP boundary **before** parsing:

```js
const response = await fetch(path);

if (!response.ok) {
  throw new Error(`Issue preview request failed with HTTP ${response.status}`);
}

const body = await response.json();
```

Run the success path and then the 404 again. The `catch` now runs because *your code* threw after inspecting the HTTP response. This is not an API-client architecture; it is one small, explicit decision about what counts as success here.

## Make a body failure visible

Keep the repaired `response.ok` check and change the path again:

```js
inspectIssuePreview('/learn/fullstack/observe/async/invalid-json');
```

The fixture deliberately returns HTTP 200 but plain text. `response.ok` is true, then `response.json()` rejects because its body is not valid JSON. The same `catch` can handle it, but the stage is different: the HTTP exchange succeeded; JSON parsing failed.

There is one more boundary. Valid JSON is only data representation, not proof that your application can use it. A JSON body such as `{ "accepted": false, "reason": "project is archived" }` may parse correctly while describing an application-domain failure. Later, FS02.5 asks how runtime code can establish that parsed values have the shape an application expects. Do not solve that with TypeScript yet.

### Four failures, four questions

| Boundary | Example | Typical `fetch` behavior | First question |
| --- | --- | --- | --- |
| Network / transport | Browser cannot reach a server | Promise rejects | Did `fetch` reject before a Response existed? |
| HTTP response | Server returns 404 or 500 | Promise fulfills with `Response` | What are `status` and `ok`? |
| Body / JSON | 200 body is not JSON | `response.json()` rejects | Did parsing fail after the Response arrived? |
| Application domain | JSON says an operation was rejected | Depends on your application rule | What did the parsed value actually say? |

A genuine network failure depends on the learner’s network, browser policy, and server state, so this lesson does not fake one. If you later observe one, inspect the rejected error and the absence of a usable `Response`; it is not the same evidence as a 404 response.

## Try it

**Prediction:** the table above names four failure boundaries. Before running anything,
predict which single boundary each of these two calls will actually exercise, using your
repaired `inspectIssuePreview` (the one with the `response.ok` check and JSON parsing both
in place):

```js
inspectIssuePreview('/learn/fullstack/observe/async/missing-issue');
inspectIssuePreview('/learn/fullstack/observe/async/invalid-json');
```

**Run / inspect:** run each call separately and read exactly where the thrown error's
message comes from — your explicit `response.ok` check, or the JSON parser.

**What happened:** `missing-issue` throws from your explicit HTTP-status check; `fetch`
itself fulfilled normally, because the server answered — it just answered with 404.
`invalid-json` throws from `response.json()` itself; `response.ok` was true, so your check
never fires, and the failure only appears once parsing runs.

**Why:** both calls reach `catch`, so a `console.error` alone cannot tell you which boundary
failed — that is the entire reason the table separates four questions instead of one. Notice
what you *cannot* reproduce this way: a genuine network/transport failure, where `fetch`
itself rejects before any `Response` exists. That boundary depends on conditions this
fixture cannot simulate honestly, which is exactly what the paragraph after the table
says — do not manufacture a fake one and mistake it for the real thing.

## Focused exercise — Load an issue preview safely

**Mode: self-reported practice with browser DevTools evidence. This exercise is not automatically verified.** Work in a scratch `issue-preview.mjs` module and an importing `run-preview.mjs` module, or adapt the code in the browser Console while keeping the two responsibilities separate.

Start with this working loader:

```js
// issue-preview.mjs
export const loadIssuePreview = async (path) => {
  const response = await fetch(path);
  return response.json();
};

// run-preview.mjs
import { loadIssuePreview } from './issue-preview.mjs';

const run = async () => {
  const preview = await loadIssuePreview('/learn/fullstack/observe/async/issue-preview');
  console.log(preview.issue.title);
};

run();
```

If you run the files with Node, supply the origin explicitly, for example `const baseUrl = 'http://127.0.0.1:8000';`, and call `fetch(baseUrl + path)` while your local DALT server is running. In a browser Console on this site, the relative paths work directly.

Evolve the working version in four short changes. Run and inspect after every one:

1. Confirm the success request appears in Network and that the imported function returns parsed data.
2. Change the path to `missing-issue`; make `loadIssuePreview` throw a meaningful HTTP error instead of returning its error JSON as a success value.
3. Put the calling code in `try`/`catch`; report the failure without swallowing its message or stage.
4. Change the path to `invalid-json`; keep the HTTP behavior and show that body parsing is a separate failure.

Your result should export/import the loader, inspect HTTP success before parsing, return useful data on success, and let the caller handle a meaningful failure. Do not build a generic client or add React.

### Hints

<details>
<summary>Hint 1: which evidence should I inspect?</summary>

Compare Network evidence with Console evidence: did a request appear, did a Response arrive, and what status did it have?
</details>

<details>
<summary>Hint 2: which stage owns this behavior?</summary>

Separate the stages: initiating `fetch`, receiving a Response, deciding whether that HTTP response is acceptable, and parsing its body.
</details>

<details>
<summary>Hint 3: what small experiment distinguishes two hypotheses?</summary>

Log `response.status` and `response.ok` immediately after `await fetch(...)`, then log just before and after `await response.json()`. Compare the 404 and invalid-JSON fixtures.
</details>

<details>
<summary>Hint 4: a small implementation clue</summary>

After `await fetch(path)`, turn a non-OK response into an `Error` before calling `response.json()`. Let the caller's `try`/`catch` report that error.
</details>

<details>
<summary>Reference explanation — reveal after a meaningful attempt</summary>

One valid loader awaits `fetch`, checks `response.ok`, throws an error that includes the status when it is false, and otherwise returns `await response.json()`. The caller awaits the imported loader inside `try`/`catch` and logs an error message. With the 404 fixture, the loader creates the error after receiving a Response. With the invalid-JSON fixture, `response.json()` rejects; the caller's catch sees a parsing failure. The module boundary does not change JavaScript values or failure behavior—it makes the loader’s responsibility explicit.
</details>

## When this goes wrong

When behavior surprises you, follow this order instead of guessing:

1. Did this function actually run?
2. What was logged before the `await`?
3. Did a request appear in Network?
4. What URL and method were used?
5. Did `fetch` reject, or did it return a `Response`?
6. What status was returned? Is `response.ok` true?
7. Did body parsing fail?
8. What value did parsing actually produce?
9. Which `catch` handled the failure?

Keep Network and Console together: Network shows the browser/server exchange; runtime logs show what your JavaScript did with its result.

## Common mistakes

- Treating a Promise as its eventual value.
- Assuming an `async` function returns an ordinary value.
- Thinking `await` pauses all JavaScript rather than this async function’s continuation.
- Forgetting to `await` a Promise before using its value.
- Assuming `fetch` rejects automatically for HTTP 404 or 500.
- Catching only transport rejection while never inspecting `response.ok`.
- Parsing the same response body twice without accounting for body consumption.
- Treating valid JSON as proof of valid application data.
- Swallowing an error so later evidence no longer tells you what failed.
- Putting unrelated work in one giant `try`/`catch` and losing the failure stage.
- Debugging an import path/name error as though it were an async failure.

Answer to question 5: the second `response.json()` rejects, because the body is a stream and reading it consumes it. If you need the text and the parsed value, read once and keep both.

## In the project

This is the second half of **B01 — JavaScript readiness**, and it is the closest Part 01 gets to the real system.

The four boundaries reappear directly. FS02.5 takes boundary 4 seriously — valid JSON that is not a valid `Issue` — and turns it into a parser. Part 04 turns all four into visible application states, because a user needs to be told something different for "you are offline", "that issue does not exist", and "the server sent something we could not read". Part 06 adds a fifth: a `401` that means the session expired.

Notice how much of that is decided here, in plain JavaScript, before any library offers to hide it.

## Closed-book checkpoint

Close the lesson and answer before opening the disclosure.

1. What does an `async` function return?
2. What is the difference between a Promise and its fulfilled value?
3. What does `await` do to the current async function?
4. Why can a `fetch` request receive HTTP 500 without entering `catch`?
5. What does `response.json()` do, and why can it fail after a 200 response?
6. How is invalid JSON different from valid JSON that contains invalid application data?
7. No request appears in DevTools. Where would you look first, and what evidence would you seek?
8. A request appears with 404, but code enters its success path. What assumption is probably wrong, and what small repair changes that behavior?

<details>
<summary>Check your recall</summary>

1. It returns a Promise; a returned value becomes that Promise’s fulfilled value.
2. A Promise represents an outcome that may settle later; the fulfilled value is the outcome available after fulfillment.
3. It suspends the continuation of that async function until the awaited Promise settles. It does not freeze all JavaScript.
4. HTTP 500 is still an HTTP response, so native `fetch` normally fulfills with a `Response`. Code must inspect `response.ok` or status.
5. It reads the response body and parses JSON into JavaScript values. A 200 body can still be malformed JSON.
6. Invalid JSON cannot be parsed; valid JSON can parse while still failing the application’s expected meaning or shape.
7. Confirm the function ran and inspect logs before `fetch`, then verify the URL and request initiation in Network.
8. The assumption is that HTTP error statuses reject `fetch`. Check `response.ok` and throw or otherwise handle the non-OK response before parsing it as success.
</details>

## Resources

- [MDN: JavaScript modules](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Modules) — module scope, exports, and imports.
- [MDN: Using promises](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Using_promises) — settlement and consumers.
- [MDN: async function](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Statements/async_function) — what async functions return.
- [MDN: Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch) — responses, status handling, and body parsing.

## You are done when

- [ ] I ran and repaired a named ESM import/export example.
- [ ] I observed a Promise before using `async`/`await`.
- [ ] I rewrote the same Promise consumer with `async`/`await` and handled rejection with `try`/`catch`.
- [ ] I used Network and Console together to inspect successful, HTTP-error, and invalid-JSON fetch fixtures.
- [ ] I evolved the issue preview loader rather than starting from a blank architecture.
- [ ] I answered the checkpoint before revealing its answers.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_01.md`; `docs/dalt-fullstack/sources/FSO_PART_02.md`
- Official sources: MDN JavaScript modules, Promises, async functions, and Fetch links above
- Versions: MDN documentation consulted 2026-08-13; Node 25
- Consulted: 2026-08-14
- Curriculum authority: `CURRICULUM.md` §11 FS01.2 — topics and required outcome
- Laravel source: not applicable; this is language groundwork before framework work
