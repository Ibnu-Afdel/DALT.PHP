function labelCount(value: number | undefined): string {
  if (value) return `Count: ${value}`;
  return 'No count';
}

console.log(labelCount(0));
