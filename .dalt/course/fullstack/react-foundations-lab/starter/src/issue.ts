export type IssueStatus = 'todo' | 'in_progress' | 'done';
export type Priority = 'low' | 'medium' | 'high';
export type Issue = { id: string; title: string; status: IssueStatus; priority: Priority };
export const issues: Issue[] = [
  { id: 'ISS-1', title: 'Trace login failure', status: 'todo', priority: 'high' },
  { id: 'ISS-2', title: 'Document response shape', status: 'in_progress', priority: 'medium' },
  { id: 'ISS-3', title: 'Remove stale log', status: 'done', priority: 'low' },
];
