# Build milestones — contributor guide

A Build milestone is the point in a Part where the learner stops reading and produces
software. It is content, not an application.

## Layout

```text
.dalt/course/build/<ID>-<slug>/
├── README.md     the specification the learner reads     ← required
├── starter/      a resettable workspace they copy        ← optional
└── reference/    author-facing snapshots                 ← optional, never linked
```

`<ID>` is `B00`–`B11` or `C01`–`C07`. `<slug>` is lowercase kebab-case. The directory
name is parsed by `.dalt/Core/BuildMilestone.php`; a name that does not match, or a
directory with no `README.md`, fails the catalog load with an actionable message
rather than silently disappearing.

Only `README.md` is ever served. `reference/` may hold worked solutions for
verifying that a milestone is completable — `EXERCISE_STANDARD.md` §55 requires it
stay unreachable from learner navigation, and reading one fixed filename is what
keeps that true.

## Wiring a new milestone

Two edits, no code:

1. `.dalt/course/build/<ID>-<slug>/README.md`
2. the milestone entry in `.dalt/course/fullstack.php` gains
   `'route' => '/learn/fullstack/build/<id>'` and its `prerequisites`

`FullstackTrack` validates both directions — a spec with no route is unreachable, a
route with no spec is a 404 — so a half-finished wiring fails the test suite, not the
learner. There is one controller and one view for every milestone; do not add more.

## What a specification contains

Follow the shipped B00–B03. The shape:

```text
> blockquote: what exists when you finish
## What you are building        concrete, with the end state described
## Why this milestone exists    what it teaches, and what it costs to skip
## Before you start             prerequisites, workspace setup, toolchain check
## Stage 1..N                   build this / working looks like / check it yourself
## Decisions you have to make   real tradeoffs, stated without answers
## Acceptance criteria          a checklist the learner reads against their work
## Prove it to yourself         closed-book recall prompts, as prose
## What this unlocks            the seam the next Part plugs into
```

Every stage has all three of **build this**, **working looks like**, and **check it
yourself**. The third is what makes a stage verifiable by the person doing it; a stage
without it is a suggestion.

## The rules that are easy to break

**No forms.** No textareas, no checkboxes, no required inputs. An earlier version of
these pages collected the learner's predictions and traces into `required` textareas,
then discarded every word on submit. That is ceremony that verifies nothing while
implying assessment, and it is the exact failure mode this course exists to avoid.
Recall prompts are prose; the learner answers them in their own notes.

**No learner content is stored.** `ProgressManager` records one flag per milestone
and nothing else. If a future milestone seems to need stored answers, that is a
curriculum change to record in `CURRICULUM.md` first — not a field to add here.

**Completion is self-reported and says so.** The view states plainly that nothing was
checked. Never phrase a milestone as though the platform verified it.

**The learner owns the implementation.** Specify the behaviour, the evidence and the
tradeoffs. Do not ship the answer. `starter/` may hold data and scaffolding the
milestone is not about; it must not hold the thing the milestone asks them to write.

**Acceptance criteria describe software that ran.** "I understand X" is not a
criterion. "`npm run test` passes, including the whitespace-title case" is.

## One thing that looks like an exception and is not

`- [ ]` in the acceptance criteria renders as a GFM task list, so the page does show
checkboxes. They are emitted `disabled`, sit outside any `<form>`, and submit nothing
— they are typography for a list the learner reads against their own work. That is
different in kind from the `required` textareas this design replaced, which blocked
submission until the learner typed something and then discarded it.

If you ever make those checkboxes interactive or persisted, you have rebuilt the thing
that was removed. `FullstackTrackTest` guards the view; it cannot guard your intent.
