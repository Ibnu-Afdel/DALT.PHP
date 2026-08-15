import { IssueList } from './IssueList.js';
import { issues } from './issue.js';

// The screen you grow across FS03.1 -> FS03.4.
// FS03.1 gives it a component boundary, FS03.2 adds state and events,
// FS03.3 adds a real form, FS03.4 makes it semantic, responsive and keyboard-usable.
export function App() {
  return (
    <main>
      <h1>Platform / Issue tracker</h1>
      <IssueList issues={issues} />
    </main>
  );
}
