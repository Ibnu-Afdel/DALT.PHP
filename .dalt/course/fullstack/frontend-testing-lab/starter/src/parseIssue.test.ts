import { parseIssue } from './issue';

// The cheapest level. No React, no server, no browser — and it already passes.
// Level 1 of the pyramid in FS07.3: push each claim to the cheapest place that can prove it.
describe('parseIssue', () => {
  const valid = { id: 'ISS-1', projectId: 'PRJ-1', title: 'Search is slow', status: 'todo', priority: 'high' };

  it('accepts a well-formed issue', () => {
    expect(parseIssue(valid)).toEqual(valid);
  });

  it('rejects an unknown status rather than passing it to the UI', () => {
    expect(() => parseIssue({ ...valid, status: 'archived' })).toThrow(/not a known status/);
  });

  it('rejects a missing title', () => {
    expect(() => parseIssue({ ...valid, title: undefined })).toThrow(/title must be a string/);
  });

  it('rejects a non-object', () => {
    expect(() => parseIssue(null)).toThrow(/must be an object/);
  });
});
