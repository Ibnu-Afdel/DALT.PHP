import type { CreateIssueInput, Issue, Project, RequestState, UserSummary } from './model.js';
import { applyIssueUpdate, countByStatus, findIssue, requestSummary } from './operations.js';

const project: Project = { id: 7, name: 'Mobile release' };
const ada: UserSummary = { id: 1, name: 'Ada' };
const draft: CreateIssueInput = { projectId: project.id, title: 'Fix search', priority: 'high' };
const issues: Issue[] = [{ id: 42, ...draft, description: null, status: 'todo', assigneeId: ada.id }];
const updated = applyIssueUpdate(issues[0], { description: 'Reproduce before changing ranking.' });
const state: RequestState<Issue[]> = { state: 'success', data: [updated] };
console.log(findIssue(issues, 42)?.title, countByStatus(issues), requestSummary(state));
