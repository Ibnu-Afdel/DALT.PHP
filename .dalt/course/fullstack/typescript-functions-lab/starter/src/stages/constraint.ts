function brokenFindById<T>(items: T[], id: number): T | undefined {
  return items.find((item) => item.id === id);
}

void brokenFindById;
