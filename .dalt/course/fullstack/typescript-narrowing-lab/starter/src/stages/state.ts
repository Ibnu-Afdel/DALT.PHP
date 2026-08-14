type IssueSummary = { id: number; title: string };
type IssueLoadState =
  | { status: 'idle' }
  | { status: 'loading' }
  | { status: 'success'; issues: IssueSummary[] }
  | { status: 'error'; message: string };

const missingIssues: IssueLoadState = { status: 'success' };
const missingMessage: IssueLoadState = { status: 'error' };
const contradictory: IssueLoadState = { status: 'loading', issues: [] };

console.log(missingIssues, missingMessage, contradictory);
