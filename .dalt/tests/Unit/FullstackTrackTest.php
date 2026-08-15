<?php

declare(strict_types=1);

use Core\CourseLoader;
use Core\CourseMetadataException;
use Core\FullstackTrack;

function fullstackTrackFixture(): string
{
    $root = sys_get_temp_dir() . '/dalt-fullstack-' . bin2hex(random_bytes(6));
    mkdir($root . '/lessons/first-fullstack-lesson', 0700, true);
    mkdir($root . '/challenges', 0700, true);
    file_put_contents($root . '/lessons/first-fullstack-lesson/README.md', '# Lesson');
    file_put_contents($root . '/lessons/first-fullstack-lesson/meta.json', json_encode([
        'title' => 'First Fullstack Lesson', 'description' => 'A fixture lesson.', 'order' => 1,
        'section' => 'fullstack', 'section_order' => 1, 'icon' => 'layers', 'color' => 'purple', 'prerequisites' => [],
    ], JSON_THROW_ON_ERROR));

    return $root;
}

function fullstackTrackManifest(string $lessonId = 'first-fullstack-lesson'): string
{
    $parts = [];
    foreach (range(0, 12) as $number) {
        $key = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
        $parts[$key] = ['title' => "Part {$key}", 'purpose' => 'A clear purpose.', 'lessons' => $number === 0 ? [$lessonId] : [], 'milestones' => [['id' => $number === 12 ? 'C01' : 'B' . $key, 'title' => 'A milestone']]];
    }
    return '<?php return ' . var_export(['title' => 'DALT Fullstack', 'description' => 'A separate journey.', 'parts' => $parts], true) . ';';
}

function removeFullstackTrackFixture(string $path): void
{
    if (is_dir($path)) {
        foreach (new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS) as $entry) {
            removeFullstackTrackFixture($entry->getPathname());
        }
        rmdir($path);
    } elseif (file_exists($path)) {
        unlink($path);
    }
}

