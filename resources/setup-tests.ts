// Adds the jest-dom matchers (toBeInTheDocument, toBeDisabled, toHaveTextContent,
// toHaveValue, ...) to expect(). Registered through vite.config.mjs -> test.setupFiles,
// so every application test file gets them without importing anything.
//
// This file is toolchain, not application code. B03 Stage 1 promises the learner they
// will not have to "add a test runner", and B03 Stage 4 then tells them to port the
// Part 03 lab's tests into resources/. Those tests use these matchers, and the lab
// registered them through its own vite config. Without this file they port a green
// typecheck into a red test run: tsconfig.json lists @testing-library/jest-dom under
// `types`, so the compiler knows the matchers exist while the runtime does not.
import '@testing-library/jest-dom/vitest';
