import { render, screen } from '@testing-library/react';
import { IssueList } from './IssueList.js';
import { issues } from './issue.js';

// `test` and `expect` are global because vite.config.ts sets test.globals.
// The jest-dom matchers come from src/setup-tests.ts.
test('renders one list item per typed issue', () => {
  render(<IssueList issues={issues} />);

  expect(screen.getByText(/Trace login failure/)).toBeInTheDocument();
  expect(screen.getAllByRole('listitem')).toHaveLength(3);
});

test('renders the empty case without inventing a row', () => {
  render(<IssueList issues={[]} />);

  expect(screen.queryAllByRole('listitem')).toHaveLength(0);
});