test('the Fullstack manifest describes all planned parts and only real Fullstack lessons', function () {
    $track = FullstackTrack::load();
    $lessons = CourseLoader::getLessons();
    $fullstackLessons = array_values(array_filter($lessons, fn (array $lesson): bool => $lesson['section'] === 'fullstack'));

    expect(array_map(static fn (string|int $part): int => (int) $part, array_keys($track['parts'])))->toBe(range(0, 12))
        ->and($track['parts']['00']['lessons'])->toBe(['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa'])
        ->and($track['parts']['00']['milestones'][0])->toMatchArray([
            'id' => 'B00',
            'route' => '/learn/fullstack/build/b00',
            'prerequisites' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa'],
        ])
        ->and($track['parts']['01']['lessons'])->toBe(['22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure'])
        ->and($track['parts']['01']['milestones'][0])->toMatchArray([
            'id' => 'B01',
            'route' => '/learn/fullstack/build/b01',
            'prerequisites' => ['22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure'],
        ])
        ->and($track['parts']['02']['lessons'])->toBe(['24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types', '28-fs02-5-runtime-boundaries'])
        ->and($track['parts']['02']['milestones'][0])->toMatchArray(['id' => 'B02', 'route' => '/learn/fullstack/build/b02', 'prerequisites' => ['24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types', '28-fs02-5-runtime-boundaries']])
        ->and($track['parts']['03']['lessons'])->toBe(['29-fs03-1-components-jsx-and-typed-props', '30-fs03-2-state-and-events', '31-fs03-3-forms-and-state-design', '32-fs03-4-tailwind-and-accessible-ui'])
        ->and($track['parts']['04']['lessons'])->toBe(['33-fs04-1-fetching-data-and-effects', '34-fs04-2-mutating-server-data', '35-fs04-3-separating-transport-from-ui'])
        ->and($track['parts']['04']['milestones'][0])->toMatchArray(['id' => 'B04', 'route' => '/learn/fullstack/build/b04', 'prerequisites' => ['33-fs04-1-fetching-data-and-effects', '34-fs04-2-mutating-server-data', '35-fs04-3-separating-transport-from-ui']])
        ->and($track['parts']['06']['lessons'])->toBe(['39-fs06-1-backend-api-behavior-tests', '40-fs06-2-users-passwords-sessions-and-csrf', '41-fs06-3-authorization-and-ownership'])
        ->and($track['parts']['06']['milestones'][0])->toMatchArray(['id' => 'B06', 'route' => '/learn/fullstack/build/b06', 'prerequisites' => ['39-fs06-1-backend-api-behavior-tests', '40-fs06-2-users-passwords-sessions-and-csrf', '41-fs06-3-authorization-and-ownership']])
        ->and($track['parts']['07']['lessons'])->toBe(['42-fs07-1-urls-and-react-router', '43-fs07-2-authentication-in-the-frontend', '44-fs07-3-test-frontend-behavior'])
        ->and($track['parts']['07']['milestones'][0])->toMatchArray(['id' => 'B07', 'route' => '/learn/fullstack/build/b07', 'prerequisites' => ['42-fs07-1-urls-and-react-router', '43-fs07-2-authentication-in-the-frontend', '44-fs07-3-test-frontend-behavior']])
        ->and($track['parts']['08']['lessons'])->toBe(['45-fs08-1-client-state-versus-server-state', '46-fs08-2-mutations-invalidation-and-optimistic-ui', '47-fs08-3-context-reducers-and-zustand'])
        ->and($track['parts']['08']['milestones'][0])->toMatchArray(['id' => 'B08', 'route' => '/learn/fullstack/build/b08', 'prerequisites' => ['45-fs08-1-client-state-versus-server-state', '46-fs08-2-mutations-invalidation-and-optimistic-ui', '47-fs08-3-context-reducers-and-zustand']])
        ->and($track['parts']['09']['lessons'])->toBe(['48-fs09-1-custom-hooks-and-feature-boundaries', '49-fs09-2-build-pipeline-configuration-and-failure-boundaries'])
        ->and($track['parts']['09']['milestones'][0])->toMatchArray(['id' => 'B09', 'route' => '/learn/fullstack/build/b09', 'prerequisites' => ['48-fs09-1-custom-hooks-and-feature-boundaries', '49-fs09-2-build-pipeline-configuration-and-failure-boundaries']])
        ->and($track['parts']['10']['milestones'][0])->toMatchArray(['id' => 'B10', 'route' => '/learn/fullstack/build/b10', 'prerequisites' => ['50-fs10-1-containers-around-the-application', '51-fs10-2-builds-health-and-debugging']])
        ->and($track['parts']['11']['lessons'])->toBe(['52-fs11-1-query-performance-and-postgresql-capabilities', '53-fs11-2-transactions-concurrency-and-row-isolation'])
        ->and($track['parts']['11']['milestones'][0])->toMatchArray(['id' => 'B11', 'route' => '/learn/fullstack/build/b11', 'prerequisites' => ['52-fs11-1-query-performance-and-postgresql-capabilities', '53-fs11-2-transactions-concurrency-and-row-isolation']])
        ->and($track['parts']['12']['milestones'])->toHaveCount(7)
        ->and(array_column($fullstackLessons, 'id'))->toBe(['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa', '22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure', '24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types', '28-fs02-5-runtime-boundaries', '29-fs03-1-components-jsx-and-typed-props', '30-fs03-2-state-and-events', '31-fs03-3-forms-and-state-design', '32-fs03-4-tailwind-and-accessible-ui', '33-fs04-1-fetching-data-and-effects', '34-fs04-2-mutating-server-data', '35-fs04-3-separating-transport-from-ui', '36-fs05-1-designing-the-application-api', '37-fs05-2-relational-modeling-and-migrations', '38-fs05-3-crud-queries-and-transaction-boundaries', '39-fs06-1-backend-api-behavior-tests', '40-fs06-2-users-passwords-sessions-and-csrf', '41-fs06-3-authorization-and-ownership', '42-fs07-1-urls-and-react-router', '43-fs07-2-authentication-in-the-frontend', '44-fs07-3-test-frontend-behavior', '45-fs08-1-client-state-versus-server-state', '46-fs08-2-mutations-invalidation-and-optimistic-ui', '47-fs08-3-context-reducers-and-zustand', '48-fs09-1-custom-hooks-and-feature-boundaries', '49-fs09-2-build-pipeline-configuration-and-failure-boundaries', '50-fs10-1-containers-around-the-application', '51-fs10-2-builds-health-and-debugging', '52-fs11-1-query-performance-and-postgresql-capabilities', '53-fs11-2-transactions-concurrency-and-row-isolation'])
        ->and($fullstackLessons[0]['prerequisites'])->toBe([])
        ->and($fullstackLessons[1]['prerequisites'])->toBe(['20-fs00-1-browser-and-http'])
        ->and($fullstackLessons[2]['prerequisites'])->toBe(['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa'])
        ->and($fullstackLessons[3]['prerequisites'])->toBe(['22-fs01-1-data-functions-transformations'])
        ->and($fullstackLessons[4]['prerequisites'])->toBe(['23-fs01-2-modules-async-and-failure'])
        ->and($fullstackLessons[5]['prerequisites'])->toBe(['24-fs02-1-typescript-mental-model'])
        ->and($fullstackLessons[6]['prerequisites'])->toBe(['25-fs02-2-modeling-application-data'])
        ->and($fullstackLessons[7]['prerequisites'])->toBe(['26-fs02-3-unions-narrowing-and-unknown'])
        ->and($fullstackLessons[8]['prerequisites'])->toBe(['27-fs02-4-functions-generics-and-reusable-types'])
        ->and($fullstackLessons[9]['prerequisites'])->toBe(['28-fs02-5-runtime-boundaries'])
        ->and($fullstackLessons[10]['prerequisites'])->toBe(['29-fs03-1-components-jsx-and-typed-props'])
        ->and($fullstackLessons[11]['prerequisites'])->toBe(['30-fs03-2-state-and-events'])
        ->and($fullstackLessons[12]['prerequisites'])->toBe(['31-fs03-3-forms-and-state-design'])
        ->and($fullstackLessons[13]['prerequisites'])->toBe(['32-fs03-4-tailwind-and-accessible-ui'])
        ->and($fullstackLessons[14]['prerequisites'])->toBe(['33-fs04-1-fetching-data-and-effects'])
        ->and($fullstackLessons[15]['prerequisites'])->toBe(['34-fs04-2-mutating-server-data'])
        ->and($fullstackLessons[16]['prerequisites'])->toBe(['35-fs04-3-separating-transport-from-ui'])
        ->and($fullstackLessons[17]['prerequisites'])->toBe(['36-fs05-1-designing-the-application-api'])
        ->and($fullstackLessons[18]['prerequisites'])->toBe(['37-fs05-2-relational-modeling-and-migrations'])
        ->and($fullstackLessons[19]['prerequisites'])->toBe(['38-fs05-3-crud-queries-and-transaction-boundaries'])
        ->and($fullstackLessons[20]['prerequisites'])->toBe(['39-fs06-1-backend-api-behavior-tests'])
        ->and($fullstackLessons[21]['prerequisites'])->toBe(['40-fs06-2-users-passwords-sessions-and-csrf'])
        ->and($fullstackLessons[22]['prerequisites'])->toBe(['41-fs06-3-authorization-and-ownership'])
        ->and($fullstackLessons[23]['prerequisites'])->toBe(['42-fs07-1-urls-and-react-router'])
        ->and($fullstackLessons[24]['prerequisites'])->toBe(['43-fs07-2-authentication-in-the-frontend'])
        ->and($fullstackLessons[25]['prerequisites'])->toBe(['44-fs07-3-test-frontend-behavior'])
        ->and($fullstackLessons[26]['prerequisites'])->toBe(['45-fs08-1-client-state-versus-server-state'])
        ->and($fullstackLessons[27]['prerequisites'])->toBe(['46-fs08-2-mutations-invalidation-and-optimistic-ui'])
        ->and($fullstackLessons[28]['prerequisites'])->toBe(['47-fs08-3-context-reducers-and-zustand'])
        ->and($fullstackLessons[29]['prerequisites'])->toBe(['48-fs09-1-custom-hooks-and-feature-boundaries'])
        ->and($fullstackLessons[30]['prerequisites'])->toBe(['49-fs09-2-build-pipeline-configuration-and-failure-boundaries'])
        ->and($fullstackLessons[31]['prerequisites'])->toBe(['50-fs10-1-containers-around-the-application'])
        ->and($fullstackLessons[32]['prerequisites'])->toBe(['51-fs10-2-builds-health-and-debugging'])
        ->and($fullstackLessons[33]['prerequisites'])->toBe(['52-fs11-1-query-performance-and-postgresql-capabilities']);
});

