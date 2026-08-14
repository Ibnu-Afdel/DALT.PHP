import { invalidFixtureTexts, validIssueJson } from './fixtures.js';
import { parseIssue } from './parser.js';

function expect(condition: boolean, message: string): void {
  if (!condition) throw new Error(message);
}

const validPayload: unknown = JSON.parse(validIssueJson);
const issue = parseIssue(validPayload);
expect(issue.id === 42 && issue.description === null, 'valid issue should be accepted as an Issue');
expect(issue !== validPayload, 'a parser should construct a trusted Issue rather than return the untrusted object');

const secondValidPayload: unknown = {
  id: 7,
  title: 'Write release notes',
  status: 'in_progress',
  description: 'Use the customer examples.',
};
const secondIssue = parseIssue(secondValidPayload);
expect(
  secondIssue.id === 7
    && secondIssue.title === 'Write release notes'
    && secondIssue.status === 'in_progress'
    && secondIssue.description === 'Use the customer examples.',
  'a different valid Issue should be accepted without relying on the fixture identity or text',
);

for (const [name, text] of invalidFixtureTexts) {
  const payload: unknown = JSON.parse(text);
  let rejected = false;
  try {
    parseIssue(payload);
  } catch (error) {
    rejected = error instanceof Error && error.message.length > 0;
  }
  expect(rejected, `${name} should be rejected`);
}

console.log('boundary tests passed: valid issues accepted; malformed fixtures rejected');
