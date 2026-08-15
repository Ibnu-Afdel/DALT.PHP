export type IssueStatus = 'todo' | 'doing' | 'done';
export type IssuePriority = 'low' | 'medium' | 'high';

export type Issue = {
  id: string;
  projectId: string;
  title: string;
  status: IssueStatus;
  priority: IssuePriority;
};

export type IssueDraft = {
  projectId: string;
  title: string;
  priority: IssuePriority;
};

const STATUSES: IssueStatus[] = ['todo', 'doing', 'done'];
const PRIORITIES: IssuePriority[] = ['low', 'medium', 'high'];

/**
 * The cheapest test level in the track: an ordinary function over `unknown`.
 * No React, no server, no browser. FS04.3 built this; FS07.3 keeps testing it here.
 */
export function parseIssue(value: unknown): Issue {
  if (typeof value !== 'object' || value === null) {
    throw new Error('An issue must be an object');
  }
  const record = value as Record<string, unknown>;

  if (typeof record.id !== 'string' || record.id === '') throw new Error('issue.id must be a non-empty string');
  if (typeof record.projectId !== 'string') throw new Error('issue.projectId must be a string');
  if (typeof record.title !== 'string') throw new Error('issue.title must be a string');
  if (!STATUSES.includes(record.status as IssueStatus)) throw new Error(`issue.status is not a known status: ${String(record.status)}`);
  if (!PRIORITIES.includes(record.priority as IssuePriority)) throw new Error(`issue.priority is not a known priority: ${String(record.priority)}`);

  return {
    id: record.id,
    projectId: record.projectId,
    title: record.title,
    status: record.status as IssueStatus,
    priority: record.priority as IssuePriority,
  };
}