test('the FS02.1 lab is course-owned, resettable, and keeps generated learner work out of the repository', function () {
    $starter = base_path('.dalt/course/fullstack/typescript-lab/starter');

    expect(is_file($starter . '/package.json'))->toBeTrue()
        ->and(is_file($starter . '/package-lock.json'))->toBeTrue()
        ->and(is_file($starter . '/tsconfig.json'))->toBeTrue()
        ->and(is_file($starter . '/src/runtime-failure.mjs'))->toBeTrue()
        ->and(is_file($starter . '/src/issue-summary.ts'))->toBeTrue()
        ->and(file_get_contents(base_path('.gitignore')))->toContain('.dalt/workspace/');
});

test('the FS02.2 lab is course-owned, pinned, resettable, and keeps generated learner work out of the repository', function () {
    $starter = base_path('.dalt/course/fullstack/typescript-modeling-lab/starter');

    expect(is_file($starter . '/package.json'))->toBeTrue()
        ->and(is_file($starter . '/package-lock.json'))->toBeTrue()
        ->and(is_file($starter . '/tsconfig.json'))->toBeTrue()
        ->and(is_file($starter . '/src/modeling.ts'))->toBeTrue()
        ->and(is_file($starter . '/src/exercise.ts'))->toBeTrue()
        ->and(file_get_contents($starter . '/package.json'))->toContain('"typescript": "5.9.3"')
        ->and(file_get_contents($starter . '/tsconfig.json'))->toContain('"exactOptionalPropertyTypes": true')
        ->and(file_get_contents(base_path('.gitignore')))->toContain('.dalt/workspace/');
});

