import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { ProjectPage } from './ProjectPage';

createRoot(document.getElementById('root')!).render(
  <StrictMode><ProjectPage projectId="PRJ-1" /></StrictMode>,
);
