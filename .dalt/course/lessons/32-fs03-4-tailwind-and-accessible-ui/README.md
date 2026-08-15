# FS03.4 — Tailwind and accessible application UI

Lesson ID: FS03.4  
Title: Tailwind and accessible application UI  
Part: 03 — React foundations  
Order: 4  
Status: Published  
Estimated effort: 90–120 minutes  
Difficulty: Applied  
Prerequisites: FS03.3 — Forms and state design  
Project milestone: B03 — The local issue tracker  
Primary source dossier: `FSO_PART_01.md`; `REACT_DOCS.md`  
Last reviewed: 2026-08-14

## Why this matters

The screen works and is close to unusable. Everything is stacked at the left margin in serif defaults, the clickable rows are `div`s, and if you put the mouse down and navigate with the keyboard you cannot tell where you are.

That last one is the reason this lesson is not decoration. **A control you cannot reach or cannot see is a broken control**, in the same category as a handler that never fires — the difference is that your tests pass and you personally never hit it. Accessible structure is not a layer applied at the end by someone else; it is what makes the interaction contract real. A `div` with an `onClick` is not a button. It looks like one to you and to no one else.

The other reason: you are about to learn a utility CSS framework, and the failure mode is learning Tailwind instead of learning CSS. Tailwind has no opinions of its own. `flex` is a one-word spelling of `display: flex`, and if you do not know what a flex formatting context is, the utility gives you a way to produce layouts you cannot debug. Every utility in this lesson is introduced with the CSS it stands for, deliberately.

## Before you start

Required:

- FS03.3 — Forms and state design.
- Your lab copy at `.dalt/workspace/fs03-react-foundations`, with the create flow working and `npm test` passing.

Recommended first:

- Nothing. Tailwind v4 needs no configuration; `@import "tailwindcss"` in `src/index.css` and the Vite plugin are already set up in the lab.

Going deeper in DALT Core — optional:

- None.

## By the end

You should be able to:

- name the CSS behaviour behind each Tailwind utility you use;
- choose semantic elements by the interaction contract they carry, not by appearance;
- associate every form control with a visible label;
- decide between a button and a link by what the control does;
- build a layout that reflows at a breakpoint, writing the small screen first;
- make focus visible on every keyboard-operable control;
- distinguish `disabled` from "hidden" and from "invalid";
- operate your own interface with the keyboard alone and find at least one defect doing it.

## Predict before reading

Answer before reading on.

1. You add `onClick` to a `<div>` styled to look like a button. Name three things it still cannot do that a `<button>` does for free.
2. `flex` and `grid` are one word each in Tailwind. What does each one change about how children are positioned?
3. Tailwind's `md:flex-row` applies from the `md` breakpoint **upward**. What does that imply about which layout you should write unprefixed?
4. A submit button is `disabled`. Can a keyboard user still reach it with Tab?

Question 4 matters more than it looks: it determines whether "why can't I submit?" is discoverable or a dead end.

## Mental model

```text
1. semantics      what the element IS        → the interaction contract
2. structure      source order, grouping     → what a screen reader and Tab follow
3. layout         flex / grid / spacing      → where things sit
4. surface        colour, type, radius       → how it looks
```

Work down that list, never up. Each layer depends on the one above it being right.

The reason is practical, not moral. Choose `<button>` at layer 1 and you receive click, Enter, Space, Tab order, the focus ring, `disabled`, and the announced role — all of it, free, correct in every browser. Choose `<div>` and you owe every one of those, by hand, forever. Layer 4 can be redone in an afternoon; layer 1 mistakes propagate into every component built on top.

Tailwind lives entirely at layers 3 and 4. It has nothing to say about layers 1 and 2, which is precisely why those are where the real decisions are.

## 1. Semantics carry the interaction contract

Now the prediction. A `<div onClick>` cannot: receive keyboard focus via Tab, fire on Enter or Space, announce itself as a button, be disabled, or participate in form submission. That is five, and the standard workaround — `tabIndex={0}` plus `role="button"` plus a keydown handler for two keys — reimplements four of them incompletely and still misses the fifth.

The rule: **choose the element by what it does, then style it.**

