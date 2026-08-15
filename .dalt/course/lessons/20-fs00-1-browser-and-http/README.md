# FS00.1 — What happens when you open a web page?

Lesson ID: FS00.1  
Title: What happens when you open a web page?  
Part: 00 — Web fundamentals  
Order: 1  
Status: Published  
Estimated effort: 30–45 minutes  
Difficulty: Foundation  
Prerequisites: None  
Project milestone: B00 — Trace the system  
Primary source dossier: FSO_PART_00.md  
Last reviewed: 2026-08-15

## Why this matters

Type `/learn/fullstack` into the address bar and press Enter. A moment later, a page appears. It feels like one action, but several things have happened: the browser has contacted a server, the server has chosen a response, and the browser has decided how to use what came back.

That boundary will be the most useful place to look when the issue tracker later behaves strangely. If the screen is empty, the request may have gone to the wrong URL. If the request succeeded but the page is wrong, the response body may not contain what you expected. If the browser never made the request, the problem is somewhere before the server.

Before React, TypeScript, or DALT adds its own vocabulary, let’s watch this basic exchange happen and learn how to describe it precisely.

## Before you start

Required:

- A browser with Developer Tools. Chrome/Chromium and Firefox are both fine.
- This DALT application running locally.

Recommended:

- Keep a small note open. You will write down a guess, then compare it with evidence.

Going deeper in DALT Core — optional:

- [Request Lifecycle](/learn/lessons/01-request-lifecycle) follows a request inside DALT. It is not needed here; this lesson stays at the browser/server boundary.

## By the end

You should be able to:

- follow a page visit from the address bar to the rendered document;
- find a request’s method, URL, headers, optional body, and response status;
- find a response’s headers and body, including its `Content-Type`;
- tell the difference between an HTML document and JSON data;
- use the Network panel to replace “the app is broken” with a specific observation.

## Predict before reading

Before opening Developer Tools, make a guess about what the browser will do.

1. When you load `/learn/fullstack`, will the browser make one request or several?
2. Which response will contain the words **DALT Fullstack**: the first document response, a stylesheet, or a script?
3. If the server returns HTML, who turns that HTML into the page you see?
4. If the server returns JSON, will the browser automatically replace the page with it?

Write your answers down. They do not need to be correct. A useful prediction gives you something definite to compare with the browser later.

## Mental model

Here is the whole journey in its smallest useful form:

```text
you enter a URL
        ↓
the browser creates an HTTP request
        ↓
the server handles the request
        ↓
the server sends an HTTP response
        ↓
the browser interprets the response
```

The browser is the **client**. It starts the conversation and uses the response. The server is a separate process that receives the request, runs application code, and sends something back. HTTP is the protocol used for the messages between them.

That model is intentionally plain. Later, the browser will run JavaScript, the server will call DALT code, and PostgreSQL will sit behind the server. Each new layer adds detail without removing this boundary.

## 1. Start with the request

Open the DALT Fullstack journey in your browser:

**→ [/learn/fullstack](/learn/fullstack)**

Open Developer Tools, choose **Network**, and reload the page. The first important entry is the document request. Select it and look at the **Headers** panel.

For a normal page visit, the browser usually sends a `GET`. That means it is asking the server to return a representation of the URL. The URL identifies what it wants. Headers carry additional information, such as which response formats the browser can accept or which cookies it already has.

This is the shape of the message, not an exact transcript from your machine:

```text
GET /learn/fullstack HTTP/1.1
Host: localhost:8000
Accept: text/html, ...
Cookie: ...
```

The exact host, port, and headers will vary. What matters is that a request has identifiable parts:

- **method** — what kind of operation the client is asking for;
- **URL** — where the request is going;
- **request headers** — extra information about the request;
- **request body** — optional data sent with the request.

A simple `GET` usually has no body. Forms and API calls will often send one later.

## 2. Read the response

Now stay on the same Network entry and examine the response. The server answers with its own set of information:

- a **status code** describing the result;
- **response headers** describing the response;
- a **response body** containing the representation itself.

The response for the page should look roughly like this:

```text
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<!doctype html>
<html>
  ...
</html>
```

`200` tells you that the server successfully returned a response. `Content-Type` tells the browser what the body represents. Here it is HTML, so the browser parses it as a document and renders the result.

A `404` would mean that the server could not find what this URL requested. A `500` would mean that the server encountered an error while handling it. A status code is valuable evidence, but it is not the entire story: a successful `200` response can still contain the wrong data or markup.

Now compare HTML with JSON:

```text
Content-Type: application/json

{"message":"The server received the request."}
```

JSON is structured data. It is not automatically a new page. JavaScript can read that data and decide what to display, but the browser does not turn every JSON response into a document by itself.

## 3. One page can mean several requests

Look at the rest of the Network panel. Loading one visible page may produce requests for its HTML document, stylesheet, JavaScript, fonts, and images.

```text
browser → GET /learn/fullstack       → HTML document
browser → GET /assets/app.css        → CSS
browser → GET /assets/app.js         → JavaScript
browser → GET /assets/icon.svg       → image
```

The address bar gave you one visible action, but the HTML document can contain links to resources that the browser fetches separately. This is why the answer to “how many requests did the page make?” is often more than one.

Select one stylesheet or script request. Its method will probably also be `GET`, but its `Content-Type` and response body will be different from the document’s. The browser uses each response according to what it represents.

