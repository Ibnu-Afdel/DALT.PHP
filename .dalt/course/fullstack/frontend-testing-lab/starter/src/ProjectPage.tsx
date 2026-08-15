import { useEffect, useState } from 'react';
import { CreateIssueForm } from './CreateIssueForm';
import { IssueList } from './IssueList';
import type { Issue } from './issue';
// STAGE 1 — this direct import is the defect you are here to remove.
// It hard-wires the screen to the real HTTP client, so no test can substitute
// anything for it. Replace it with the seam in ./ApiContext.
import { issueApi } from './issueApi';
import { IssueApiError } from './issueApi';

type Load =
  | { status: 'loading' }
  | { status: 'ready'; issues: Issue[] }
  | { status: 'failed'; message: string };

export function ProjectPage({ projectId }: { projectId: string }) {
  // STAGE 1 — should be: const api = useIssueApi();
  const api = issueApi;
  const [load, setLoad] = useState<Load>({ status: 'loading' });

  useEffect(() => {
    let live = true;
    api
      .listIssues(projectId)
      .then((issues) => live && setLoad({ status: 'ready', issues }))
      .catch((error: unknown) =>
        live && setLoad({ status: 'failed', message: error instanceof Error ? error.message : 'Unknown failure' }),
      );
    return () => { live = false; };
  }, [api, projectId]);

  async function createIssue(draft: { title: string; priority: Issue['priority'] }): Promise<string | null> {
    if (draft.title.trim() === '') return 'title is required';
    try {
      const created = await api.createIssue({ ...draft, projectId });
      setLoad((current) =>
        current.status === 'ready' ? { status: 'ready', issues: [...current.issues, created] } : current,
      );
      return null;
    } catch (error: unknown) {
      if (error instanceof IssueApiError) return error.message;
      return 'Could not create the issue';
    }
  }

  return (
    <main>
      <h1>Project {projectId}</h1>
      <CreateIssueForm onSubmitDraft={createIssue} />
      {load.status === 'loading' && <p>Loading issues…</p>}
      {load.status === 'failed' && <p role="alert">{load.message}</p>}
      {load.status === 'ready' && <IssueList issues={load.issues} />}
    </main>
  );
}
