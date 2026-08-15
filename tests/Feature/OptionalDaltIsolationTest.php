<?php

declare(strict_types=1);

test('the root manifests do not own DALT-only dependencies', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require'])->not->toHaveKey('league/commonmark')
        ->and($composer['require'])->not->toHaveKey('scrivo/highlight.php')
        ->and($composer['autoload']['psr-4']['Core\\'])->toBe('framework/Core/')
        ->and($package['dependencies'] ?? [])->not->toHaveKey('vue')
        ->and($package['dependencies'] ?? [])->not->toHaveKey('reka-ui')
        ->and($package['dependencies'] ?? [])->not->toHaveKey('shadcn-vue')
        ->and($package['dependencies'] ?? [])->not->toHaveKey('lucide-vue-next');
});

// IMPLEMENTATION_PLAN.md 4.10 item 1. The course shell is Vue; the learner's
// application is React. Both manifests exist, and the whole cleanliness argument
// depends on them never merging. The Vue-side guard above has existed since the
// course platform was extracted; this is the same guard facing the other way,
// added when the React toolchain landed in the root manifest under 5.3 Option A.
//
// This test is skipped without .dalt rather than reading it unconditionally. That is
// the whole invariant it defends: nothing in root tests/ may depend on a course
// artifact. As first written it did exactly what it forbids — after
// `php artisan platform:remove`, file_get_contents returned false and json_decode
// threw, so the framework suite failed on a skeleton. Nothing caught it, because
// .dalt is always present in development; only actually removing it shows the bug.
test('the course shell manifest does not own the learner application toolchain', function () {
    $package = json_decode((string) file_get_contents(base_path('.dalt/package.json')), true, flags: JSON_THROW_ON_ERROR);
    $owned = [...array_keys($package['dependencies'] ?? []), ...array_keys($package['devDependencies'] ?? [])];

    foreach (['react', 'react-dom', '@vitejs/plugin-react', 'typescript', 'vitest', '@testing-library/react', '@testing-library/jest-dom', 'jsdom', 'eslint', 'typescript-eslint'] as $forbidden) {
        expect(in_array($forbidden, $owned, true))->toBeFalse(
            "'{$forbidden}' belongs to the learner application's root package.json, not to the course shell. "
            . 'Keeping the two manifests separate is what lets platform:remove delete .dalt and leave a working project.',
        );
    }
})->skip(
    !is_file(base_path('.dalt/package.json')),
    'Guided learning is not installed; there is no course shell manifest to keep separate.',
);

// 5.3 Option A resolved that the root manifest is the learner-application manifest
// and may own React. The cost of that decision is that the manifest must actually
// work: dependencies with no config to consume them are shipped weight that also
// fail the first learner who tries to use them in B03.
test('the learner application toolchain is wired, not merely declared', function () {
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
    $scripts = $package['scripts'] ?? [];
    $dev = $package['devDependencies'] ?? [];

    expect($package['dependencies'] ?? [])->toHaveKey('react')
        ->and($dev)->toHaveKey('@vitejs/plugin-react')
        ->and($scripts)->toHaveKeys(['typecheck', 'lint', 'test', 'build', 'dev']);

    expect(is_file(base_path('tsconfig.json')))
        ->toBeTrue('React and TypeScript are declared in package.json but there is no tsconfig.json for `npm run typecheck` to use.');
    expect(is_file(base_path('eslint.config.mjs')))
        ->toBeTrue('ESLint is declared in package.json but there is no flat config for `npm run lint` to use.');

    // str_contains rather than toContain(): Pest's toContain() is variadic over
    // needles and would read a failure message as a second thing to look for.
    $vite = (string) file_get_contents(base_path('vite.config.mjs'));
    expect(str_contains($vite, '@vitejs/plugin-react'))
        ->toBeTrue('Vite cannot compile the .tsx the curriculum asks the learner to write without the React plugin.');
    expect(str_contains($vite, 'jsdom'))
        ->toBeTrue('Vitest needs a DOM environment to run the React Testing Library tests the curriculum specifies.');
});

// B03 Stage 4 tells the learner to port the Part 03 lab's tests into resources/, and
// B03 Stage 1 promises they will not have to "add a test runner" to do it. Performing
// B03 showed that promise was false in two ways at once, and both were invisible to
// every existing check:
//
//   1. The lab registers the jest-dom matchers through its own vite config setupFiles.
//      The root config had none, so a ported `toBeInTheDocument()` died with
//      "Invalid Chai property" — while `npm run typecheck` stayed green, because
//      tsconfig.json lists the matchers under `types`.
//   2. FS03.3 tells the learner "@testing-library/user-event is already installed".
//      True in the lab, false at the root, and B03's required tests ("a whitespace-only
//      title is rejected", "a valid submit adds exactly one row") are exactly the ones
//      that need it.
//
// The test above proves the toolchain compiles and lints. This one proves it can run
// the tests the curriculum actually asks for.
test('the learner application toolchain can run the tests B03 asks the learner to port', function () {
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
    $dev = $package['devDependencies'] ?? [];

    // array_key_exists rather than toHaveKey(): toHaveKey's second argument is the
    // expected value, not a failure message, so a message there asserts the wrong thing.
    expect(array_key_exists('@testing-library/user-event', $dev))->toBeTrue(
        'FS03.3 tells the learner user-event is already installed and B03 Stage 4 has them port those tests here.',
    );
    expect(is_file(base_path('resources/setup-tests.ts')))
        ->toBeTrue('The jest-dom matchers need a setup file for vite.config.mjs to register.');

    $vite = (string) file_get_contents(base_path('vite.config.mjs'));
    expect(str_contains($vite, 'setupFiles'))
        ->toBeTrue(
            'vite.config.mjs registers no setupFiles, so the jest-dom matchers are not installed at runtime. '
            . 'Ported tests fail with "Invalid Chai property: toBeInTheDocument" while typecheck stays green.',
        );
    expect(str_contains($vite, 'resources/setup-tests.ts'))
        ->toBeTrue('vite.config.mjs sets setupFiles but not to the file that registers the matchers.');
});

// Every version the curriculum teaches against is pinned exactly under CR-08. A
// caret here means a learner three months from now silently gets a different
// toolchain from the one the lessons describe.
test('the learner application toolchain is pinned exactly, per CR-08', function () {
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach (['react', 'react-dom', '@vitejs/plugin-react', 'typescript', 'vitest', '@testing-library/react', '@testing-library/user-event', 'jsdom'] as $pinned) {
        $constraint = $package['dependencies'][$pinned] ?? $package['devDependencies'][$pinned] ?? null;
        expect($constraint)->not->toBeNull("'{$pinned}' is part of the CR-08 toolchain and is missing from package.json.");
        expect($constraint)->toMatch(
            '/^\d+\.\d+\.\d+$/',
            "'{$pinned}' is '{$constraint}'. CR-08 pins the curriculum's toolchain exactly; a range lets a learner's install drift away from what the lessons describe.",
        );
    }
});
