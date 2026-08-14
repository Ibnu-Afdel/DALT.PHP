import type { IssuePreview } from './preview.js';
import type { IssueStatus } from './model.js';

function isRecord(value: unknown): value is Record<string, unknown> { return typeof value === 'object' && value !== null && !Array.isArray(value); }
function isIssueStatus(value: unknown): value is IssueStatus { return value === 'todo' || value === 'in_progress' || value === 'done'; }

export function parseIssuePreview(value: unknown): IssuePreview {
  void isRecord; void isIssueStatus; void value;
  throw new Error('TODO: establish runtime evidence, then construct a trusted preview.');
}
