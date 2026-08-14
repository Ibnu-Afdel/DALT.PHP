export function normalizeIssueIdentifier(value: string | number): number {
  if (typeof value === 'number') {
    return value;
  }

  return Number(value);
}

export type UserSummary = { id: number; name: string };

export function isUserSummary(value: unknown): value is UserSummary {
  return typeof value === 'object'
    && value !== null
    && 'id' in value
    && 'name' in value
    && typeof value.id === 'number'
    && typeof value.name === 'string';
}

export function describeUnknown(value: unknown): string {
  if (typeof value === 'string') return value.toUpperCase();
  if (typeof value === 'number') return String(value);
  if (value === null) return 'no value';
  if (isUserSummary(value)) return value.name;
  return 'unrecognized';
}

export type IssueSummary = { id: number; title: string };
export type IssueLoadState =
  | { status: 'idle' }
  | { status: 'loading' }
  | { status: 'success'; issues: IssueSummary[] }
  | { status: 'error'; message: string };

export function describeLoadState(state: IssueLoadState): string {
  switch (state.status) {
    case 'idle': return 'Waiting to load issues.';
    case 'loading': return 'Loading issues.';
    case 'success': return `Loaded ${state.issues.length} issues.`;
    case 'error': return `Could not load issues: ${state.message}`;
    default: {
      const exhaustive: never = state;
      return exhaustive;
    }
  }
}
