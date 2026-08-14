import type { HasTitle, IssueSummary } from './contracts.js';

type IssueDraft = {
  title: string;
};

interface IssueWithNote extends IssueSummary {
  note: string;
}

const draft: IssueDraft = { title: 'Check emitted JavaScript' };
const issue: IssueWithNote = { id: 17, title: draft.title, note: 'Type syntax disappears.' };

const printTitle = (value: HasTitle): void => {
  console.log(value.title);
};

printTitle(issue);
