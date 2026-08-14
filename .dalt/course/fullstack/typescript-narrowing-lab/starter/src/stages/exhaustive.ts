type IssueSummary = { id: number; title: string };
type IssueLoadState =
  | { status: 'idle' }
  | { status: 'loading' }
  | { status: 'success'; issues: IssueSummary[] }
  | { status: 'error'; message: string }
  | { status: 'refreshing'; issues: IssueSummary[] };

function describe(state: IssueLoadState): string {
  switch (state.status) {
    case 'idle': return 'idle';
    case 'loading': return 'loading';
    case 'success': return String(state.issues.length);
    case 'error': return state.message;
    default: {
      const exhaustive: never = state;
      return exhaustive;
    }
  }
}

console.log(describe);
