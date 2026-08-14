import type { Issue } from './issue.js';
export function IssueList({ issues }: { issues: readonly Issue[] }) {
  return <ul>{issues.map((issue) => <li key={issue.id}>{issue.id}: {issue.title}</li>)}</ul>;
}
