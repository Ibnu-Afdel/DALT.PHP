import { describeLoadState, describeUnknown, normalizeIssueIdentifier } from './narrowing.js';

console.log(normalizeIssueIdentifier('17'));
console.log(describeUnknown({ id: 7, name: 'Amina' }));
console.log(describeLoadState({ status: 'success', issues: [{ id: 17, title: 'Broken search' }] }));
