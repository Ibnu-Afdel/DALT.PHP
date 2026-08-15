# FS00.1 — Browser, server, request, response

Lesson ID: FS00.1  
Title: Browser, server, request, response  
Part: 00 — Web fundamentals  
Order: 1  
Status: Published  
Estimated effort: 30–45 minutes  
Difficulty: Foundation  
Prerequisites: None  
Project milestone: B00 — Trace the system  
Primary source dossier: FSO_PART_00.md  
Last reviewed: 2026-08-14

## Why this matters

Later, your issue tracker will have a React interface, a DALT server, and PostgreSQL behind it. When something looks wrong, the useful first question is not “which framework is broken?” It is: **what did the browser ask for, and what came back?**

That question works before React, after React, and when no framework is involved. It gives you evidence instead of a guess.

You are starting with a deliberately small model: a browser asks a server for something; the server responds; the browser decides what to do with that response. The details grow later, but this boundary stays.

## Before you start

Required:

- A browser with Developer Tools (Chrome/Chromium or Firefox are both fine).
- This DALT application running locally.

Recommended:

- Keep a small note open for predictions and observations.

Going deeper (optional, DALT Core):

- [Request Lifecycle](/learn/lessons/01-request-lifecycle) examines DALT's server-side request path in more depth. It is not required for this lesson.

## By the end

You should be able to:

- trace a page visit from browser to server and back;
- identify a request's method, URL, request headers, optional request body, status, response headers, and response body;
- distinguish an HTML document response from a JSON response;
- use the Network panel as evidence when a browser/server interaction surprises you.

## Predict before reading

Open a new tab and write down answers before checking.

1. When you load `/learn`, how many requests do you expect to see: one, or several?
2. Which response do you expect contains the words “DALT.PHP learning”?
3. If a request returns HTML, which process turns it into visible text and controls?
4. If a request returns JSON, does the browser automatically turn it into a page?

There is no score here. The comparison between your prediction and the evidence is the exercise.

## Mental model

```text
you enter a URL
        ↓
browser creates an HTTP request
        ↓
server chooses how to handle it
        ↓
server sends an HTTP response
        ↓
browser interprets the response
```

The browser is a **client**: it initiates the request and renders or otherwise uses the response. The server receives the request, runs application code, and returns a response. They are separate processes with a message boundary between them.

HTTP is the message protocol at that boundary. A request usually has a method and URL, plus headers and sometimes a body. A response has a status, headers, and often a body.

## 1. A request is a specific question

For a normal page visit, the browser commonly sends a `GET` request. `GET` means “retrieve a representation of this URL.” The URL tells the server which resource is being requested.

Headers add context. For example, a browser may say which response formats it accepts, what language it prefers, or which existing session cookie it is sending. You do not need to memorize headers yet; learn to notice that they are part of the message.

A request body is optional. A browser usually has no request body for a simple `GET`. Later, form submissions and JSON API requests will often carry one.

```text
GET /learn HTTP/1.1
Host: localhost:8000
Accept: text/html, ...
Cookie: ...                 ← only if the browser has one
```

The exact headers and host/port on your machine will differ. The shape is what matters.

## 2. A response answers with status, headers, and a body

The server answers with a status code. A `200` commonly means the server successfully produced the requested representation. A `404` means it could not find a matching resource. Status is concise evidence about the server's result; it is not the whole response.

Response headers describe the response. `Content-Type: text/html` tells the browser it received HTML. `Content-Type: application/json` tells it the body is JSON data. The response body contains the actual HTML, JSON, image bytes, CSS, JavaScript, or another representation.

```text
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

<!doctype html>
<html>...
```

HTML is a document the browser can parse and render. JSON is data. Seeing JSON in a Network response does not by itself change the screen; JavaScript has to decide what to do with it. That distinction is central to the React work ahead.

## Inspect real evidence

Use your running DALT application. This is observation, not a hidden test.

