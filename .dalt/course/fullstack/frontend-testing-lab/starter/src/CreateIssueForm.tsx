import { useState } from 'react';
import type { IssuePriority } from './issue';

type Props = {
  onSubmitDraft: (draft: { title: string; priority: IssuePriority }) => Promise<string | null>;
};

/** Owns only its draft. The submit behaviour arrives as a prop, so a test can supply one. */
export function CreateIssueForm({ onSubmitDraft }: Props) {
  const [title, setTitle] = useState('');
  const [priority, setPriority] = useState<IssuePriority>('medium');
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState(false);

  async function submit(event: React.FormEvent) {
    event.preventDefault();
    setPending(true);
    const failure = await onSubmitDraft({ title, priority });
    setPending(false);
    setError(failure);
    if (failure === null) setTitle('');
  }

  return (
    <form aria-label="Create issue" onSubmit={submit}>
      <label htmlFor="title">Title</label>
      <input id="title" value={title} onChange={(e) => setTitle(e.target.value)} />

      <label htmlFor="priority">Priority</label>
      <select id="priority" value={priority} onChange={(e) => setPriority(e.target.value as IssuePriority)}>
        <option value="low">low</option>
        <option value="medium">medium</option>
        <option value="high">high</option>
      </select>

      <button type="submit" disabled={pending}>{pending ? 'Creating…' : 'Create issue'}</button>
      {error !== null && <p role="alert">{error}</p>}
    </form>
  );
}
