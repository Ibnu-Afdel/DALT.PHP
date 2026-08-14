export type IssueStatus = 'backlog' | 'todo' | 'in_progress' | 'done';
export type IssuePriority = 'low' | 'medium' | 'high';

export type Issue = {
  readonly id: number;
  title: string;
  description: string | null;
  status: IssueStatus;
  priority: IssuePriority;
  createdAt: string;
};

export type Project = { readonly id: number; name: string };
export type IssueSummary = Pick<Issue, 'id' | 'title' | 'status'>;
export type NewIssue = Omit<Issue, 'id' | 'createdAt'>;
type EditableIssue = Pick<Issue, 'title' | 'description' | 'status' | 'priority'>;
export type IssuePatch = Partial<EditableIssue>;
export type Status = Issue['status'];
export type Result<T> = { ok: true; value: T } | { ok: false; error: string };
export type IssuePredicate = (issue: Issue) => boolean;

export function findIssueById(issues: Issue[], id: number): Issue | undefined {
  return issues.find((issue) => issue.id === id);
}

export function toIssueSummary(issue: Issue): IssueSummary {
  return { id: issue.id, title: issue.title, status: issue.status };
}

export function filterIssues(issues: Issue[], predicate: IssuePredicate): Issue[] {
  return issues.filter(predicate);
}

export function first<T>(items: T[]): T | undefined {
  return items[0];
}

export function findById<T extends { id: number }>(items: T[], id: number): T | undefined {
  return items.find((item) => item.id === id);
}

export function readField<T, K extends keyof T>(value: T, key: K): T[K] {
  return value[key];
}

export function findIssueResult(issues: Issue[], id: number): Result<Issue> {
  const issue = findIssueById(issues, id);
  return issue === undefined ? { ok: false, error: `Issue ${id} was not found.` } : { ok: true, value: issue };
}

export const statusLabels: Record<IssueStatus, string> = {
  backlog: 'Backlog',
  todo: 'To do',
  in_progress: 'In progress',
  done: 'Done',
};

export type IssueDraft = { title?: string; description?: string };
export type FinalIssueDraft = Required<IssueDraft>;
export type ReadonlyIssueSummary = Readonly<IssueSummary>;
