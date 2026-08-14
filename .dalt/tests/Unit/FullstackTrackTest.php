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
        ->and($track['parts']['12']['milestones'])->toHaveCount(7)
        ->and(array_column($fullstackLessons, 'id'))->toBe(['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa', '22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure', '24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types', '28-fs02-5-runtime-boundaries', '29-fs03-1-components-jsx-and-typed-props', '30-fs03-2-state-and-events', '31-fs03-3-forms-and-state-design', '32-fs03-4-tailwind-and-accessible-ui'])
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
        ->and($fullstackLessons[12]['prerequisites'])->toBe(['31-fs03-3-forms-and-state-design']);
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
        ->and(file_get_contents($starter . '/package.json'))->toContain('"typescript":"5.9.3"')
        ->and(file_get_contents($starter . '/tsconfig.json'))->toContain('"exactOptionalPropertyTypes":true')
        ->and(file_get_contents($starter . '/src/model.ts'))->toContain('TODO_Issue')
        ->and(file_get_contents($starter . '/src/parser.ts'))->not->toContain('as IssuePreview')
        ->and(file_get_contents($starter . '/src/parser.ts'))->not->toContain(': any')
        ->and(is_file($build . '/reference/broken-assignee/src/main.ts'))->toBeTrue()
        ->and(file_get_contents($build . '/reference/broken-assignee/src/main.ts'))->toContain('assigneeId')
        ->and(is_file($build . '/reference/final/src/parser.test.ts'))->toBeTrue()
        ->and(file_get_contents(base_path('.gitignore')))->toContain('.dalt/workspace/');
});

test('the Part 03 React foundations lab is pinned, resettable, and separate from B03', function () {
    $starter = base_path('.dalt/course/fullstack/react-foundations-lab/starter');
    expect(is_file($starter . '/package.json'))->toBeTrue()
        ->and(is_file($starter . '/package-lock.json'))->toBeTrue()
        ->and(is_file($starter . '/tsconfig.json'))->toBeTrue()
        ->and(is_file($starter . '/src/IssueList.tsx'))->toBeTrue()
        ->and(is_file($starter . '/src/IssueList.test.tsx'))->toBeTrue()
        ->and(file_get_contents($starter . '/package.json'))->toContain('"react": "19.2.3"')
        ->and(is_dir(base_path('.dalt/course/build/B03-local-issue-tracker')))->toBeFalse();
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
