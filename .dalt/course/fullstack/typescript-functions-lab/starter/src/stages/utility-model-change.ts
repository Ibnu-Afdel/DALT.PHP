type IssueStatus = 'backlog' | 'todo' | 'in_progress' | 'done' | 'blocked';

const labels: Record<IssueStatus, string> = {
  backlog: 'Backlog',
  todo: 'To do',
  in_progress: 'In progress',
  done: 'Done',
};

void labels;
