export type IssueStatus = string;

export interface UserSummary {
  readonly id: number;
  name: string;
}

export type IssueSummary = {
  id: number;
  title: string;
};

export type Issue = {
  readonly id: number;
  title: string;
  description?: string;
  status: IssueStatus;
  assigneeId?: number;
};

export const triageIssue: Issue = {
  id: 17,
  title: 'Broken search',
  status: 'todo',
};

export const richerIssue = {
  id: 18,
  title: 'Refresh documentation',
  status: 'backlog',
  priority: 'high',
};

export const summary: IssueSummary = richerIssue;