1. Open Developer Tools, select **Network**, and enable “Preserve log” if your browser offers it.
2. Reload `/learn`. Select the document request (usually named `learn` or `/learn`).
3. In its panels, find the method, URL, status, request headers, response headers, and response body/preview.
4. Find at least one additional request for a stylesheet, JavaScript file, or font. Notice that one visible page can require several responses.
5. Follow a visible link such as **DALT Fullstack**. In the Network panel, identify the new document request and its status.

Write one sentence for each: “The browser requested ___; the server responded with ___; the browser then ___.” Be concrete about the URL and response type.

## Try it

**Mode: manual-proof.** You are proving a real observation to yourself, not submitting source text for an automated checker.

### Goal

Reconstruct a browser/server exchange without relying on the lesson.

### Starting state

The DALT application is open and the Network panel is recording.

### Requirements

- Reload `/learn/fullstack` and select its document request.
- Record its method, full URL/path, status, one request header, one response header, and what kind of body it returned.
- In a note, draw this trace from memory:

```text
browser → request → server → response → browser
```

- Label where the HTML came from and what the browser did with it.

### Verification

Compare your note with the Network entry. Correct it until each label corresponds to evidence you can point at. If you can explain the trace aloud without calling every step “the app,” you have the model B00 needs.

### Hints

<details>
<summary>Hint 1: finding the document request</summary>

Reload with Network open. Filter by **Doc** or look for the request whose response preview contains the page title.
</details>

<details>
<summary>Hint 2: where the message pieces live</summary>

The Headers panel normally groups General (method, URL, status), Request Headers, and Response Headers. Preview or Response shows the body.
</details>

<details>
<summary>Revealable reference trace</summary>

The browser sends a `GET` for `/learn/fullstack`. DALT's router selects the Fullstack controller, which reads course data and returns an HTML response. The browser receives a successful HTML response, parses it, and renders the Fullstack journey. Asset requests may follow separately.
</details>

## Common mistakes

### “The browser got the page” is enough detail

It is a start, but it hides the boundary. Name the method, URL, status, content type, and body. Those are the clues you will use when the result is wrong.

### HTML and JSON are interchangeable responses

Both are response bodies, but the browser treats them differently. HTML can become a document directly. JSON is data that code must deliberately use.

### A successful status means the screen must be correct

No. A `200` says the server returned a successful response. The body may still contain unexpected data, and browser-side code may still render it incorrectly.

## When this goes wrong

1. If Network is empty, open it before reloading the page.
2. If you see too many entries, filter by **Doc** first; then inspect CSS, JS, and fonts one at a time.
3. If the app is unavailable, use any local page you can load and practice the same request/response inventory. Return to DALT when it is running.

## In the project

This is B00 — **Trace the system**. There is no issue-tracker code yet. The output is a reliable habit: predict an interaction, inspect the browser's evidence, then explain the path across the boundary.

## Closed-book checkpoint

Close this lesson before answering.

1. Name the six pieces you can identify in a Network entry for a request.
2. A browser receives `Content-Type: application/json`. What must happen before JSON changes the visible page?
3. What is one useful difference between a `GET` request and a request with a body?
4. Draw the five-step path from entering a URL to seeing a response.

Then reopen the lesson and correct your answers in a different color.

## Resources

### Read

- [MDN: An overview of HTTP](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/Overview) — read the introduction, client/server roles, HTTP flow, and request/response message sections.

### Reference

- [Chrome DevTools Network panel](https://developer.chrome.com/docs/devtools/network/) — use the sections relevant to the fields you inspected; equivalent Firefox tools are fine.

## You are done when

- [ ] I inspected a real DALT document request in Developer Tools.
- [ ] I can identify method, URL, headers, body, and status without guessing.
- [ ] I can explain why HTML and JSON do not automatically produce the same browser behavior.
- [ ] I reconstructed the trace and completed the closed-book checkpoint.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_00.md`
- Official sources: MDN HTTP Overview; Chrome DevTools Network documentation
- Versions: web documentation consulted 2026-08-13
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/routes/routes.php`, `.dalt/Http/controllers/learn/index.php`, `.dalt/Core/MarkdownRenderer.php`
- Curriculum authority: `CURRICULUM.md` §10 FS00.1 — core questions, required outcomes and practice
- Laravel source: not applicable to this web-fundamentals lesson