```tsx
// Performs an action in this page → button.
<button type="button" onClick={() => onSelect(issue.id)}>{issue.title}</button>

// Navigates somewhere → link.
<a href={`/issues/${issue.id}`}>{issue.title}</a>
```

Buttons act; links navigate. A link can be middle-clicked, opened in a new tab, copied, and bookmarked, because it has a destination. A button has no destination and offers none of that. Using a link with `href="#"` to run an action gives the user a control that lies about what it will do — and, in Part 07 when real routing arrives, one that breaks the back button.

Structural elements matter for the same reason. `<main>`, `<h1>`, `<h2>`, `<ul>`/`<li>`, `<form>`, `<section>` describe the shape of the document so it can be navigated by headings and landmarks rather than by scrolling. Headings are an outline, not a font size — do not skip from `h1` to `h4` because the size looked right. Set the size with a utility.

### Labels

Every control needs a label the user can see and the browser can associate:

```tsx
<label htmlFor="issue-title">Title</label>
<input id="issue-title" value={title} onChange={…} />
```

`htmlFor` matches `id`. That association does two jobs: it announces the field's purpose, and it makes the label text a click target — which is a real usability win on touch, not only an accessibility one. Placeholder text is not a label; it disappears exactly when the user needs it, and it fails contrast requirements by design.

## 2. The box model, and spacing as a system

Before any layout utility, the CSS underneath. Every element is a box: content, then `padding` inside the border, then `border`, then `margin` outside it. Tailwind's preflight sets `box-sizing: border-box` globally, meaning a declared width **includes** padding and border. That is the sane behaviour and the reason widths stop surprising you.

```tsx
<div className="p-4 mt-2">
```

`p-4` is `padding: 1rem`. `mt-2` is `margin-top: 0.5rem`. The numbers are steps on a spacing scale — `4` is not pixels, it is the fourth step, `0.25rem` each. Using a scale rather than arbitrary values is most of what makes an interface look deliberate: three spacings used consistently read as designed, and eleven ad-hoc ones read as accidental.

Prefer padding inside a component and let the parent own the gaps between children — usually with `gap`, which avoids margin collapsing entirely.

## 3. Flexbox: one axis

```tsx
<div className="flex items-center justify-between gap-3">
```

`flex` sets `display: flex`, which creates a **flex formatting context**: the children stop being block-level boxes stacked vertically and become flex items laid along one axis. That is the whole mechanism; everything else configures it.

- `flex-row` (the default) lays items horizontally; `flex-col` vertically.
- `justify-between` distributes along the **main** axis — horizontal in a row.
- `items-center` aligns on the **cross** axis — vertical in a row.
- `gap-3` is `gap: 0.75rem`, space *between* items only, with none at the edges.

The confusion everyone has once: `justify-*` and `items-*` swap meaning when you switch to `flex-col`, because they refer to main and cross rather than to horizontal and vertical. Learn them as main/cross and they stop moving.

Use flex for one-dimensional arrangements: a row of filter buttons, a label beside a value, a header with a title at one end and an action at the other.

## 4. Grid: two axes

```tsx
<div className="grid grid-cols-1 gap-4 md:grid-cols-[2fr_1fr]">
```

`grid` sets `display: grid`, creating a grid formatting context with explicit rows and columns. `grid-cols-1` is one column — a plain stack. `md:grid-cols-[2fr_1fr]` is two columns from the `md` breakpoint, the first taking twice the free space of the second. The `fr` unit is a fraction of the space left after fixed sizes, which is why grid handles "list beside detail panel" cleanly and float-based approaches never did.

Flex for a line of things; grid for a page region with rows *and* columns. Both, not one or the other.

## 5. Responsive: small screen first

The answer to question 3: **write the small-screen layout unprefixed, then add breakpoints for larger.**

```tsx
<div className="flex flex-col gap-4 md:flex-row md:items-start">
```

Unprefixed utilities apply at every width. A `md:` prefix means "from the `md` breakpoint upward" — a `min-width` media query. So the unprefixed version is what a phone gets, and each prefix is an enhancement for more space.

Doing it the other way round means every small-screen rule has to override a large-screen one, and you accumulate a pile of `sm:` undo-utilities. Writing narrow-first also imposes a useful discipline: it forces you to decide what actually matters when there is no room for everything.

## 6. Focus and disabled

