import type { IssueStatus } from './model.js';
export type IssuePreview = { readonly id: number; title: string; status: IssueStatus; description: string | null; assignee: { readonly id: number; name: string } | null };
