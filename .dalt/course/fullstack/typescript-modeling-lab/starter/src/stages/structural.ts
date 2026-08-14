type IssueSummary = { id: number; title: string };

const richerIssue = {
  id: 17,
  title: 'Broken search',
  status: 'todo',
  priority: 'high',
};

const summary: IssueSummary = richerIssue;

console.log(summary);