```tsx
<button
  type="submit"
  disabled={!isValid}
  className="rounded px-4 py-2 focus-visible:outline-2 focus-visible:outline-offset-2
             disabled:cursor-not-allowed disabled:opacity-50"
>
  Create issue
</button>
```

**Focus.** Browsers draw a focus ring by default. Many CSS resets remove it, and removing it without a replacement is the single most common accessibility defect on the web — it makes keyboard navigation impossible while looking tidier to someone using a mouse. `focus-visible` is the modern behaviour: the ring shows for keyboard focus and not for a mouse click, so you get the accessibility without the visual noise that motivated people to remove it. Never write `outline-none` without a `focus-visible:` replacement on the same element.

**Disabled.** The answer to question 4 is **no** — a `disabled` button is removed from the tab order and is not announced, so a keyboard user who cannot submit gets no explanation. That is the cost of `disabled`, and it is why the disabled state must be accompanied by visible text saying what is missing. `disabled:opacity-50` is the appearance; the message is the part that helps.

Three states people conflate: `disabled` means "not available yet, and here is why"; hidden means "not relevant at all"; invalid means "you supplied something wrong". They call for different treatment, and reaching for `disabled` for all three is how forms become guessing games.

## 7. Extracting repeated patterns

`className` strings get long. Three ways to handle it, in the order to reach for them:

1. **Leave it.** A long string on a component that appears once is not a problem worth solving.
2. **Extract the component**, not the class string. If three buttons share styling *and* behaviour, that is a `Button` component with a real props contract — the FS03.3 rule, unchanged.
3. **Extract a constant** only when styling repeats without a component boundary: `const cellClass = 'px-3 py-2 text-sm';`.

Do not build a design system this week. Tailwind's `@apply` exists and is a trap at this stage: it recreates the indirection utilities removed, and you lose the ability to read an element's appearance from the element itself. If you find yourself inventing `.btn-primary`, you wanted a component.

## Try it

**Before — predict.** Write down, before touching anything: which controls on your current screen can you reach with Tab, in what order, and which will show you where you are?

**Do — the keyboard pass.** In the running lab (`npm run dev`), put the mouse away completely.

1. Click once in the address bar, then press Tab repeatedly through the whole screen.
2. At each stop, note whether you can see where focus is.
3. Try to complete a full task with the keyboard alone: filter the list, select an issue, create a new one, mark it done.
4. Then open DevTools' device toolbar, or narrow the window to about 375px, and try the same task.

**Observe — record.**

- The actual tab order, and where it differs from the visual order.
- Every stop with no visible focus indicator.
- Every control you could not reach at all.
- At 375px: does anything overflow horizontally? Does the page scroll sideways?

**Explain.** Any control you could not reach — what element is it, and what would it need to be instead? Where tab order differed from visual order, what caused it? (Usually source order, which is the real reason layers 1 and 2 come before 3.)

Keep this list. It is your exercise.

## Common mistakes

### A `div` with an `onClick`

The mistake this lesson exists for. Severity: high — it removes the control from keyboard users entirely, and it is invisible to mouse testing and to Testing Library queries by role.

### `outline-none` with no replacement

Focus becomes invisible. Severity: high, same reason. If a reset removed the ring, put one back.

### Placeholder as label

Disappears on the first keystroke, fails contrast, and is not announced as a name. Use a real `<label>`.

### Headings chosen by size

`<h4>` because the size suited. This breaks the document outline that heading navigation depends on. Choose the level, set the size with a utility.

### Desktop-first with breakpoint undos

A pile of `sm:` rules cancelling unprefixed ones. Start narrow.

### `disabled` with no explanation

The user cannot press the button and cannot Tab to it to find out why. Pair it with visible text.

### Learning utilities instead of CSS

The one that costs most later. If you cannot say what `items-center` does in CSS terms, you cannot debug it when it does not centre — and the reason is almost always that you are on the wrong axis.

## When this goes wrong

