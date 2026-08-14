for (const value of [null, [], { id: 42 }]) {
  console.log(JSON.stringify(value), '→ typeof:', typeof value, 'array:', Array.isArray(value));
}
