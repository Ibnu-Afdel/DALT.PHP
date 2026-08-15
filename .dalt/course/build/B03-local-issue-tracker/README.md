> **What exists when you finish:** the Team Issue Tracker, running in your own
> repository at `resources/app/`, served by a real DALT route, with filtering,
> selection, creation and an accessible responsive layout — all from typed local
> data, on your own branch.

## What you are building

**This is the milestone where the project starts.** Everything before it lived in a
throwaway workspace under `.dalt/workspace/`. From here you are working in the real
repository, in the learner's zone, and the code you write now survives to Part 12.

```text
resources/app/            your React + TypeScript source          ← new, yours
routes/routes.php         a route serving the tracker             ← yours
app/Http/controllers/     the controller behind it                ← yours
resources/views/          the PHP page the React app mounts into  ← yours

framework/  config/  public/  .dalt/                              ← never touched
```

The screen itself is the one you already built across FS03.1–FS03.4: a project
header, a filterable issue list, a detail panel, a create form, keyboard-usable and
responsive. You are not designing it again. You are **moving it into the real
application and making it run on the real stack**.

That move is the milestone. It is also the first time you will see how a PHP-served
page and a React bundle actually fit together — which is the architecture every
remaining part of this course builds on.

## Why this milestone exists

Three reasons, in order of how much they matter later.

**Because the lab has no server.** Part 04 makes this interface talk to DALT. It
cannot do that from a directory under `.dalt/workspace/` that Vite does not build and
no route serves. B03 is the move that makes Part 04 possible, and doing it now — while
the app has no server dependencies at all — means when the fetch arrives you are
debugging one new thing instead of two.

**Because you have to see the seam.** A React app does not float. Something serves an
HTML document; that document loads a bundle; the bundle mounts into an element. You
traced exactly that in B00 without knowing it was your own architecture. Wiring it
yourself is the difference between using a framework and understanding one.

**Because the project is now yours to keep.** No scaffold is installed for you. There
is no generator, and that is deliberate — `PROJECT_BLUEPRINT.md` puts the
specification, the fixtures and the teaching on the course, and the implementation on
you. A tracker you assembled is one you can debug.

## Before you start

All four Part 03 lessons complete, with the lab working: filters, selection, create
form, keyboard pass done.

**Take a branch first.** The repository's `main` is the clean framework skeleton; your
issue tracker is not part of it.

```sh
git switch -c fullstack-build
```

Everything from here to Part 12 happens on that branch. It is also your recovery
mechanism — commit at the end of each stage, and a bad refactor costs you one
`git restore`, not an evening.

The toolchain is already installed and pinned at the repository root: React 19.2.3,
TypeScript 5.9.3, Vite 8, Vitest 4, Tailwind 4, ESLint 9. Confirm it:

```sh
npm install
npm run typecheck   # clean
npm run lint        # clean
npm run test        # no test files yet, exits 0
```

If any of those fail before you have written a line, stop and report it — that is a
defect in the course, not in your setup.

---

## Stage 1 — Give the application a source root

Create `resources/app/` and point the build at it.

`vite.config.mjs` currently builds `resources/js/app.js`. Change the input to a new
`resources/app/main.tsx`. Keep the Tailwind import — either move `resources/css/input.css`
into your entry or import it from there.

**Working looks like:** `npm run build` succeeds and `public/build/.vite/manifest.json`
lists your new entry.

**Check it yourself:** open the manifest and read it. You should be able to say which
file on disk produced which hashed asset. That manifest is how the PHP side will find
your bundle in Stage 2 — it is not a build artifact you can ignore.

> Note what you did *not* have to do: install React, configure JSX, add a test runner,
> or set up Tailwind. That work was done when the root manifest was wired. The cost is
> that a fresh clone of this framework carries a React toolchain it may not use — a
> deliberate trade recorded as Option A in `IMPLEMENTATION_PLAN.md` §5.3.

## Stage 2 — Serve it from DALT

Add a route, a controller and a view so that a real HTTP request returns a page your
bundle mounts into.

- `routes/routes.php` — a `GET` route, for example `/app`.
- `app/Http/controllers/` — a controller returning a view. Plain PHP file, no class;
  follow `welcome.php`.
- `resources/views/` — a view with a mount element (`<div id="root"></div>`) and the
  script and stylesheet tags for your built assets.

You do not hand-write those tags. The framework ships a `vite()` helper — read it in
`framework/Core/functions.php` — which looks your entry up in the manifest you just
read and returns the `<link>` and `<script type="module">` for it:

```php
<head>
  <title>Issue tracker</title>
  <?= vite('resources/app/main.tsx') ?>
</head>
<body>
  <div id="root"></div>
</body>
```

The argument is the manifest key, which is the source path from Stage 1 — not the
hashed output filename. That indirection is the whole point of the manifest: the hash
changes on every build and nothing in your PHP has to know.

**Working looks like:** `php artisan serve`, visit `/app`, and the React screen
renders.

**Check it yourself — do this with the Network panel open, and recognise it:**

1. A document request to `/app` returns HTML with status 200.
2. That document causes a request for your JS bundle, and one for CSS.
3. The HTML arrives containing an empty `<div id="root">`; the content appears after
   the bundle runs.

That is B00's interaction #1, and now you built the server side of it. If the page is
blank, work down that list rather than guessing — it tells you whether the failure is
the route, the asset path, or the mount.

**The one that catches everyone:** `vite()` prefers the manifest, always. Read the
first branch of the function and you will see why that matters — *if
`public/build/.vite/manifest.json` exists, the dev server is never consulted.* Stage 1
made you run `npm run build`, so from now on that file exists.