1. **A control is unreachable by Tab.** It is not a natively focusable element. Make it a `<button>`.
2. **Focus is invisible.** Something set `outline-none`. Add a `focus-visible:` style.
3. **`items-center` does not centre.** Wrong axis — you are in `flex-col`, so it is now the horizontal one.
4. **The page scrolls sideways on a phone.** Something has a fixed width or a long unbreakable string. Bisect by adding a temporary outline to every element.
5. **A layout collapses at one width only.** Check which breakpoint prefixes are on the element; you probably have a gap between `md:` and the unprefixed base.
6. **A Tailwind class does nothing.** In v4 utilities are scanned from your source, so a class name assembled at runtime (`` `p-${size}` ``) is never seen. Write complete class names.
7. **Tab order jumps around.** Source order differs from visual order. Fix the source order rather than reaching for `tabIndex`.

## Exercise

### Goal

Turn the working screen into a usable, accessible, responsive interface — and fix every defect your keyboard pass found.

### Starting state

Your lab copy with FS03.3 complete, plus the written list from **Try it**.

### Requirements

1. Semantic structure: `main`, one `h1`, sensible heading levels, the issue list as a real `<ul>`/`<li>`, the create flow as a real `<form>`, in a source order that matches the visual order.
2. Every clickable row and action is a `<button>`. No `div` or `span` has an `onClick`.
3. Every form control has a visible `<label>` associated by `htmlFor`/`id`.
4. Visible `focus-visible` styling on every keyboard-operable control.
5. A single-column layout below `md`; from `md` upward, the list and the detail panel sit side by side using Grid.
6. Filter buttons in a Flex row that wraps rather than overflowing at 375px.
7. The disabled submit button has visible text explaining what is required.
8. Spacing from the scale only. No arbitrary values without a written reason.
9. Fix every defect on your keyboard-pass list, and record what each one was.

### Constraints

- No component library. Write the UI.
- No `@apply`, no custom CSS beyond the existing `@import "tailwindcss"`.
- No `tabIndex` above 0 — if the tab order is wrong, the source order is wrong.
- Do not change any behaviour from FS03.1–FS03.3. This lesson is layers 1–4 on working logic; `npm test` must still pass untouched.

### Verification

**Mode: manual proof plus tool-run.** The keyboard pass is the verification, and no tool performs it for you.

- `npm run typecheck` clean, `npm test` still passing with no test edits.
- Tab through the entire screen: every interactive control is reachable, in visual order, with a visible focus indicator at every stop.
- Complete filter → select → create → mark done using the keyboard alone.
- At 375px: no horizontal scrolling, filter buttons wrap, list and detail stack.
- At 1280px: list and detail sit side by side.
- Disable the submit by clearing the title — the explanation is visible on screen, not only in a tooltip.
- With `npm run build`, confirm the production build still succeeds.
- Write down one thing you changed *because of* the keyboard pass that you would not have noticed with a mouse.

### Hints

<details>
<summary>Hint 1 — should a clickable issue row be a <code>div</code>?</summary>

No. It performs an in-page action, so it is a `<button>`. Put the button inside the `<li>` rather than making the `<li>` itself clickable, so the list semantics survive.
</details>

<details>
<summary>Hint 2 — where do responsive prefixes belong?</summary>

The unprefixed classes are the phone layout. Add `md:` only for what changes with more room. If you are writing `md:flex-col`, you probably started from the wrong end.
</details>

<details>
<summary>Hint 3 — the filter row at 375px</summary>

A flex row does not wrap by default. `flex-wrap` plus `gap-2` is the whole fix, and it is a better answer than shrinking the buttons.
</details>

<details>
<summary>Hint 4 — explaining a disabled button</summary>

Render a short message next to it, driven by the same derived condition that sets `disabled`. One source of truth, two consumers — FS03.2's rule applied to UI text.
</details>

<details>
<summary>Reference explanation — read after an honest attempt</summary>

The layout is one `main`, an `h1`, the create form, a filter row (`flex flex-wrap gap-2`), and a results region that is `grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-4`. Rows are `<li>` containing a full-width `<button>`.

The transferable idea is the working order: **semantics, then structure, then layout, then surface.** Every defect the keyboard pass finds is a layer-1 or layer-2 mistake showing up late — a `div` that should have been a button, or a source order that was chosen for visual convenience. None of them are Tailwind problems, which is the point. Utilities are the last layer and the least consequential one.

The disabled-button message is worth noticing as a pattern: the same derived boolean drives `disabled` and the explanation, so they cannot disagree. That is FS03.2's rule, in a place people do not think of as state.
</details>

