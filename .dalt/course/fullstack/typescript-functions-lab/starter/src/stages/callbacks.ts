type Issue = { id: number; title: string; priority: 'low' | 'high' };
type IssuePredicate = (issue: Issue) => boolean;

declare function filterIssues(issues: Issue[], predicate: IssuePredicate): Issue[];
declare const issues: Issue[];

filterIssues(issues, (issue) => issue.priority === 'high');
filterIssues(issues, (id: number) => id > 10);
filterIssues(issues, (issue) => issue.title);
