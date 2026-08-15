import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ApiProvider } from './ApiContext';
import { ProjectPage } from './ProjectPage';
import { IssueApiError, type IssueApi } from './issueApi';
import type { Issue } from './issue';

const seed: Issue = {
  id: 'ISS-1', projectId: 'PRJ-1', title: 'Search is slow', status: 'todo', priority: 'high',
};

/** Typed with the real interface, so it cannot drift from what the API can return. */
function fakeApi(overrides: Partial<IssueApi> = {}): IssueApi {
  return {
    listIssues: async () => [seed],
    createIssue: async () => { throw new Error('createIssue was not expected in this test'); },
    ...overrides,
  };
}

const renderPage = (api: IssueApi) =>
  render(<ApiProvider api={api}><ProjectPage projectId="PRJ-1" /></ApiProvider>);

afterEach(() => { vi.clearAllMocks(); });

describe('ProjectPage', () => {
  it('renders one row per issue the API returns', async () => {
    renderPage(fakeApi());

    expect(await screen.findByRole('listitem')).toHaveTextContent('Search is slow');
  });

  it('shows the empty state for a successful empty list', async () => {
    renderPage(fakeApi({ listIssues: async () => [] }));

    expect(await screen.findByText(/no issues yet/i)).toBeInTheDocument();
  });

  it('distinguishes a failed request from an empty list', async () => {
    renderPage(fakeApi({
      listIssues: async () => { throw new IssueApiError('Could not reach the server', 'network'); },
    }));

    expect(await screen.findByRole('alert')).toHaveTextContent('Could not reach the server');
    expect(screen.queryByText(/no issues yet/i)).not.toBeInTheDocument();
  });

  it('rejects a whitespace-only title without calling the API', async () => {
    const user = userEvent.setup();
    const createIssue = vi.fn();
    renderPage(fakeApi({ createIssue }));
    await screen.findByRole('listitem');

    await user.type(screen.getByLabelText(/title/i), '   ');
    await user.click(screen.getByRole('button', { name: /create issue/i }));

    expect(await screen.findByRole('alert')).toHaveTextContent('title is required');
    expect(createIssue).not.toHaveBeenCalled();
    expect(screen.getByLabelText(/title/i)).toHaveValue('   ');
  });

  it('sends the chosen priority and appends the issue the server returned', async () => {
    const user = userEvent.setup();
    const createIssue = vi.fn(async (draft: Parameters<IssueApi['createIssue']>[0]): Promise<Issue> => ({
      ...draft, id: 'ISS-2', status: 'todo',
    }));
    renderPage(fakeApi({ createIssue }));
    await screen.findByRole('listitem');

    await user.type(screen.getByLabelText(/title/i), 'Login form loses focus');
    await user.selectOptions(screen.getByLabelText(/priority/i), 'low');
    await user.click(screen.getByRole('button', { name: /create issue/i }));

    expect(await screen.findAllByRole('listitem')).toHaveLength(2);
    expect(createIssue).toHaveBeenCalledWith({
      title: 'Login form loses focus', priority: 'low', projectId: 'PRJ-1',
    });
  });

  it('keeps the draft on screen when the server rejects it', async () => {
    const user = userEvent.setup();
    renderPage(fakeApi({
      createIssue: async () => { throw new IssueApiError('title is already taken', 'validation'); },
    }));
    await screen.findByRole('listitem');

    await user.type(screen.getByLabelText(/title/i), 'Search is slow');
    await user.click(screen.getByRole('button', { name: /create issue/i }));

    expect(await screen.findByRole('alert')).toHaveTextContent('title is already taken');
    expect(screen.getByLabelText(/title/i)).toHaveValue('Search is slow');
  });
});
