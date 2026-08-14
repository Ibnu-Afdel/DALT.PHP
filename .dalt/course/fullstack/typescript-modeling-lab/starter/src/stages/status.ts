type IssueStatus = 'backlog' | 'todo' | 'in_progress' | 'done';
type StatusIssue = { status: IssueStatus };

const invalidStatus: StatusIssue = { status: 'banana' };

console.log(invalidStatus);
