type UserSummary = { id: number; name: string };

function isUserSummary(value: unknown): value is UserSummary {
  return typeof value === 'object'
    && value !== null
    && 'id' in value
    && 'name' in value
    && typeof value.id === 'number'
    && typeof value.name === 'string';
}

console.log(isUserSummary({ id: 7, name: 'Amina' }));
console.log(isUserSummary({ id: '7', name: 'Amina' }));
