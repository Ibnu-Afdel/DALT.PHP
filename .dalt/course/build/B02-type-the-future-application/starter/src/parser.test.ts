import { parseIssuePreview } from './parser.js';
function expect(condition: boolean, message: string): void { if (!condition) throw new Error(message); }
const valid: unknown = { id: 42, title: 'Fix search', status: 'todo', description: null, assignee: { id: 1, name: 'Ada' } };
const parsed = parseIssuePreview(valid);
expect(parsed !== valid && parsed.assignee?.name === 'Ada', 'valid data must be reconstructed into a trusted preview');
for (const malformed of [{ id: '42', title: 'Fix', status: 'todo', description: null, assignee: null }, { id: 42, title: 'Fix', status: 'blocked', description: null, assignee: null }, { id: 42, title: 'Fix', status: 'todo', description: null, assignee: { id: 1, name: null } }]) { let rejected = false; try { parseIssuePreview(malformed); } catch { rejected = true; } expect(rejected, 'malformed preview must be rejected'); }
console.log('parser proof passed');
