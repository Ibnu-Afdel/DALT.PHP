# Challenge: Broken Routing

**Difficulty:** Beginner · **Bugs:** 2 · **Lesson:** [02 — Routing](../../lessons/02-routing/README.md)

## Start

```bash
php artisan challenge:start broken-routing
php artisan serve
```

The command backs up your files and copies the broken versions in. `php artisan challenge:stop` restores them when you are done — never copy or delete these files by hand.

## Observe the symptoms first

Do this before opening `routes/routes.php`.

```bash
curl -i http://localhost:8000/posts          # works
curl -i http://localhost:8000/posts/1        # works
curl -i http://localhost:8000/posts/create   # wrong page, not a 404
curl -i http://localhost:8000/posts/1/edit   # 404
```

Two different failures. Note that `/posts/create` does **not** 404 — it returns a page, just not the one you asked for. That distinction is the whole first bug.

Write down what each result implies about how the router reached its decision.

## Hints

Work down this list only as far as you need.

<details>
<summary>Hint 1 — where to look</summary>

Both defects are in `routes/routes.php`. `framework/Core/Router.php` is correct and does not need changing. Read it to understand *why* the route table produces these results.
</details>

<details>
<summary>Hint 2 — the one that returns the wrong page</summary>

`/posts/create` reached a handler, so some pattern matched it. Read the route table from the top and stop at the first entry that could match the string `/posts/create`. What does the router capture as the parameter?
</details>

<details>
<summary>Hint 3 — the one that 404s</summary>

A 404 means no entry matched at all. The controller file for this URL exists on disk. Check whether anything ever told the router about it.
</details>

<details>
<summary>Hint 4 — the concepts</summary>

- The route table is an ordered list and the first method-and-URI match wins. A placeholder such as `{id}` happily matches ordinary words, so a generic pattern placed above a specific one will shadow it forever.
- A controller file existing on disk means nothing on its own. Routing is explicit registration, not filesystem discovery.
</details>

## Success criteria

- `/posts` lists posts
- `/posts/create` shows the create form
- `/posts/1` shows post 1
- `/posts/1/edit` shows the edit form
- An unregistered path still returns 404

## Verify

```bash
php artisan challenge:verify
```

Then confirm the behavior yourself — the checks are a completion signal, not proof:

```bash
curl -i http://localhost:8000/posts/create   # expect the create form
curl -i http://localhost:8000/posts/1/edit   # expect the edit form
```

## Finish

```bash
php artisan challenge:stop
```

## Related

- **Lesson 02: Routing** — read this first
- **Next challenge:** Broken Middleware
