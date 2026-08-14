# FS03.4 — Tailwind and accessible application UI

Lesson ID: FS03.4  
Part: 03 — React foundations  
Order: 4  
Status: Published  
Estimated effort: 60–90 minutes  
Difficulty: Applied  
Prerequisites: FS03.3 — Forms and state design  
Project milestone: B03 — The local issue tracker  
Primary source dossier: FSO_PART_01.md; REACT_DOCS.md  
Last reviewed: 2026-08-14

## Before you start

Required:

- FS03.3 — Forms and state design.

Recommended first:

- Review the existing root Tailwind/Vite setup if you have not used it before.

Going deeper in DALT Core — optional:

- None.

## Why UI structure is part of correctness

The local issue UI should be usable before it is connected to a server. A form without labels is hard to operate with assistive technology; a “button” made from a generic `div` loses keyboard behavior; a dense desktop-only row hides information on a narrow screen. These are not cosmetic defects.

Tailwind is a utility vocabulary for expressing layout and visual choices in the markup. It does not replace HTML semantics, sensible component boundaries, or a keyboard test. Use it to make the intent you already designed visible.

By the end, you can:

- choose semantic elements for navigation, controls, lists, and status messages;
- associate each form control with a visible label;
- distinguish a button action from a link navigation;
- compose responsive Flexbox/Grid layouts with Tailwind utilities;
- verify focus, disabled, empty, and narrow-screen behavior deliberately.

## Predict before reading

For each item, decide what it should be before continuing: “Open issue details,” “Save issue,” “Current status: done,” and “No issues match this filter.” A link, button, heading, list item, or status/message may each be appropriate—but not interchangeably.

## Semantics provide the interaction contract

Choose native HTML before styling it:

```tsx
<main>
  <header><h1>Atlas / API reliability</h1></header>
  <section aria-labelledby="issue-list-heading">
    <h2 id="issue-list-heading">Issues</h2>
    <ul>{/* issue rows */}</ul>
  </section>
</main>
```

Use a `<button type="button">` for an in-place action such as selecting or filtering. Use a link for navigation to another location. Use `<button type="submit">` inside the create form. These choices give keyboard behavior and names before any Tailwind class is added.

Every input needs a programmatic, visible label:

```tsx
<label className="grid gap-1" htmlFor="issue-title">
  Title
  <input id="issue-title" className="rounded border px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2" />
</label>
```

The answer to the prediction: details navigation is usually a link once routes exist (in this local stage it can be a button that changes selection); save is a submit button; a status is text with a meaningful label; an empty list is an honest message near the list.

## Layout follows content, then changes at a breakpoint

Start from readable source order. A useful small-screen issue row can stack its title and metadata. At a wider breakpoint, use Grid or Flexbox to place stable columns:

```tsx
<li className="grid gap-2 rounded border p-4 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center">
  <h3 className="min-w-0 font-medium">{issue.title}</h3>
  <span className="text-sm">{issue.status}</span>
  <span className="text-sm">{issue.priority}</span>
</li>
```

`minmax(0, 1fr)` lets long titles shrink rather than force horizontal overflow. Tailwind classes are not magic design tokens: inspect the result at a narrow width and change only where the content needs a new layout.

## Focus is evidence, not decoration

Do not remove focus outlines unless you replace them with a visible `focus-visible` treatment. Disabled means the action cannot currently happen; it must use the native `disabled` attribute and explain the condition when the reason is not obvious.

```tsx
<button type="submit" disabled={title.trim() === ''} className="rounded bg-violet-700 px-4 py-2 text-white disabled:cursor-not-allowed disabled:opacity-50">
  Create issue
</button>
```

## Utilities express a small layout system

The box model still applies: content has padding, borders, and margins; width constraints decide when text wraps. Use spacing consistently (`gap`, `p-*`, `space-y-*`) rather than nudging each child with unrelated margins. Typography communicates hierarchy: one page heading, section headings, readable body text, and secondary metadata. Extract a repeated status badge or button class only after it repeats with the same semantic meaning; do not create a premature component library for four classes.

Tailwind is already configured at the root for the learner application. This lesson teaches the choices, not a second installation. The B03 scaffold is the later point at which the learner receives the application entry and runs Vite; until then, validate semantics by inspecting markup and performing the keyboard/narrow-width check.

## Focused exercise — Make the local screen usable

**Mode: manual proof. B03 is still not created.** Take the resettable local screen from FS03.1–FS03.3 and make these targeted changes:

1. Use `main`, headings, a list, and a real form in source order.
2. Add visible labels, an error/empty message, and a native disabled submit control.
3. Make issue rows stack on narrow screens and use a simple Grid or Flex layout from `md` upward.
4. Add visible `focus-visible` styling to every keyboard-operable control.
5. Check it with keyboard only: Tab, Shift+Tab, Enter/Space, then resize to about 375px wide.

Verification evidence: every focusable control has a visible focus indicator; labels identify their inputs; the layout has no horizontal scrolling at the narrow check; buttons perform actions and links (if any) navigate. Record one thing you changed after the keyboard pass.

### Hints

<details><summary>Hint 1 — should a clickable issue row be a `div`?</summary>
No. Use a button for local selection or a link for navigation. A `div` would make you recreate native behavior.</details>

<details><summary>Hint 2 — where do responsive classes belong?</summary>
Keep the small-screen source layout as the base, then add `md:` only for the layout change.</details>

<details><summary>Answer and reasoning</summary>
Accessible structure is the baseline; Tailwind refines spacing and layout. The keyboard and narrow-screen check are the proof that the UI is usable, not just visually pleasant.</details>

## Closed-book checkpoint

1. When is a button correct and when is a link correct?
2. What two things make a text input understandable without relying on placeholder text?
3. Why start responsive styling with the narrow layout?
4. Why is a visible focus state required even for mouse users?

## Part 03 hand-off

You now have the concepts required for B03: typed local data, components, events, derived state, a controlled form, and accessible layout. The B03 build is deliberately not created by these lessons; it is the separate integration step after this Part.
