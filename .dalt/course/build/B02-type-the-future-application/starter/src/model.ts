// Phase 1: replace these gaps with small, honest domain contracts.
export type IssueStatus = 'todo' | 'in_progress' | 'done';
export type IssuePriority = 'low' | 'medium' | 'high';

export type UserSummary = { readonly id: number; name: string };
export type Project = { readonly id: number; name: string };

// Start with the old representation. Phase 2 changes it to assignee: UserSummary | null.
export type Issue = TODO_Issue;
export type CreateIssueInput = TODO_CreateIssueInput;
export type IssueUpdate = TODO_IssueUpdate;

export type RequestState<T> =
  | { state: 'idle' }
  | { state: 'loading' }
  | { state: 'success'; data: T }
  | { state: 'error'; message: string };
