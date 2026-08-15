import { parseIssue, type Issue, type IssueDraft } from './issue';

/**
 * The seam. Every operation the UI needs, and nothing about HTTP in its signature.
 * A fake in a test satisfies this same type, which is what makes the fake trustworthy.
 */
export type IssueApi = {
  listIssues(projectId: string): Promise<Issue[]>;
  createIssue(draft: IssueDraft): Promise<Issue>;
};

export class IssueApiError extends Error {
  constructor(message: string, readonly kind: 'network' | 'validation' | 'http') {
    super(message);
    this.name = 'IssueApiError';
  }
}

async function request(path: string, init?: RequestInit): Promise<unknown> {
  let response: Response;
  try {
    response = await fetch(path, { headers: { 'Content-Type': 'application/json' }, ...init });
  } catch (cause) {
    throw new IssueApiError(`Could not reach the server: ${String(cause)}`, 'network');
  }
  if (response.status === 422) {
    const body = (await response.json()) as { error?: { message?: string } };
    throw new IssueApiError(body.error?.message ?? 'The server rejected this issue', 'validation');
  }
  if (!response.ok) {
    throw new IssueApiError(`The server answered ${response.status}`, 'http');
  }
  return response.json();
}

export const issueApi: IssueApi = {
  async listIssues(projectId) {
    const body = await request(`/api/projects/${projectId}/issues`);
    if (!Array.isArray(body)) throw new IssueApiError('Expected a list of issues', 'http');
    return body.map(parseIssue);
  },
  async createIssue(draft) {
    const body = await request('/api/issues', { method: 'POST', body: JSON.stringify(draft) });
    return parseIssue(body);
  },
};
