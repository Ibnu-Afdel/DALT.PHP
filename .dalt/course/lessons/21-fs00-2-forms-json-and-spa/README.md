# FS00.2 — Forms, JavaScript, JSON and the SPA model

Lesson ID: FS00.2  
Title: Forms, JavaScript, JSON and the SPA model  
Part: 00 — Web fundamentals  
Order: 2  
Status: Published  
Estimated effort: 30–45 minutes  
Difficulty: Foundation  
Prerequisites: FS00.1 — Browser, server, request, response  
Project milestone: B00 — Trace the system  
Primary source dossier: FSO_PART_00.md  
Last reviewed: 2026-08-14

## Why this matters

A form can send a request before you write any JavaScript. That browser-default behavior is useful, but it is not the only way an application can respond to a submit.

Later, a React interface will usually keep its application shell visible while JavaScript decides how an interaction changes the UI and when it exchanges data with a server. Before React gives that pattern a name, you should see the underlying contrast directly.

## Before you start

Required:

- FS00.1 — Browser, server, request, response.
- A browser with Developer Tools and this DALT application running locally.

Recommended first:

- Keep the Network panel open with **Preserve log** enabled.

Going deeper in DALT Core — optional:

- [Routing](/learn/lessons/02-routing) shows how DALT decides which handler answers a
  form submission. It is not required for this lesson, and nothing here depends on it.

## By the end

You should be able to:

- predict the request and navigation caused by a plain HTML form;
- explain what `preventDefault()` changes at a conceptual level;
- distinguish an HTML document response from a JSON response;
- explain how a visible UI can change without loading a new document;
- separate what is currently visible from what a server or database stores.

## Predict before reading

Open the [Part 00 form observation fixture](/learn/fullstack/observe/forms) in a new tab. Before submitting either form, write down your prediction.

| Interaction | What request will happen? | Will the browser navigate? | What response do you expect? |
| --- | --- | --- | --- |
| Ordinary HTML form |  |  |  |
| JavaScript-controlled form |  |  |  |

Do not inspect the implementation yet. A wrong prediction is useful evidence about the model you are building.

## Mental model

```text
plain form submit
    ↓
browser performs its default submission behavior
    ↓
server response tells the browser what happens next
```

```text
JavaScript-controlled submit
    ↓
JavaScript prevents the default behavior
    ↓
application chooses whether and how to make an HTTP request
    ↓
application uses the response to change visible UI
```

The second model is not “no server.” It is a different division of responsibility after the submit.

## 1. A browser already knows how to submit a form

An HTML `form` groups input values for submission. Its `action` says where to send them, and its `method` says how to send them. A control with a `name` contributes a named value.

In the observation fixture, the ordinary form has an action and uses `POST`. When you submit it, the browser creates the request and navigates according to the response. You did not need to write JavaScript for that to happen.

Inspect the form's Network entry. The useful questions are:

- Was it a `POST`? Which URL received it?
- Which submitted value appears in the request payload/body?
- Did the response redirect the browser?
- After the redirect, which `GET` requested the next document?

The fixture deliberately returns a redirect after the `POST`, so you may observe this common shallow sequence:

```text
POST form data
    ↓
redirect response
    ↓
GET a document
```

You do not need to memorize redirect status codes yet. Notice the consequences: the browser navigates, and a new HTML document is requested.

## 2. JavaScript can take control of the interaction

An event such as form submission has default browser behavior. Calling `preventDefault()` tells the browser not to carry out that default submission. It does not send data by itself; it gives JavaScript the chance to decide what happens next.

The second fixture form does exactly that. Its small script sends a request, receives JSON, and changes text already present in the document. The address bar and document stay in place.

You do not need to learn request syntax here. In the Network panel, observe the result instead:

```text
submit
    ↓
POST with JSON data
    ↓
JSON response
    ↓
JavaScript changes visible UI
```

## 3. JSON is data, not a page

JSON is a text representation for structured data. For example:

```json
{
  "accepted": true,
  "message": "The server received the preview request."
}
```

Unlike an HTML document response, JSON does not tell the browser how to render a new page. JavaScript can read it and choose what to show. In the fixture, the response message becomes a visible status message.

## 4. The DOM: the browser's working representation

The browser parses an HTML document into a representation it can work with. This is commonly called the DOM.

```text
HTML document
    ↓
browser representation
    ↓
JavaScript can change visible UI
```

The status text changing in the fixture is a small DOM change. React will later offer a more structured way to describe such updates, but the basic fact arrives first: browser JavaScript can change what you see without asking for a whole new document.

## 5. The SPA model, carefully

An SPA, or single-page application, keeps an application shell loaded and lets JavaScript handle many interactions.

```text
application shell remains loaded
    ↓
JavaScript handles interaction
    ↓
HTTP can exchange data
    ↓
visible UI changes
    ↓
full-document navigation is not required for every interaction
```

“Single page” does not mean one HTTP request forever, or no server. It means many interactions can update the current application without replacing the entire HTML document. Routes, React, and detailed request code belong later.

## Do not confuse visible state with stored state

```text
what the browser currently shows
    ≠
what the server stores
    ≠
what a database stores
```

The fixture changes a message on screen but intentionally stores nothing. Refreshing removes that message. That is evidence about the current browser state, not a database lesson and not proof of persistence.

Likewise, a successful JSON response can describe an accepted request without proving what a future refresh will show. When persistence matters, inspect the next request and the behavior after refresh; later lessons will teach the server and database layers that make this reliable.

## Two ways to submit

**Mode: manual-proof.** This is an observation and reasoning exercise. There is no automated verifier and no source-text answer to match.

