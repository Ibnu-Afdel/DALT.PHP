# B02 workspace — Type the future application

This TypeScript-only workspace models a small preview of the future Team Issue
Tracker. It is not the learner-owned React application; B03 starts that later.

```sh
mkdir -p .dalt/workspace
cp -R .dalt/course/build/B02-type-the-future-application/starter .dalt/workspace/b02-future-application
cd .dalt/workspace/b02-future-application
npm ci
npx tsc --version # 5.9.3
```

The copied starter intentionally does not typecheck. First complete the initial
model, then run `npm run typecheck` and `npm run run`. Next change the old
`assigneeId?: number` representation to `assignee: UserSummary | null`, run
typecheck before repairing callers, and finish the parser. `npm run test:parser`
is runtime evidence; `npm run typecheck` is static evidence.

To reset, remove only `.dalt/workspace/b02-future-application` and copy the
starter again. Reference snapshots are course authoring evidence, not a layout
the learner must copy.
