export const validIssueJson = JSON.stringify({
  id: 42,
  title: 'Broken search',
  status: 'todo',
  description: null,
});

export const malformedIssueJson = JSON.stringify({
  id: '42',
  title: null,
  status: 'banana',
  description: 123,
});

export const invalidFixtureTexts = [
  ['string id', JSON.stringify({ id: '42', title: 'Broken search', status: 'done', description: null })],
  ['null title', JSON.stringify({ id: 42, title: null, status: 'done', description: null })],
  ['unknown status', JSON.stringify({ id: 42, title: 'Broken search', status: 'banana', description: null })],
  ['missing title', JSON.stringify({ id: 42, status: 'done', description: null })],
  ['array instead of object', JSON.stringify([])],
  ['null instead of object', 'null'],
  ['number description', JSON.stringify({ id: 42, title: 'Broken search', status: 'done', description: 123 })],
] as const;
