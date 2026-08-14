export type IssueStatus = 'backlog' | 'todo' | 'in_progress' | 'done';

export type Issue = {
  id: number;
  title: string;
  status: IssueStatus;
  description: string | null;
};
