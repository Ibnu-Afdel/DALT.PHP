# Part 03 lab — React foundations

The course-owned starter for FS03.1 through FS03.4. One lab, grown across all four
lessons. It is deliberately isolated from the framework and from the future Issue
Tracker: **nothing you do here is B03.**

```sh
mkdir -p .dalt/workspace
cp -R .dalt/course/fullstack/react-foundations-lab/starter .dalt/workspace/fs03-react-foundations
cd .dalt/workspace/fs03-react-foundations
npm ci
```

Delete only `.dalt/workspace/fs03-react-foundations` and repeat the copy to reset.

## The four commands

| Command | What it proves |
|---|---|
| `npm run dev` | The screen in a real browser, on `http://localhost:5174`. This is where FS03.2's events, FS03.3's form, and FS03.4's keyboard and narrow-screen checks happen. |
| `npm test` | Rendered DOM output, via Vitest + React Testing Library. |
| `npm run typecheck` | The declared prop and model contracts, via `tsc --noEmit`. |
| `npm run build` | The whole thing still compiles for production. |

`npm test` and `npm run typecheck` both pass on the unmodified starter. If either
fails before you have changed anything, the lab is broken — that is a defect to
report, not an exercise.

The compiler and the tests answer different questions. A green typecheck says your
declared shapes agree with each other; a passing test says the browser DOM actually
came out the way you expected. Neither is a server or persistence test, and neither
replaces the keyboard pass in FS03.4.

## What is here

```text
index.html            Vite entry
vite.config.ts        React plugin, Tailwind plugin, Vitest (jsdom, globals, setup file)
tsconfig.json         strict; checks src/
src/main.tsx          mounts <App /> in StrictMode
src/index.css         @import "tailwindcss"
src/App.tsx           the screen you grow across FS03.1 -> FS03.4
src/IssueList.tsx     FS03.1 starting point: list and row markup in one component
src/IssueList.test.tsx  worked example of a rendering test
src/issue.ts          the typed local data — the Issue model from FS02.2
src/setup-tests.ts    registers the jest-dom matchers
```

`src/App.tsx` is intentionally minimal. Growing it is the exercise.

## Notes

- Tailwind v4 needs no config file. `@import "tailwindcss"` in `src/index.css` plus
  the Vite plugin is the whole setup; utilities are scanned from your source.
- Versions are pinned exactly and match the toolchain recorded under CR-08 in
  `PROJECT_BLUEPRINT.md`. Do not bump them inside a lesson.
- `vite.config.ts` is excluded from `tsconfig.json`'s `include`. Vite 8 (rolldown) and
  Vitest 4 ship conflicting plugin types, and that argument is not what this lab
  teaches. `npm run typecheck` checks your source, which is the part under study.
