type RequiredIssue = { id: number; title: string; status: string };

const missingTitle: RequiredIssue = { id: 17, status: 'todo' };

console.log(missingTitle);
