import { validIssueJson } from './fixtures.js';
import { parseIssue } from './parser.js';

function issueLabel(issue: { id: number; title: string; status: string }): string {
  return `#${issue.id} ${issue.title} — ${issue.status}`;
}

const payload: unknown = JSON.parse(validIssueJson);
const issue = parseIssue(payload);

console.log(issueLabel(issue));
