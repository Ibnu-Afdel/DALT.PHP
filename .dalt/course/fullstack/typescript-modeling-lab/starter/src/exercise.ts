type InitialIssue = {
  readonly id: number;
  title: string;
  description?: string;
  status: 'backlog' | 'todo' | 'in_progress' | 'done';
  assigneeId?: number;
};

const firstIssue: InitialIssue = {
  id: 31,
  title: 'Clarify empty-state copy',
  status: 'todo',
  assigneeId: 7,
};

const unassignedIssue: InitialIssue = {
  id: 32,
  title: 'Review keyboard focus',
  status: 'backlog',
};

console.log(firstIssue.title, unassignedIssue.title);