This is also why the Network panel is more useful than a vague report such as “the page loaded slowly.” It lets you ask which resource was requested, what status came back, and what the browser received.

## Try it

Let’s follow one real page visit instead of talking about an imaginary one.

**Mode: manual-proof.** You will prove the model with browser evidence and a trace in your own notes. The platform does not inspect or grade the note.

### First, make your guess

Open `/learn/fullstack` in a new tab. Before reloading it, write down:

- which request you expect to be the document request;
- which response you expect to contain the page title;
- whether you expect any other requests after the document arrives.

### Now inspect the exchange

Open **Network**, enable **Preserve log** if your browser provides it, and reload the page. Select the document request and record:

- the method and URL;
- one request header;
- the status;
- one response header;
- the kind of body returned.

Then select one additional request, such as a stylesheet or script. Notice what is different about its response.

### Tell the story in your own words

Write this sentence twice, once for the document and once for the additional resource:

```text
The browser requested __________ using __________.
The server answered with __________, and the browser used it as __________.
```

### Check yourself

Your note is complete when another person could use it to answer these questions without opening the lesson:

- What did the browser ask for?
- Where did it ask?
- What did the server return?
- How did the browser know what the response represented?
- What did the browser do next?

If your explanation says only “the app loaded the page,” replace “the app” with the actual request, response, and browser action.

### If you need a nudge

<details>
<summary>Finding the document request</summary>

Filter the Network panel by **Doc**, or choose the entry whose response preview contains the page’s HTML. The request name may be `/learn/fullstack` or a shortened version of it.
</details>

<details>
<summary>Finding the message pieces</summary>

The Headers panel normally groups the method, URL, and status under General. Request Headers and Response Headers are separate groups. Preview or Response shows the body.
</details>

<details>
<summary>Reference trace — read after your attempt</summary>

The browser sends a `GET` request for `/learn/fullstack`. DALT’s router selects the Fullstack handler, which returns an HTML response. The browser reads the response’s content type, parses the HTML, renders the document, and then requests any linked assets it needs.
</details>

## Common mistakes

### “The browser got the page” is enough

That sentence hides the useful evidence. A debugging trace should name the method, URL, status, content type, and what the browser did with the body.

### “A `200` means the screen must be correct”

`200` means the server successfully returned a response. The response can still contain unexpected HTML or data, and browser-side code can still display it incorrectly.

### “HTML and JSON are just two spellings of the same thing”

They are both response bodies, but they play different roles here. HTML can be parsed as a document. JSON is data that code must choose to use.

### “The address bar tells me everything that happened”

The address bar shows the current document URL, not every request that produced the document. The Network panel shows the rest of the conversation.

## When this goes wrong

If the Network panel is empty, open it before reloading. Developer Tools cannot show a request that happened before the panel started recording.

If there are too many entries, filter by **Doc** first. Once you understand the document request, inspect the CSS, JavaScript, font, and image requests one at a time.

If the application does not load, check the address and whether the local server is running. You can practise the same inventory on another local page, then return to the DALT page so your final trace describes the actual course route.

If you cannot find the response body, select the request and use **Response** or **Preview**. The body is the part that contains the representation the server sent.

## In the project

This is the first half of B00 — **Trace the system**. The issue tracker does not exist yet. What you are building now is a habit you will use throughout it:

```text
something looks wrong
        ↓
inspect the browser evidence
        ↓
identify the request and response
        ↓
choose the layer worth investigating
```

In Part 04, the browser will request issue data from a server. In Part 05, that server will ask PostgreSQL for it. The path will become longer, but the first question will stay the same.

## Closed-book checkpoint

Close this lesson before answering. Do not look back until you have written something for each question.

1. What four pieces can you identify in an HTTP request?
2. What three pieces can you identify in an HTTP response?
3. A response has `Content-Type: application/json`. What must happen before that data changes the visible page?
4. Why can loading one URL produce requests for a document, a stylesheet, and a script?
5. Draw the path from entering a URL to the browser rendering the response.

Then reopen the lesson and correct your answers in a different colour. The corrections are useful evidence about what you remembered and what you only recognized while reading.

## Resources

### Read

- [MDN: An overview of HTTP](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/Overview) — read the introduction, client/server roles, HTTP flow, and the request/response message sections.

### Reference

- [Chrome DevTools Network panel](https://developer.chrome.com/docs/devtools/network/) — use the sections related to requests, headers, responses, and initiators. Firefox Developer Tools are fine too.

## You are done when

- [ ] I inspected a real DALT document request in Developer Tools.
- [ ] I can identify the request and response parts without guessing.
- [ ] I can explain why HTML and JSON do not automatically produce the same browser behavior.
- [ ] I wrote and recalled a browser/server trace.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_00.md`
- Official sources: MDN HTTP Overview; Chrome DevTools Network documentation
- Versions: web documentation consulted 2026-08-13
- Consulted: 2026-08-15
- DALT files inspected: `.dalt/routes/routes.php`, `.dalt/Http/controllers/learn/index.php`, `.dalt/Core/MarkdownRenderer.php`
- Curriculum authority: `CURRICULUM.md` §10 FS00.1 — core questions, required outcomes and practice
- Laravel source: not applicable to this web-fundamentals lesson
