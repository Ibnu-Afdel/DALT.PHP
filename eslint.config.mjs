// Learner application linting. Scoped to resources/ so it never reports on the
// framework skeleton, the course platform in .dalt/, or generated build output.
import js from '@eslint/js';
import tseslint from 'typescript-eslint';

export default tseslint.config(
  { ignores: ['public/build/**', 'vendor/**', 'node_modules/**', '.dalt/**'] },
  {
    files: ['resources/**/*.{js,mjs,ts,tsx}'],
    extends: [js.configs.recommended, ...tseslint.configs.recommended],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
    },
  },
);
