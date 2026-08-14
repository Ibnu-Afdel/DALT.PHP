interface UserSummary {
  id: number;
  name: string;
}

type NestedIssue = { assignee: UserSummary | null };

const wrongId: NestedIssue = { assignee: { id: '7', name: 'Amina' } };
const missingName: NestedIssue = { assignee: { id: 7 } };

console.log(wrongId, missingName);
