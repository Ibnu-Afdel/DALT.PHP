type Issue = { id: number; title: string };

function brokenFind(issues: Issue[], id: number): Issue | undefined {
  const issue = issues.find((candidate) => candidate.id === id);
  return issue ?? 'not found';
}

void brokenFind;