test('the FS02.3 lab is course-owned, pinned, resettable, and keeps generated learner work out of the repository', function () {
    $starter = base_path('.dalt/course/fullstack/typescript-narrowing-lab/starter');

    expect(is_file($starter . '/package.json'))->toBeTrue()
        ->and(is_file($starter . '/package-lock.json'))->toBeTrue()
        ->and(is_file($starter . '/tsconfig.json'))->toBeTrue()
        ->and(is_file($starter . '/src/narrowing.ts'))->toBeTrue()
        ->and(is_file($starter . '/src/exercise.ts'))->toBeTrue()
        ->and(file_get_contents($starter . '/package.json'))->toContain('"typescript": "5.9.3"')
        ->and(file_get_contents($starter . '/tsconfig.json'))->toContain('"exactOptionalPropertyTypes": true')
        ->and(file_get_contents(base_path('.gitignore')))->toContain('.dalt/workspace/');
});

test('the FS02.4 lab is course-owned, pinned, resettable, and keeps generated learner work out of the repository', function () {
    $starter = base_path('.dalt/course/fullstack/typescript-functions-lab/starter');

    expect(is_file($starter . '/package.json'))->toBeTrue()
        ->and(is_file($starter . '/package-lock.json'))->toBeTrue()
        ->and(is_file($starter . '/tsconfig.json'))->toBeTrue()
        ->and(is_file($starter . '/src/functions.ts'))->toBeTrue()
        ->and(is_file($starter . '/src/exercise.ts'))->toBeTrue()
        ->and(file_get_contents($starter . '/package.json'))->toContain('"typescript": "5.9.3"')
        ->and(file_get_contents($starter . '/tsconfig.json'))->toContain('"exactOptionalPropertyTypes": true')
        ->and(file_get_contents(base_path('.gitignore')))->toContain('.dalt/workspace/');
});

test('the FS02.5 runtime-boundaries lab is course-owned, pinned, resettable, and keeps generated learner work out of the repository', function () {
    $starter = base_path('.dalt/course/fullstack/typescript-runtime-boundaries-lab/starter');

    expect(is_file($starter . '/package.json'))->toBeTrue()
        ->and(is_file($starter . '/package-lock.json'))->toBeTrue()
        ->and(is_file($starter . '/tsconfig.json'))->toBeTrue()
        ->and(is_file($starter . '/src/unsafe.ts'))->toBeTrue()
        ->and(is_file($starter . '/src/parser.ts'))->toBeTrue()
        ->and(is_file($starter . '/src/parser.test.ts'))->toBeTrue()
        ->and(file_get_contents($starter . '/package.json'))->toContain('"typescript": "5.9.3"')
        ->and(file_get_contents($starter . '/tsconfig.json'))->toContain('"exactOptionalPropertyTypes": true')
        ->and(file_get_contents($starter . '/src/parser.ts'))->not->toContain('as Issue')
        ->and(file_get_contents($starter . '/src/parser.ts'))->not->toContain(': any')
        ->and(file_get_contents(base_path('.gitignore')))->toContain('.dalt/workspace/');
});

