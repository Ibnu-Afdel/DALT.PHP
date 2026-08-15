import type { Issue } from './issue.js';

// FS03.1 starting point: one component owns both the list and the row markup.
// Part of the lesson's work is deciding where that boundary should actually be.
export function IssueList({ issues }: { issues: readonly Issue[] }) {
  return (
    <ul>
      {issues.map((issue) => (
        <li key={issue.id}>
          {issue.id}: {issue.title}
        </li>
      ))}
    </ul>
  );
}
