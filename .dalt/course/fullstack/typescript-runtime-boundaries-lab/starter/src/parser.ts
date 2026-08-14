import type { Issue, IssueStatus } from './model.js';

const issueStatusValues = new Set<string>(['backlog', 'todo', 'in_progress', 'done']);

export function isIssueStatus(value: unknown): value is IssueStatus {
  return typeof value === 'string' && issueStatusValues.has(value);
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

export function parseIssue(value: unknown): Issue {
  void value;
  throw new Error('TODO: establish runtime evidence for every Issue field, then reconstruct the Issue.');
}
