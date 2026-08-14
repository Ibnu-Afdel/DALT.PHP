type StoredIssue = { readonly id: number; title: string };

const issue: StoredIssue = { id: 17, title: 'Broken search' };
issue.id = 99;

console.log(issue);
