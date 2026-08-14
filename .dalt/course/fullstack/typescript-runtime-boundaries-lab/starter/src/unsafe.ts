import { malformedIssueJson } from './fixtures.js';
import type { Issue } from './model.js';

function displayIssue(issue: Issue): string {
  return `#${issue.id + 1} ${issue.title.toUpperCase()} (${issue.status})`;
}

const parsed: unknown = JSON.parse(malformedIssueJson);
const issue = parsed as Issue;

console.log(displayIssue(issue));
