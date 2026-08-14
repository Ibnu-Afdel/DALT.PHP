import type { Issue, IssueStatus, IssueUpdate, RequestState } from './model.js';

export function findIssue(issues: readonly Issue[], id: number): Issue | undefined { return issues.find((issue) => issue.id === id); }
export function countByStatus(issues: readonly Issue[]): Record<IssueStatus, number> { return issues.reduce<Record<IssueStatus, number>>((counts, issue) => ({ ...counts, [issue.status]: counts[issue.status] + 1 }), { todo: 0, in_progress: 0, done: 0 }); }
export function applyIssueUpdate(issue: Issue, update: IssueUpdate): Issue { return { ...issue, ...update }; }
export function requestSummary<T>(request: RequestState<T>): string { switch (request.state) { case 'idle': return 'Not started'; case 'loading': return 'Loading'; case 'success': return 'Loaded'; case 'error': return `Failed: ${request.message}`; default: { const exhaustive: never = request; return exhaustive; } } }