## In the project

This completes the interface for **B03 — The local issue tracker**: React, TypeScript and Tailwind rendering a usable issue tracker from typed local data.

**B03 itself is deliberately not started.** No project scaffold, no `resources/app/`, no scaffold manager, no B03 route. Everything you built lives in the resettable lab under `.dalt/workspace/`, and the framework skeleton is untouched. That is the owner's standing decision, not an oversight — resume it only when asked.

## Part 03 hand-off

Four lessons, one screen:

```text
FS03.1   components, typed props, lists and keys      the shape
FS03.2   state, events, ownership, derivation         the behaviour
FS03.3   forms, drafts, lifting, derived validity     the input boundary
FS03.4   semantics, layout, focus, responsive         the usable surface
```

Everything on that screen is local and instantly correct, because there is one copy of every fact in one process. Part 04 breaks that: the real issues live on a server, the browser holds a copy, and the copy can be wrong. Loading states, failures, and staleness are not new features — they are what "one source of truth" costs once the truth is somewhere else.

Notice how much you did not have to think about here. That is the baseline Part 04 is measured against.

## Closed-book checkpoint

Close the lesson and the lab.

1. Name three things a `<button>` gives you that a `<div onClick>` does not.
2. When is a link correct and a button wrong?
3. What does `flex` change about how children are laid out? What about `grid`?
4. Why do `justify-*` and `items-*` appear to swap when you switch to `flex-col`?
5. Why write the small-screen layout unprefixed?
6. Why is `focus-visible` preferable to styling `focus`?
7. What is the cost of `disabled` for a keyboard user, and what must accompany it?
8. Give a good and a bad reason to extract a repeated `className` string.

Then reopen and correct your answers in a different colour.

## Resources

### Read

- [MDN: HTML: A good basis for accessibility](https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Accessibility/HTML) — semantics before ARIA.
- [MDN: Flexbox basics](https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/CSS_layout/Flexbox) — main and cross axes.
- [Tailwind: Styling with utility classes](https://tailwindcss.com/docs/styling-with-utility-classes)

### Go deeper

- [MDN: CSS grid layout](https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/CSS_layout/Grids)
- [MDN: `:focus-visible`](https://developer.mozilla.org/en-US/docs/Web/CSS/:focus-visible)
- [Tailwind: Responsive design](https://tailwindcss.com/docs/responsive-design)

### Reference

- [WAI: Keyboard accessibility](https://www.w3.org/WAI/perspective-videos/keyboard/)
- [MDN: The box model](https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Styling_basics/Box_model)

## You are done when

- [ ] No `div` or `span` on the screen has an `onClick`.
- [ ] Every control is reachable by Tab, in visual order, with a visible focus indicator.
- [ ] I completed filter → select → create → mark done with the keyboard alone.
- [ ] Every form control has a visible associated label.
- [ ] At 375px nothing scrolls horizontally and the filter row wraps; at 1280px the list and detail sit side by side.
- [ ] The disabled submit explains itself on screen.
- [ ] I can name the CSS behaviour behind every utility I used.
- [ ] I recorded at least one defect the keyboard pass found that a mouse would have hidden.
- [ ] `npm run typecheck`, `npm test` and `npm run build` all pass, with no test edits.
- [ ] I attempted the closed-book checkpoint without notes.

---

## Maintainer source record

- Source dossier: `docs/dalt-fullstack/sources/FSO_PART_01.md`; `docs/dalt-fullstack/sources/REACT_DOCS.md`
- Official sources: MDN — HTML accessibility basics, Flexbox, CSS grid layout, box model, `:focus-visible`; Tailwind CSS — utility classes, responsive design; W3C WAI — keyboard accessibility
- Versions: Tailwind CSS 4.3.0, React 19.2.3, TypeScript 5.9.3, Vite 8.0.12 (CR-08 pinned toolchain)
- Consulted: 2026-08-14
- DALT files inspected: `.dalt/course/fullstack/react-foundations-lab/starter/**` (Tailwind v4 via `@tailwindcss/vite`, no config file)
- Curriculum authority: `CURRICULUM.md` §13 FS03.4 — practical CSS/Tailwind fundamentals only, no component library
- Laravel bridge: not applicable — client-side presentation has no DALT or Laravel counterpart
