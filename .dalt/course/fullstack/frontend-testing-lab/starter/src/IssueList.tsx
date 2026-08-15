import type { Issue } from './issue';

export function IssueList({ issues }: { issues: Issue[] }) {
  if (issues.length === 0) return <p>No issues yet.</p>;

  return (
    <ul>
      {issues.map((issue) => (
        <li key={issue.id}>
          <span>{issue.title}</span> <span>{issue.priority}</span>
        </li>
      ))}
    </ul>
  );
}