The consequence is worth spelling out, because nothing reports it: start `npm run dev`,
edit a component, reload `/app`, and **you will see the old build**. No error, no
warning in either terminal, no clue in the Network panel beyond a filename hash that
never changes. People lose an evening to this.

So pick a mode deliberately:

```sh
# Built mode — what production does. Re-run after every change.
npm run build

# Dev mode — hot reload. The manifest has to be gone for vite() to look for the server.
rm -rf public/build && npm run dev
```

Remember to `npm run build` again before Stage 5, or you will commit a `/app` that
serves nothing. Part 10 revisits this properly under Docker, where the same decision
becomes an environment flag instead of a deleted directory.

## Stage 3 — Move the screen in

Port your lab components into `resources/app/`. Keep the boundaries you already
settled: `ProjectPage` owns the data, `IssueList` and `IssueRow` render, `IssueDetail`
displays, `CreateIssueForm` owns the draft.

Keep the local data in a module — `resources/app/issue.ts` or similar. **Nothing
fetches anything yet.**

**Working looks like:** everything that worked in the lab works at `/app` — filters
narrow, selection shows detail, create adds a row, "mark done" updates one issue
immutably.

**Check it yourself:** `npm run typecheck` and `npm run lint` are both clean, and this
is stricter than the lab was — the root config enables `noUnusedLocals` and
`typescript-eslint`. Fix what it finds rather than relaxing the config. If a rule is
genuinely wrong for this project, disable it deliberately in `eslint.config.mjs` with
a comment saying why.

## Stage 4 — Bring the tests with you

Port your lab tests to `resources/app/`, and add the ones you did not have.

At minimum: the list renders one row per issue; the empty state appears for an empty
list; a whitespace-only title is rejected; a valid submit adds exactly one row.

They should run unchanged. The lab registered the jest-dom matchers through its own
`vite.config.ts`; here `resources/setup-tests.ts` does it, wired from the root
`vite.config.mjs`, and `@testing-library/user-event` is installed at the root exactly
as it was in the lab. If a matcher comes back as *"Invalid Chai property:
toBeInTheDocument"*, that setup file is missing or unregistered — report it, because
your typecheck will stay green while it happens and that combination is a course
defect, not yours.

**Working looks like:** `npm run test` runs them and passes.

**Check it yourself:** make each test fail on purpose before you trust it. Break the
component it covers, watch it go red, put it back. A test you have never seen fail is
a test you have no evidence about — this is the same standard the course applies to
its own challenges, applied to your work.

Prefer queries by role and label over test IDs. `getByRole('button', { name: /create/i })`
fails when you turn a button into a div; `getByTestId('create')` does not. FS03.4's
accessibility work is what makes those queries possible, and the tests are how it
stays true.

## Stage 5 — Confirm the boundary held

The framework must still be a framework.

```sh
php artisan test        # same count as before you started — you changed no framework code
git status --short      # only resources/, routes/, app/, public/build, vite.config.mjs
```

**Check it yourself:** nothing under `framework/`, `config/`, or `.dalt/` appears in
your diff. If it does, something went in the wrong zone and now the course platform
depends on your project — the one thing the architecture forbids.

Then commit. `git log --oneline` on `fullstack-build` should read as a sequence of
stages, which is what makes the next ten parts recoverable.

---

## Decisions you have to make

- **Dev server or built assets?** Vite's dev server gives hot reload and needs the
  view to point at `localhost:5173`; built assets are what production serves. Real
  projects support both and switch on an environment flag. Do the simple one now and
  write down which you chose — Part 10 will make you revisit it under Docker.
- **What is the route?** `/app`, `/issues`, `/`? Replacing `/` means deciding what
  happens to the welcome page. There is no wrong answer, only an undecided one.
- **Where does local data live?** A module you import is simplest. A module that
  *looks* like an API — a function returning a promise — is more work now and less
  churn in Part 04. Either is fine; knowing which you picked and why is the point.
- **How much of the lab comes across?** You may improve things while porting. You may
  also change three things at once and lose track of which broke the tests. Port
  first, verify, then improve.

## Acceptance criteria

Read these against software you actually ran. Nothing here is checked automatically.

- [ ] I am on the `fullstack-build` branch and have committed at each stage.
- [ ] `resources/app/` holds my React + TypeScript source and Vite builds it.
- [ ] A real DALT route serves a page and the React app mounts into it.
- [ ] In the Network panel I can point at the document request, the bundle request,
      and the moment content appears.
- [ ] Filtering, selection, creation and "mark done" all work at that route.
- [ ] `npm run typecheck`, `npm run lint`, `npm run test` and `npm run build` all pass.
- [ ] My tests query by role and label, and I watched each one fail on purpose.
- [ ] `php artisan test` reports the same counts it did before I started this milestone.
- [ ] `git status` shows no change under `framework/`, `config/`, or `.dalt/`.
- [ ] Every value on screen came from typed local data — nothing fetches anything.

## Prove it to yourself

Close the editor. In your notes:

1. Trace a request to your route, from address bar to rendered issue list, naming
   every hop. Compare it with the trace you wrote in B00.
2. What does the Vite manifest do, and who reads it?
3. Which zones of this repository does your project live in, and why does it matter
   that it stays there?
4. Which components own state, and which only receive props?
5. Everything is local right now. Name three things that become hard the moment the
   issues live on a server.

Question 5 is Part 04's syllabus. Write your answer down before you read it there.

## What this unlocks

Part 04 replaces your local `issue.ts` with a request to a server, and the four
boundaries from FS01.2 become four things a user can see: loading, offline, an issue
that does not exist, and a response you could not parse.

Everything you built here stays. That is what makes it a project rather than an
exercise — and why the branch, the tests, and the zone discipline were worth the
extra stage.