### Goal

Use browser evidence to explain why two submits that begin with a form produce different browser behavior.

### Starting state

Open the [Part 00 form observation fixture](/learn/fullstack/observe/forms), open Developer Tools → Network, and enable **Preserve log** if available.

### Requirements

For each interaction below:

1. Record your prediction before submitting.
2. Perform it and inspect the Network panel.
3. Record what actually happened.
4. Explain why it differed or matched.

| Interaction | What to do |
| --- | --- |
| A. Ordinary HTML form | Submit a short preview title. Trace the `POST`, redirect, and following document `GET`. |
| B. JavaScript-controlled form | Submit a short preview title. Trace the request and JSON response, then confirm the document did not navigate. |

For each one, answer:

- What caused the request?
- Did the browser navigate?
- Was a new HTML document requested?
- What representation did the response contain?
- Who decided what happened after submit?
- Did changing the visible UI necessarily persist anything?

### Verification

Compare your notes with the Network entries and the address bar. You have completed the exercise when you can point to evidence for each answer and explain the contrast without calling both interactions “just a form submit.”

### Hints

<details>
<summary>Hint 1: find the ordinary form sequence</summary>

Clear the Network log, submit the ordinary form, then look for a `POST` followed by a document `GET`. Preserve log keeps the first request visible after navigation.
</details>

<details>
<summary>Hint 2: recognize the JavaScript-controlled request</summary>

The request's response has `Content-Type: application/json`. The page's URL does not change, and the status message inside the page does.
</details>

<details>
<summary>Hint 3: test whether the visible result was stored</summary>

After the JavaScript-controlled interaction, refresh the page. The fixture was designed to show a UI change without persistence.
</details>

<details>
<summary>Revealable reference explanation</summary>

The ordinary form lets the browser perform its built-in submit. It sends form data with `POST`; the server responds with a redirect; the browser follows it with a document `GET` and renders returned HTML. The JavaScript-controlled form calls `preventDefault()`, so the browser does not navigate by default. JavaScript sends a JSON request, receives JSON, and changes the existing document's visible status. Neither fixture interaction writes a database record.
</details>

## Common mistakes

### “A form always reloads the page”

A plain form normally follows browser-default submission behavior. JavaScript can prevent that default and choose another interaction, as the second fixture shows.

### “JSON automatically becomes the screen”

JSON is data. The screen changes only because JavaScript decides to use that data to update the browser's representation of the document.

### “No navigation means no HTTP”

The JavaScript-controlled form still sends an HTTP request. Network evidence separates document navigation from data exchange.

### “I saw it, so it was saved”

Visible browser state can disappear on refresh. A UI update is not evidence that a server, much less a database, stored anything.

## When this goes wrong

1. If you cannot see the initial `POST`, enable Preserve log before submitting.
2. If the ordinary form seems to jump too quickly, filter Network by **Doc** and inspect the redirect entry before the following document request.
3. If the JavaScript form navigates, reload the fixture normally and check that browser JavaScript is enabled.
4. If the UI changes but you are unsure why, compare the response `Content-Type`, the address bar, and the document requests for both submissions.

## In the project

This completes B00's browser-side model. The issue tracker does not start until Part 03, but its interface will rely on this contrast: JavaScript can react to input, exchange data over HTTP, and update visible UI without treating every interaction as a new document visit.

## Closed-book checkpoint

Close the lesson before answering. Write an answer before opening each reveal.

1. What normally happens when a plain HTML form is submitted?
2. What does `preventDefault()` change?
3. Why can the UI change without loading another HTML document?
4. What is JSON?
5. If something appears on screen but disappears after refresh, what might that tell you?
6. Why does an SPA still communicate with a server over HTTP?

<details>
<summary>Reveal checkpoint answers</summary>

1. The browser performs its built-in submission behavior: it sends the form according to its method and action, then handles the response, which may navigate to another document.
2. It prevents the event's built-in browser behavior, allowing JavaScript to decide what happens next.
3. JavaScript can change the browser's representation of the current document after an interaction or data response.
4. JSON is a text representation of structured data.
5. It may be browser-only visible state rather than durable server or database state.
6. The loaded application still needs to request or send data; HTTP remains the browser/server message boundary even when a document is not replaced.
</details>

## Resources

### Read

- [MDN: Your first form](https://developer.mozilla.org/en-US/docs/Learn_web_development/Extensions/Forms/Your_first_form) — read the sections introducing `form`, `action`, `method`, controls, and submission. Stop before broader form validation topics.

### Reference

- [MDN: JSON](https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Scripting/JSON) — use the introduction to reinforce that JSON represents structured data; detailed parsing belongs later.
- [MDN: Single-page application](https://developer.mozilla.org/en-US/docs/Glossary/SPA) — a short reference for the model, not a framework tutorial.

## You are done when

- [ ] I predicted and inspected both fixture submissions in Developer Tools.
- [ ] I can distinguish browser-default submission from JavaScript-controlled behavior.
- [ ] I can explain why a JSON response can change UI without a new HTML document.
- [ ] I can separate visible browser state from server and database storage.
- [ ] I attempted the closed-book checkpoint before revealing its answers.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_00.md`
- Official sources: MDN HTML forms, JSON, SPA glossary; Chrome DevTools Network documentation
- Versions: web documentation consulted 2026-08-13
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/routes/routes.php`, `.dalt/Http/controllers/learn/fullstack-observation.php`, `.dalt/resources/views/learn/fullstack-observation.view.php`
- Curriculum authority: `CURRICULUM.md` §10 FS00.2 — topics and required outcome
- Laravel source: not applicable to this web-fundamentals lesson
