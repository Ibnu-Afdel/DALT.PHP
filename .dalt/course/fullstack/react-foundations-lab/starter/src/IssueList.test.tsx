import { render, screen } from '@testing-library/react';
import { IssueList } from './IssueList.js';
import { issues } from './issue.js';
test('renders each typed issue', () => { render(<IssueList issues={issues} />); expect(screen.getByText(/Trace login failure/)).toBeTruthy(); expect(screen.getAllByRole('listitem')).toHaveLength(3); });
