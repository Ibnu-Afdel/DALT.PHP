import {
  filterIssues,
  findById,
  findIssueById,
  findIssueResult,
  first,
  readField,
  statusLabels,
  toIssueSummary,
  type Issue,
  type Project,
} from './functions.js';

const issues: Issue[] = [
  { id: 17, title: 'Broken search', description: null, status: 'todo', priority: 'high', createdAt: '2026-08-14' },
  { id: 18, title: 'Clarify empty state', description: 'Mention filters.', status: 'backlog', priority: 'low', createdAt: '2026-08-15' },
];
const projects: Project[] = [{ id: 3, name: 'Learning platform' }];

console.log(findIssueById(issues, 17)?.title ?? 'missing');
console.log(filterIssues(issues, (issue) => issue.priority === 'high').map(toIssueSummary));
console.log(first(issues)?.title, first(projects)?.name);
console.log(findById(projects, 3)?.name);
console.log(readField(issues[0], 'status'), statusLabels.todo);

const result = findIssueResult(issues, 99);
console.log(result.ok ? result.value.title : result.error);
