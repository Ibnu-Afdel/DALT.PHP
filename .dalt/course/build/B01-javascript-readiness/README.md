# B01 workspace — JavaScript readiness

This is a deliberately small, resettable workspace for B01. It is course-owned
and lives inside this repository; it is not the future Issue Tracker.

From the repository root, create your working copy:

```sh
mkdir -p .dalt/workspace
cp -R .dalt/course/build/B01-javascript-readiness/starter .dalt/workspace/b01-issue-triage
cd .dalt/workspace/b01-issue-triage
```

Start by creating `main.mjs` beside `issue-data.mjs`. As the milestone asks you
to evolve the program, add `issue-tools.mjs` and move the useful functions into
it. Run the program with `node main.mjs`.

To begin again, remove only `.dalt/workspace/b01-issue-triage` and repeat the
copy command. Your learner work stays inside `.dalt`, away from framework and
future application files.
