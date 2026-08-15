import { createContext, useContext, type ReactNode } from 'react';
import { issueApi, type IssueApi } from './issueApi';

/**
 * Default value is the real client, so production code renders without a wrapper.
 * A test wraps in <ApiProvider api={fake}> and the subtree sees the fake instead.
 */
const ApiContext = createContext<IssueApi>(issueApi);

export const useIssueApi = (): IssueApi => useContext(ApiContext);

export function ApiProvider({ api, children }: { api: IssueApi; children: ReactNode }) {
  return <ApiContext.Provider value={api}>{children}</ApiContext.Provider>;
}