test('the B02 workspace is TypeScript-only, resettable, deliberately incomplete, and has focused evidence snapshots', function () {
    $build = base_path('.dalt/course/build/B02-type-the-future-application');
    $starter = $build . '/starter';
    expect(is_file($build . '/README.md'))->toBeTrue()
        ->and(is_file($starter . '/package.json'))->toBeTrue()
        ->and(is_file($starter . '/package-lock.json'))->toBeTrue()
        ->and(is_file($starter . '/tsconfig.json'))->toBeTrue()
        ->and(is_file($starter . '/src/model.ts'))->toBeTrue()
        ->and(is_file($starter . '/src/parser.ts'))->toBeTrue()
        ->and(file_get_contents($starter . '/src/model.ts'))->toContain('TODO_Issue')
        ->and(file_get_contents($starter . '/src/parser.ts'))->not->toContain('as IssuePreview')
        ->and(file_get_contents($starter . '/src/parser.ts'))->not->toContain(': any')
        ->and(is_file($build . '/reference/broken-assignee/src/main.ts'))->toBeTrue()
        ->and(file_get_contents($build . '/reference/broken-assignee/src/main.ts'))->toContain('assigneeId')
        ->and(is_file($build . '/reference/final/src/parser.test.ts'))->toBeTrue()
        ->and(file_get_contents(base_path('.gitignore')))->toContain('.dalt/workspace/');

    // Parsed, not string-matched: the previous version asserted on unformatted JSON
    // (`"typescript":"5.9.3"` with no spaces) and broke the moment the file was
    // reformatted, which says nothing about whether the pin is correct.
    $package = json_decode((string) file_get_contents($starter . '/package.json'), true, flags: JSON_THROW_ON_ERROR);
    $tsconfig = json_decode((string) file_get_contents($starter . '/tsconfig.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($package['devDependencies']['typescript'])->toBe('5.9.3')
        ->and($package['dependencies'] ?? [])->toBe([], 'B02 is TypeScript-only; it must pull in no runtime dependency.')
        ->and($tsconfig['compilerOptions']['exactOptionalPropertyTypes'])->toBeTrue();

    // The specification names these; FullstackLabExecutionTest runs them.
    expect(array_keys($package['scripts']))->toBe(['typecheck', 'run', 'test']);
});

test('the Part 03 React foundations lab is pinned, resettable, and runnable in a browser', function () {
    $starter = base_path('.dalt/course/fullstack/react-foundations-lab/starter');

    // FS03.2 needs events to click, FS03.3 a form to submit, FS03.4 a keyboard pass
    // and a narrow-screen check. All three need the lab to actually run in a browser,
    // which means an entry point, a dev server and Tailwind — not just a test runner.
    foreach ([
        '/package.json', '/package-lock.json', '/tsconfig.json', '/vite.config.ts', '/index.html',
        '/src/IssueList.tsx', '/src/IssueList.test.tsx', '/src/main.tsx', '/src/App.tsx',
        '/src/index.css', '/src/setup-tests.ts',
    ] as $file) {
        expect(is_file($starter . $file))->toBeTrue("The Part 03 lab is missing {$file}.");
    }

    $package = json_decode((string) file_get_contents($starter . '/package.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($package['dependencies']['react'])->toBe('19.2.3')
        ->and($package['devDependencies'])->toHaveKeys(['@vitejs/plugin-react', 'tailwindcss', '@tailwindcss/vite', 'vitest', 'jsdom'])
        ->and($package['scripts'])->toHaveKeys(['dev', 'build', 'typecheck', 'test']);

    // Whether these commands actually succeed is proven by running them —
    // .dalt/tests/Feature/FullstackLabExecutionTest.php. This test only pins shape.
});

test('every declared Build milestone has a specification, and every specification is reachable', function () {
    $track = FullstackTrack::load();
    $specs = \Core\BuildMilestone::all();

    $declared = [];
    foreach ($track['parts'] as $part) {
        foreach ($part['milestones'] as $milestone) {
            $declared[$milestone['id']] = $milestone;
        }
    }

    expect(array_keys($specs))->toBe(['B00', 'B01', 'B02', 'B03', 'B04', 'B05', 'B06', 'B07', 'B08', 'B09', 'B10', 'B11', 'C01', 'C02', 'C03', 'C04', 'C05', 'C06', 'C07']);

    foreach ($specs as $id => $spec) {
        // array_key_exists rather than toHaveKey(): Pest's toHaveKey() reads a second
        // argument as an expected value, not as a failure message.
        expect(array_key_exists($id, $declared))->toBeTrue("Build specification '{$id}' exists on disk but no part declares it.");
        expect($declared[$id]['route'] ?? null)->toBe(
            \Core\BuildMilestone::routeFor($id),
            "Build milestone '{$id}' has a specification but its manifest route does not match.",
        );
        expect($declared[$id]['prerequisites'] ?? [])->not->toBe(
            [],
            "Build milestone '{$id}' is reachable with no prerequisites, so a learner can open it before doing the work.",
        );
        expect(is_dir($spec['path'] . '/reference'))->toBe(
            $id === 'B02',
            "Author-facing reference snapshots are only expected for B02; EXERCISE_STANDARD.md 55 keeps them out of learner navigation.",
        );
    }

    // Milestones with no specification yet must not advertise a route, or the
    // learner finds the 404 before the author does.
    foreach ($declared as $id => $milestone) {
        if (!isset($specs[$id])) {
            expect(array_key_exists('route', $milestone))->toBeFalse("Milestone '{$id}' declares a route but has no specification.");
        }
    }
});

test('Build milestone pages teach and specify, and never collect learner input', function () {
    // The owner's standing decision, recorded in .dalt/course/build/README.md. An
    // earlier design collected predictions and traces into `required` textareas and
    // discarded every word on submit — ceremony that verified nothing while implying
    // assessment. This test is why it cannot come back by accident.
    $view = (string) file_get_contents(base_path('.dalt/resources/views/learn/fullstack-build.view.php'));

    foreach (['<textarea', '<input type="checkbox"', 'localStorage', 'required'] as $forbidden) {
        expect(str_contains($view, $forbidden))->toBeFalse(
            "The Build milestone view contains '{$forbidden}'. Milestones teach and specify; "
            . 'they do not collect learner input. See .dalt/course/build/README.md.',
        );
    }

    expect(str_contains($view, 'csrf_field()'))->toBeTrue('The completion action must still be CSRF protected.');
    expect(str_contains($view, 'nothing you typed anywhere is stored'))
        ->toBeTrue('The completion action must state plainly that it is self-reported and stores nothing.');

    // One controller and one view serve every milestone. Four bespoke pairs were
    // removed at B03; nineteen was the trajectory.
    $controllers = glob(base_path('.dalt/Http/controllers/learn/*build*.php'));
    $views = glob(base_path('.dalt/resources/views/learn/*build*.view.php'));
    expect(array_map('basename', $controllers))->toBe(['fullstack-build.php'])
        ->and(array_map('basename', $views))->toBe(['fullstack-build.view.php']);
});

test('a malformed Fullstack manifest fails with an actionable error', function () {
    $root = fullstackTrackFixture();
    file_put_contents($root . '/fullstack.php', fullstackTrackManifest('missing-lesson'));
    try {
        expect(fn () => FullstackTrack::load($root))->toThrow(CourseMetadataException::class, "references unknown lesson 'missing-lesson'");
    } finally {
        removeFullstackTrackFixture($root);
    }
});

test('the Core catalog inventory and challenges remain unchanged by Fullstack', function () {
    $lessons = CourseLoader::getLessons();
    $core = array_values(array_filter($lessons, fn (array $lesson): bool => $lesson['section'] !== 'fullstack'));
    expect(array_column($core, 'id'))->toBe([
        '01-request-lifecycle', '02-routing', '03-middleware', '04-authentication', '05-database', '06-docker-basics', '07-dockerfile', '08-docker-compose', '09-postgres-first-contact', '10-postgres-intermediate', '11-dalt-db-layer', '12-docker-intermediate', '13-postgres-advanced', '14-docker-production', '15-postgres-reliability', '16-postgres-advanced-patterns', '17-observability', '18-debugging-and-logging', '19-testing-framework-contracts',
    ])->and(array_column($core, 'order'))->toBe(range(1, 19))
        ->and(array_column(CourseLoader::getChallenges(), 'id'))->toHaveCount(22);
});
