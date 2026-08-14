type OptionalDescription = { description?: string };
type NullableAssignee = { assignee: { id: number; name: string } | null };

const omitted: OptionalDescription = {};
const written: OptionalDescription = { description: 'Explain the failure state.' };
const explicitlyUndefined: OptionalDescription = { description: undefined };
const nullDescription: OptionalDescription = { description: null };
const noAssignee: NullableAssignee = { assignee: null };

console.log(omitted, written, explicitlyUndefined, nullDescription, noAssignee);
