import type { IssueSummary } from './contracts.js';

export const formatIssueSummary = (issue: IssueSummary): string =>
  `#${issue.id} ${issue.title.trim()}`;

const triageIssue = {
  id: 17,
  title: 'Broken search',
  status: 'open',
  priority: 'high',
};

const inferredTitle = triageIssue.title;
const summary = formatIssueSummary(triageIssue);

// Change pressure: the issue tracker now uses visible keys such as "ISS-19".
// Read the diagnostic before deciding whether this caller or the contract is wrong.
const importedSummary = formatIssueSummary({ id: 'ISS-19', title: 'Refresh docs' });

console.log(inferredTitle, summary, importedSummary);
