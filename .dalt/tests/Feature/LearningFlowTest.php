<?php

declare(strict_types=1);

use Core\CourseLoader;
use Tests\Support\ApplicationTestClient;

function p05CopyTree(string $source, string $destination): void
{
    if (!mkdir($destination, 0700, true) && !is_dir($destination)) {
        throw new RuntimeException("Unable to create P05 fixture directory: {$destination}");
    }

    foreach (new FilesystemIterator($source) as $entry) {
        if ($entry->getBasename() === 'node_modules') {
            continue;
        }

        $target = $destination . DIRECTORY_SEPARATOR . $entry->getBasename();
        if ($entry->isDir() && !$entry->isLink()) {
            p05CopyTree($entry->getPathname(), $target);
        } else {
            copy($entry->getPathname(), $target);
        }
    }
}

function p05RemoveTree(string $path): void
{
    if (!file_exists($path) && !is_link($path)) {
        return;
    }
    if (is_dir($path) && !is_link($path)) {
        foreach (new FilesystemIterator($path) as $entry) {
            p05RemoveTree($entry->getPathname());
        }
        rmdir($path);
        return;
    }
    unlink($path);
}

function p05ProjectFixture(): string
{
    $root = sys_get_temp_dir() . '/dalt-p05-' . bin2hex(random_bytes(6));
    mkdir($root, 0700, true);

    foreach (['config', 'framework', 'public', 'resources', 'routes', 'storage', '.dalt'] as $directory) {
        p05CopyTree(base_path($directory), $root . '/' . $directory);
    }
    copy(base_path('.env.example'), $root . '/.env');
    symlink(base_path('vendor'), $root . '/vendor');

    foreach (['active_challenge.txt', 'challenge-state.json', 'challenge.lock', 'progress.json'] as $runtimeFile) {
        $path = $root . '/.dalt/' . $runtimeFile;
        if (file_exists($path)) {
            unlink($path);
        }
    }
    p05RemoveTree($root . '/.dalt/challenge-backup');

    return $root;
}

/** @return array<string, mixed> */
function p05Manager(string $root, string $action, string $argument = ''): array
{
    $process = proc_open(
        [PHP_BINARY, base_path('.dalt/tests/Support/run-challenge-manager.php'), $root, $action, $argument],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
        null,
        ['bypass_shell' => true],
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the P05 challenge manager probe.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($stderr !== '') {
        throw new RuntimeException($stderr);
    }

    return ['exitCode' => $exitCode, ...json_decode($stdout, true, 512, JSON_THROW_ON_ERROR)];
}

test('learning pages expose navigation state prerequisites and no-script content', function () {
    $client = new ApplicationTestClient();
    $index = $client->request('GET', '/learn');
    $fullstack = $client->request('GET', '/learn/fullstack');
    $resources = $client->request('GET', '/learn/resources');
    $dockerTrack = $client->request('GET', '/learn/tracks/docker');
    $postgresTrack = $client->request('GET', '/learn/tracks/postgres');
    $roadmap = $client->request('GET', '/learn/roadmap');
    $lesson = $client->request('GET', '/learn/lessons/11-dalt-db-layer');
    $challenge = $client->request('GET', '/learn/challenges/db-missing-pagination');

    expect($index->statusCode)->toBe(200)
        ->and($index->body)->toContain('Skip to main content')
        ->and($index->body)->toContain('DALT Fullstack')
        ->and($index->body)->toContain('/learn/fullstack')
        ->and($fullstack->statusCode)->toBe(200)
        ->and($fullstack->body)->toContain('PART 00')
        ->and($fullstack->body)->toContain('Browser, server, request, response')
        ->and($fullstack->body)->toContain('Forms, JavaScript, JSON and the SPA model')
        ->and($fullstack->body)->toContain('Planned material')
        ->and($resources->statusCode)->toBe(200)
        ->and($resources->body)->toContain('All resources')
        ->and($resources->body)->toContain('/learn/resources?section=docker')
        ->and($dockerTrack->statusCode)->toBe(200)
        ->and($dockerTrack->body)->toContain('Your Docker path')
        ->and($dockerTrack->body)->toContain('Docker Basics')
        ->and($dockerTrack->body)->not->toContain('PostgreSQL First Contact')
        ->and($postgresTrack->statusCode)->toBe(200)
        ->and($postgresTrack->body)->toContain('You can still start PostgreSQL now.')
        ->and($roadmap->statusCode)->toBe(200)
        ->and($roadmap->body)->toContain('Learning roadmap')
        ->and($roadmap->body)->toContain('Foundation')
        ->and($roadmap->body)->toContain('Docker')
        ->and($roadmap->body)->toContain('PostgreSQL')
        ->and($roadmap->body)->toContain('Operations')
        ->and($roadmap->body)->toContain('Roadmap')
        ->and($lesson->statusCode)->toBe(200)
        ->and($lesson->body)->toContain('href="/learn/resources"')
        ->and($lesson->body)->toContain('All resources')
        ->and($lesson->body)->toContain('Recommended prerequisites')
        ->and($lesson->body)->toContain('<h2>')
        ->and($lesson->body)->toContain('language-php')
        ->and($lesson->body)->not->toContain('lesson-content-data')
        ->and($lesson->body)->not->toContain('markdown-fallback')
        ->and($challenge->statusCode)->toBe(200)
        ->and($challenge->body)->toContain('Browser verification needs JavaScript')
        ->and($challenge->body)->toContain('php artisan challenge:verify')
        ->and($challenge->body)->toContain('<h2>What This Challenge Is</h2>')
        ->and($challenge->body)->not->toContain('challenge-content-data')
        ->and($challenge->body)->toContain('meta name="csrf-token"');
});

test('roadmap is a server-rendered curriculum view of effective learning progress', function () {
    $root = p05ProjectFixture();

    try {
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => ['broken-routing'],
            'completed_lessons' => ['06-docker-basics'],
            'last_visited_lesson' => '08-docker-compose',
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $roadmap = $client->request('GET', '/learn/roadmap');

        $totalLessons = count(\Core\CourseLoader::getLessons());

        expect($roadmap->statusCode)->toBe(200)
            ->and($roadmap->body)->toContain("2 of {$totalLessons} lessons completed")
            ->and($roadmap->body)->toContain('1 verified through practice')
            ->and($roadmap->body)->toContain('Routing')
            ->and($roadmap->body)->toContain('>Verified</span>')
            ->and($roadmap->body)->toContain('Docker Basics')
            ->and($roadmap->body)->toContain('>Completed</span>')
            ->and($roadmap->body)->toContain('Docker Compose')
            ->and($roadmap->body)->toContain('>In progress</span>')
            ->and($roadmap->body)->toContain('Recommended first: <a href="/learn/lessons/08-docker-compose"')
            ->and($roadmap->body)->toContain('/learn/tracks/foundation')
            ->and($roadmap->body)->toContain('/learn/tracks/docker')
            ->and($roadmap->body)->toContain('/learn/tracks/postgres')
            ->and($roadmap->body)->toContain('/learn/tracks/operations')
            ->and($roadmap->body)->toContain('/learn/lessons/17-observability')
            ->and($roadmap->body)->not->toContain('localStorage')
            ->and($roadmap->body)->not->toContain('Marked understood')
            ->and($roadmap->body)->not->toContain('Needs prerequisites first')
            ->and($roadmap->body)->not->toContain('>Ready<');

        foreach ([
            'foundation' => ['Request Lifecycle', 'Routing', 'Middleware', 'Authentication', 'Database', 'DALT Database Layer'],
            'docker' => ['Docker Basics', 'Writing Dockerfiles', 'Docker Compose', 'Docker Intermediate', 'Docker Production Patterns'],
            'postgres' => ['PostgreSQL First Contact', 'PostgreSQL Core', 'PostgreSQL Advanced', 'PostgreSQL Reliability', 'Advanced PostgreSQL'],
        ] as $section => $titles) {
            $sectionStart = strpos($roadmap->body, '<h2 id="' . $section . '-title"');
            $sectionEnd = strpos($roadmap->body, 'View ', $sectionStart);
            $sectionHtml = substr($roadmap->body, $sectionStart, $sectionEnd - $sectionStart);
            $positions = array_map(static fn (string $title): int => strpos($sectionHtml, '>' . $title . '<'), $titles);
            $sortedPositions = $positions;
            sort($sortedPositions);
            expect($positions)->each->toBeGreaterThanOrEqual(0);
            expect($positions)->toBe($sortedPositions);
        }
    } finally {
        p05RemoveTree($root);
    }
});

test('roadmap reports a complete curriculum without a fake next lesson', function () {
    $root = p05ProjectFixture();

    try {
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => array_column(\Core\CourseLoader::getLessons(), 'id'),
            'last_visited_lesson' => '17-observability',
        ], JSON_THROW_ON_ERROR));
        $roadmap = (new ApplicationTestClient($root))->request('GET', '/learn/roadmap');
        $totalLessons = count(\Core\CourseLoader::getLessons());

        expect($roadmap->body)->toContain("{$totalLessons} of {$totalLessons} lessons completed")
            ->and($roadmap->body)->not->toContain('Continue from');
    } finally {
        p05RemoveTree($root);
    }
});

test('learning paths and resource filters keep navigation intentions separate', function () {
    $client = new ApplicationTestClient();
    $dashboard = $client->request('GET', '/learn');
    $dockerResources = $client->request('GET', '/learn/resources?section=docker', ['section' => 'docker']);
    $routing = $client->request('GET', '/learn/lessons/02-routing');

    expect($dashboard->body)->toContain('/learn/tracks/docker')
        ->and($dashboard->body)->toContain('/learn/tracks/postgres')
        ->and($dashboard->body)->not->toContain('/learn/resources?section=docker')
        ->and($dockerResources->body)->toContain('Package and run applications reliably.')
        ->and($dockerResources->body)->toContain('5 lessons')
        ->and($dockerResources->body)->not->toContain('Foundational theory for backend systems')
        ->and($routing->body)->toContain('Previous in Foundation')
        ->and($routing->body)->toContain('Next in Foundation')
        ->and($routing->body)->toContain('/learn/lessons/03-middleware')
        ->and($routing->body)->not->toContain('/learn/lessons/04-authentication\" class="group rounded-xl');
});

test('Core and Fullstack continuation and progress stay independently scoped', function () {
    $root = p05ProjectFixture();
    try {
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [], 'completed_lessons' => [], 'last_visited_lesson' => null,
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $dashboard = $client->request('GET', '/learn');
        $fullstack = $client->request('GET', '/learn/fullstack');

        // Derived, not pinned. This assertion read '0 / 13 lessons' for as long as it
        // took someone to add nine Fullstack lessons without re-running the suite, and
        // a literal here goes stale again at every part. The fixture copies the live
        // .dalt, so counting the catalog is counting what the page must render.
        $sections = array_count_values(array_column(CourseLoader::getLessons(), 'section'));
        $fullstackLessons = $sections['fullstack'] ?? 0;
        // Core is every section that is not Fullstack: foundation, docker, postgres,
        // operations. Amendment A keeps the two tracks separate, so they are counted
        // and rendered separately.
        $coreLessons = array_sum($sections) - $fullstackLessons;

        expect($dashboard->body)->toContain('DALT Core')
            ->and($dashboard->body)->toContain('0 / ' . $coreLessons . ' lessons')
            ->and($dashboard->body)->toContain('0 / ' . $fullstackLessons . ' lessons')
            ->and($dashboard->body)->toContain('Begin with Request Lifecycle')
            ->and($dashboard->body)->toContain('Begin with Browser, server, request, response')
            ->and($fullstack->body)->toContain('Start with Browser, server, request, response')
            ->and($fullstack->body)->not->toContain('Required: <a href="/learn/lessons/01-request-lifecycle"');
    } finally {
        p05RemoveTree($root);
    }
});

test('FS00.2 follows FS00.1 in the Fullstack journey and keeps the Fullstack back navigation', function () {
    $root = p05ProjectFixture();
    try {
        $client = new ApplicationTestClient($root);
        $lesson = $client->request('GET', '/learn/lessons/20-fs00-1-browser-and-http');
        $complete = $client->request(
            'POST',
            '/learn/lessons/20-fs00-1-browser-and-http/complete',
            input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token'],
        );
        $nextLesson = $client->request('GET', '/learn/lessons/21-fs00-2-forms-json-and-spa');
        $track = $client->request('GET', '/learn/fullstack');

        expect($lesson->statusCode)->toBe(200)
            ->and($lesson->body)->toContain('href="/learn/fullstack"')
            ->and($lesson->body)->toContain('DALT Fullstack')
            ->and($lesson->body)->toContain('30–45 minutes')
            ->and($lesson->body)->toContain('Foundation')
            ->and($lesson->body)->not->toContain('Lesson ID: FS00.1')
            ->and($lesson->body)->not->toContain('Primary source dossier: FSO_PART_00.md')
            ->and($lesson->body)->toContain('Predict before reading')
            ->and($lesson->body)->toContain('Mode: manual-proof')
            ->and($lesson->body)->toContain('<details>')
            ->and($lesson->body)->toContain('Closed-book checkpoint')
            ->and($complete->statusCode)->toBe(303)
            ->and($nextLesson->statusCode)->toBe(200)
            ->and($nextLesson->body)->toContain('href="/learn/fullstack"')
            ->and($nextLesson->body)->toContain('Forms, JavaScript, JSON and the SPA model')
            ->and($nextLesson->body)->toContain('30–45 minutes')
            ->and($nextLesson->body)->not->toContain('Lesson ID: FS00.2')
            ->and($nextLesson->body)->toContain('Two ways to submit')
            ->and($nextLesson->body)->toContain('Closed-book checkpoint')
            ->and($track->body)->toContain('1 of 2 available lessons complete')
            ->and($track->body)->toContain('Continue Forms, JavaScript, JSON and the SPA model');
    } finally {
        p05RemoveTree($root);
    }
});

test('the Part 00 observation fixture exposes a redirecting form and a JSON endpoint', function () {
    $client = new ApplicationTestClient();
    $fixture = $client->request('GET', '/learn/fullstack/observe/forms');
    $traditional = $client->request('POST', '/learn/fullstack/observe/forms/traditional', input: ['title' => 'Browser-created request']);
    $redirectTarget = $client->request('GET', '/learn/fullstack/observe/forms?submitted=traditional', ['submitted' => 'traditional']);
    $json = $client->request('POST', '/learn/fullstack/observe/forms/json');

    expect($fixture->statusCode)->toBe(200)
        ->and($fixture->body)->toContain('Ordinary HTML form')
        ->and($fixture->body)->toContain('JavaScript-controlled form')
        ->and($fixture->body)->toContain('event.preventDefault()')
        ->and($fixture->body)->toContain('/learn/fullstack/observe/forms/traditional')
        ->and($fixture->body)->toContain('/learn/fullstack/observe/forms/json')
        ->and($traditional->statusCode)->toBe(303)
        ->and($redirectTarget->body)->toContain('The ordinary form navigated here after its redirect.')
        ->and($json->statusCode)->toBe(200)
        ->and($json->body)->toBe('{"accepted":true,"message":"The server received the preview request."}');
});

test('B00 is gated by Part 00 lessons, self-reports completion, and completes only the Fullstack part', function () {
    $root = p05ProjectFixture();
    try {
        $client = new ApplicationTestClient($root);
        $locked = $client->request('GET', '/learn/fullstack/build/b00');

        $first = $client->request(
            'POST', '/learn/lessons/20-fs00-1-browser-and-http/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token'],
        );
        $stillLocked = $client->request('GET', '/learn/fullstack/build/b00');
        $second = $client->request(
            'POST', '/learn/lessons/21-fs00-2-forms-json-and-spa/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token'],
        );
        $build = $client->request('GET', '/learn/fullstack/build/b00');
        $complete = $client->request(
            'POST', '/learn/fullstack/build/b00/complete', input: ['self_report' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token'],
        );
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $journey = $client->request('GET', '/learn/fullstack');
        $core = $client->request('GET', '/learn/tracks/foundation');

        expect($locked->statusCode)->toBe(303)
            ->and($first->statusCode)->toBe(303)
            ->and($stillLocked->statusCode)->toBe(303)
            ->and($second->statusCode)->toBe(303)
            ->and($build->statusCode)->toBe(200)
            ->and($build->body)->toContain('Build B00 · Part 00')
            ->and($build->body)->toContain('Trace the system')
            ->and($build->body)->toContain('Close everything and recall')
            ->and($build->body)->toContain('Acceptance criteria')
            ->and($build->body)->toContain('nothing you typed anywhere is stored')
            ->and($build->body)->toContain('href="/learn/fullstack"')
            ->and($complete->statusCode)->toBe(303)
            ->and($progress['completed_milestones'])->toContain('B00')
            ->and($progress['completed_lessons'])->toBe(['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa'])
            ->and($journey->body)->toContain('Part 00 complete')
            ->and($journey->body)->toContain('PART 01')
            ->and($journey->body)->toContain('Data, functions and transformations')
            ->and($journey->body)->toContain('/learn/lessons/22-fs01-1-data-functions-transformations')
            ->and($journey->body)->toContain('B01')
            ->and($core->statusCode)->toBe(200);
    } finally {
        p05RemoveTree($root);
    }
});

test('FS01.2 follows FS01.1, preserves Fullstack navigation, and leaves B01 and Part 02 unavailable until both JavaScript lessons are complete', function () {
    $root = p05ProjectFixture();
    try {
        $lockedClient = new ApplicationTestClient($root);
        $locked = $lockedClient->request('GET', '/learn/lessons/22-fs01-1-data-functions-transformations');
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa'],
            'completed_milestones' => ['B00'],
            'last_visited_lesson' => '21-fs00-2-forms-json-and-spa',
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $journey = $client->request('GET', '/learn/fullstack');
        $lesson = $client->request('GET', '/learn/lessons/22-fs01-1-data-functions-transformations');
        $lockedNext = $client->request('GET', '/learn/lessons/23-fs01-2-modules-async-and-failure');
        $complete = $client->request(
            'POST',
            '/learn/lessons/22-fs01-1-data-functions-transformations/complete',
            input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token'],
        );
        $nextLesson = $client->request('GET', '/learn/lessons/23-fs01-2-modules-async-and-failure');
        $afterCompletion = $client->request('GET', '/learn/fullstack');
        $b01StillLocked = $client->request('GET', '/learn/fullstack/build/b01');
        $core = $client->request('GET', '/learn/tracks/foundation');

        expect($locked->statusCode)->toBe(303)
            ->and($journey->body)->toContain('Continue Data, functions and transformations')
            ->and($journey->body)->toContain('Part 00 complete')
            ->and($journey->body)->toContain('/learn/lessons/22-fs01-1-data-functions-transformations')
            ->and($journey->body)->toContain('B01')
            ->and($journey->body)->toContain('Planned material · not yet available')
            ->and($lesson->statusCode)->toBe(200)
            ->and($lesson->body)->toContain('href="/learn/fullstack"')
            ->and($lesson->body)->toContain('Mode: self-reported practice')
            ->and($lesson->body)->toContain('Predict before reading')
            ->and($lesson->body)->toContain('Closed-book checkpoint')
            ->and($lockedNext->statusCode)->toBe(303)
            ->and($complete->statusCode)->toBe(303)
            ->and($nextLesson->statusCode)->toBe(200)
            ->and($nextLesson->body)->toContain('href="/learn/fullstack"')
            ->and($nextLesson->body)->toContain('Modules, async JavaScript and failure')
            ->and($nextLesson->body)->toContain('Fetch is a sequence, not')
            ->and($nextLesson->body)->toContain('<details>')
            ->and($afterCompletion->body)->toContain('Continue Modules, async JavaScript and failure')
            ->and($afterCompletion->body)->toContain('/learn/lessons/23-fs01-2-modules-async-and-failure')
            ->and($afterCompletion->body)->toContain('B01')
            ->and($afterCompletion->body)->toContain('Planned material · not yet available')
            ->and($b01StillLocked->statusCode)->toBe(303)
            ->and($core->body)->toContain('Foundation')
            ->and($core->body)->not->toContain('Data, functions and transformations');
    } finally {
        p05RemoveTree($root);
    }
});

test('B01 requires both Part 01 lessons, stores its own completion, and unlocks only FS02.1 in Part 02', function () {
    $root = p05ProjectFixture();
    try {
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => [
                '20-fs00-1-browser-and-http',
                '21-fs00-2-forms-json-and-spa',
                '22-fs01-1-data-functions-transformations',
            ],
            'completed_milestones' => ['B00'],
            'last_visited_lesson' => '22-fs01-1-data-functions-transformations',
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $locked = $client->request('GET', '/learn/fullstack/build/b01');

        $completeLesson = $client->request(
            'POST', '/learn/lessons/23-fs01-2-modules-async-and-failure/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token'],
        );
        $build = $client->request('GET', '/learn/fullstack/build/b01');
        $complete = $client->request(
            'POST', '/learn/fullstack/build/b01/complete', input: ['self_report' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token'],
        );
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $journey = $client->request('GET', '/learn/fullstack');
        $completedBuild = $client->request('GET', '/learn/fullstack/build/b01');
        $partTwoLesson = $client->request('GET', '/learn/lessons/24-fs02-1-typescript-mental-model');
        $core = $client->request('GET', '/learn/tracks/foundation');

        expect($locked->statusCode)->toBe(303)
            ->and($completeLesson->statusCode)->toBe(303)
            ->and($build->statusCode)->toBe(200)
            ->and($build->body)->toContain('Build B01 · Part 01')
            ->and($build->body)->toContain('.dalt/workspace/b01-issue-triage')
            ->and($build->body)->toContain('Decisions you have to make')
            ->and($build->body)->toContain('nothing you typed anywhere is stored')
            ->and($build->body)->toContain('href="/learn/fullstack"')
            ->and($complete->statusCode)->toBe(303)
            ->and($progress['completed_lessons'])->toBe([
                '20-fs00-1-browser-and-http',
                '21-fs00-2-forms-json-and-spa',
                '22-fs01-1-data-functions-transformations',
                '23-fs01-2-modules-async-and-failure',
            ])
            ->and($progress['completed_milestones'])->toBe(['B00', 'B01'])
            ->and($journey->body)->toContain('Part 01 complete')
            ->and($journey->body)->toContain('PART 02')
            ->and($journey->body)->toContain('The TypeScript mental model')
            ->and($journey->body)->toContain('/learn/lessons/24-fs02-1-typescript-mental-model')
            ->and($journey->body)->not->toContain('/learn/fullstack/build/b02')
            ->and($completedBuild->statusCode)->toBe(200)
            ->and($completedBuild->body)->toContain('B01 marked complete')
            ->and($completedBuild->body)->toContain('Back to the journey')
            ->and($partTwoLesson->statusCode)->toBe(200)
            ->and($partTwoLesson->body)->toContain('href="/learn/fullstack"')
            ->and($partTwoLesson->body)->toContain('What survives into JavaScript?')
            ->and($partTwoLesson->body)->toContain('Mode: self-reported practice')
            ->and($partTwoLesson->body)->toContain('Closed-book checkpoint')
            ->and($core->statusCode)->toBe(200);
    } finally {
        p05RemoveTree($root);
    }
});

test('FS02.1 stays gated by B01, records ordinary completion, and does not complete Part 02 or unlock later TypeScript work', function () {
    $root = p05ProjectFixture();
    try {
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => [
                '20-fs00-1-browser-and-http',
                '21-fs00-2-forms-json-and-spa',
                '22-fs01-1-data-functions-transformations',
                '23-fs01-2-modules-async-and-failure',
            ],
            'completed_milestones' => ['B00'],
            'last_visited_lesson' => '23-fs01-2-modules-async-and-failure',
        ], JSON_THROW_ON_ERROR));
        $beforeB01 = new ApplicationTestClient($root);
        $locked = $beforeB01->request('GET', '/learn/lessons/24-fs02-1-typescript-mental-model');

        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => [
                '20-fs00-1-browser-and-http',
                '21-fs00-2-forms-json-and-spa',
                '22-fs01-1-data-functions-transformations',
                '23-fs01-2-modules-async-and-failure',
            ],
            'completed_milestones' => ['B00', 'B01'],
            'last_visited_lesson' => '23-fs01-2-modules-async-and-failure',
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $lesson = $client->request('GET', '/learn/lessons/24-fs02-1-typescript-mental-model');
        $complete = $client->request(
            'POST', '/learn/lessons/24-fs02-1-typescript-mental-model/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token'],
        );
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $journey = $client->request('GET', '/learn/fullstack');
        $laterLesson = $client->request('GET', '/learn/lessons/fs02-2');
        $b02 = $client->request('GET', '/learn/fullstack/build/b02');
        $core = $client->request('GET', '/learn/tracks/foundation');

        expect($locked->statusCode)->toBe(303)
            ->and($lesson->statusCode)->toBe(200)
            ->and($lesson->body)->toContain('TypeScript did not make JavaScript runtime magical')
            ->and($lesson->body)->toContain('import type')
            ->and($lesson->body)->toContain('strict')
            ->and($complete->statusCode)->toBe(303)
            ->and($progress['completed_lessons'])->toContain('24-fs02-1-typescript-mental-model')
            ->and($progress['completed_milestones'])->toBe(['B00', 'B01'])
            ->and($journey->body)->toContain('The TypeScript mental model')
            ->and($journey->body)->not->toContain('Part 02 complete')
            ->and($journey->body)->not->toContain('/learn/fullstack/build/b02')
            ->and($laterLesson->statusCode)->toBe(404)
            ->and($b02->statusCode)->toBe(303)
            ->and($core->statusCode)->toBe(200)
            ->and($core->body)->toContain('Foundation')
            ->and($core->body)->not->toContain('The TypeScript mental model');
    } finally {
        p05RemoveTree($root);
    }
});

test('FS02.2 requires FS02.1, records ordinary completion, and leaves later TypeScript work and B02 unavailable', function () {
    $root = p05ProjectFixture();
    try {
        $beforeFs021 = new ApplicationTestClient($root);
        $locked = $beforeFs021->request('GET', '/learn/lessons/25-fs02-2-modeling-application-data');

        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => [
                '20-fs00-1-browser-and-http',
                '21-fs00-2-forms-json-and-spa',
                '22-fs01-1-data-functions-transformations',
                '23-fs01-2-modules-async-and-failure',
                '24-fs02-1-typescript-mental-model',
            ],
            'completed_milestones' => ['B00', 'B01'],
            'last_visited_lesson' => '24-fs02-1-typescript-mental-model',
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $journeyBeforeCompletion = $client->request('GET', '/learn/fullstack');
        $lesson = $client->request('GET', '/learn/lessons/25-fs02-2-modeling-application-data');
        $complete = $client->request(
            'POST', '/learn/lessons/25-fs02-2-modeling-application-data/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token'],
        );
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $journey = $client->request('GET', '/learn/fullstack');
        $laterLesson = $client->request('GET', '/learn/lessons/fs02-3');
        $b02 = $client->request('GET', '/learn/fullstack/build/b02');
        $core = $client->request('GET', '/learn/tracks/foundation');

        expect($locked->statusCode)->toBe(303)
            ->and($journeyBeforeCompletion->body)->toContain('Continue Modeling application data')
            ->and($journeyBeforeCompletion->body)->toContain('/learn/lessons/25-fs02-2-modeling-application-data')
            ->and($lesson->statusCode)->toBe(200)
            ->and($lesson->body)->toContain('href="/learn/fullstack"')
            ->and($lesson->body)->toContain('Optional is not the same as empty')
            ->and($lesson->body)->toContain('Focused exercise — evolve one honest issue model')
            ->and($lesson->body)->toContain('Closed-book checkpoint')
            ->and($complete->statusCode)->toBe(303)
            ->and($progress['completed_lessons'])->toContain('25-fs02-2-modeling-application-data')
            ->and($progress['completed_milestones'])->toBe(['B00', 'B01'])
            ->and($journey->body)->toContain('Modeling application data')
            ->and($journey->body)->not->toContain('Part 02 complete')
            ->and($journey->body)->not->toContain('/learn/fullstack/build/b02')
            ->and($laterLesson->statusCode)->toBe(404)
            ->and($b02->statusCode)->toBe(303)
            ->and($core->statusCode)->toBe(200)
            ->and($core->body)->toContain('Foundation')
            ->and($core->body)->not->toContain('Modeling application data');
    } finally {
        p05RemoveTree($root);
    }
});

test('FS02.3 requires FS02.2, records ordinary completion, and unlocks only FS02.4', function () {
    $root = p05ProjectFixture();
    try {
        $locked = (new ApplicationTestClient($root))->request('GET', '/learn/lessons/26-fs02-3-unions-narrowing-and-unknown');
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa', '22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure', '24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data'],
            'completed_milestones' => ['B00', 'B01'],
            'last_visited_lesson' => '25-fs02-2-modeling-application-data',
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $journeyBefore = $client->request('GET', '/learn/fullstack');
        $lesson = $client->request('GET', '/learn/lessons/26-fs02-3-unions-narrowing-and-unknown');
        $complete = $client->request('POST', '/learn/lessons/26-fs02-3-unions-narrowing-and-unknown/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token']);
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $journey = $client->request('GET', '/learn/fullstack');
        $fs024 = $client->request('GET', '/learn/lessons/27-fs02-4-functions-generics-and-reusable-types');
        $fs025 = $client->request('GET', '/learn/lessons/28-fs02-5-runtime-boundaries');
        $b02 = $client->request('GET', '/learn/fullstack/build/b02');
        $core = $client->request('GET', '/learn/tracks/foundation');

        expect($locked->statusCode)->toBe(303)
            ->and($journeyBefore->body)->toContain('Continue Unions, narrowing and unknown')
            ->and($lesson->statusCode)->toBe(200)
            ->and($lesson->body)->toContain('href="/learn/fullstack"')
            ->and($lesson->body)->toContain('Unknown asks for proof')
            ->and($lesson->body)->toContain('Focused exercise — prove, model, then evolve')
            ->and($complete->statusCode)->toBe(303)
            ->and($progress['completed_lessons'])->toContain('26-fs02-3-unions-narrowing-and-unknown')
            ->and($progress['completed_milestones'])->toBe(['B00', 'B01'])
            ->and($journey->body)->not->toContain('Part 02 complete')
            ->and($fs024->statusCode)->toBe(200)
            ->and($fs025->statusCode)->toBe(303)
            ->and($b02->statusCode)->toBe(303)
            ->and($core->body)->toContain('Foundation')
            ->and($core->body)->not->toContain('Unions, narrowing and unknown');
    } finally {
        p05RemoveTree($root);
    }
});

test('FS02.4 requires FS02.3, records only its own completion, then unlocks FS02.5 while B02 stays unavailable', function () {
    $root = p05ProjectFixture();
    try {
        $locked = (new ApplicationTestClient($root))->request('GET', '/learn/lessons/27-fs02-4-functions-generics-and-reusable-types');
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa', '22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure', '24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown'],
            'completed_milestones' => ['B00', 'B01'],
            'last_visited_lesson' => '26-fs02-3-unions-narrowing-and-unknown',
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $journeyBefore = $client->request('GET', '/learn/fullstack');
        $lesson = $client->request('GET', '/learn/lessons/27-fs02-4-functions-generics-and-reusable-types');
        $complete = $client->request('POST', '/learn/lessons/27-fs02-4-functions-generics-and-reusable-types/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token']);
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $journey = $client->request('GET', '/learn/fullstack');
        $fs025 = $client->request('GET', '/learn/lessons/28-fs02-5-runtime-boundaries');
        $b02 = $client->request('GET', '/learn/fullstack/build/b02');
        $core = $client->request('GET', '/learn/tracks/foundation');

        expect($locked->statusCode)->toBe(303)
            ->and($journeyBefore->body)->toContain('Continue Functions, generics and reusable types')
            ->and($journeyBefore->body)->toContain('/learn/lessons/27-fs02-4-functions-generics-and-reusable-types')
            ->and($lesson->statusCode)->toBe(200)
            ->and($lesson->body)->toContain('href="/learn/fullstack"')
            ->and($lesson->body)->toContain('A function is a contract')
            ->and($lesson->body)->toContain('Focused exercise — evolve typed issue utilities')
            ->and($lesson->body)->toContain('Closed-book checkpoint')
            ->and($complete->statusCode)->toBe(303)
            ->and($progress['completed_lessons'])->toContain('27-fs02-4-functions-generics-and-reusable-types')
            ->and($progress['completed_milestones'])->toBe(['B00', 'B01'])
            ->and($journey->body)->toContain('Functions, generics and reusable types')
            ->and($journey->body)->toContain('Runtime boundaries')
            ->and($journey->body)->not->toContain('Part 02 complete')
            ->and($fs025->statusCode)->toBe(200)
            ->and($fs025->body)->toContain('href="/learn/fullstack"')
            ->and($fs025->body)->toContain('Where TypeScript\'s knowledge stops')
            ->and($fs025->body)->toContain('Focused exercise — establish the Issue trust boundary')
            ->and($b02->statusCode)->toBe(303)
            ->and($core->statusCode)->toBe(200)
            ->and($core->body)->toContain('Foundation')
            ->and($core->body)->not->toContain('Functions, generics and reusable types');
    } finally {
        p05RemoveTree($root);
    }
});

test('FS02.5 requires FS02.4, records only itself, and leaves B02, Part 03, and Core independent', function () {
    $root = p05ProjectFixture();
    try {
        $locked = (new ApplicationTestClient($root))->request('GET', '/learn/lessons/28-fs02-5-runtime-boundaries');
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa', '22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure', '24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types'],
            'completed_milestones' => ['B00', 'B01'],
            'last_visited_lesson' => '27-fs02-4-functions-generics-and-reusable-types',
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $lesson = $client->request('GET', '/learn/lessons/28-fs02-5-runtime-boundaries');
        $complete = $client->request('POST', '/learn/lessons/28-fs02-5-runtime-boundaries/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token']);
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $journey = $client->request('GET', '/learn/fullstack');
        $b02 = $client->request('GET', '/learn/fullstack/build/b02');
        $part03 = $client->request('GET', '/learn/fullstack');
        $core = $client->request('GET', '/learn/tracks/foundation');

        expect($locked->statusCode)->toBe(303)
            ->and($lesson->statusCode)->toBe(200)
            ->and($lesson->body)->toContain('href="/learn/fullstack"')
            ->and($lesson->body)->toContain('COMPILER GREEN')
            ->and($lesson->body)->toContain('Closed-book checkpoint')
            ->and($complete->statusCode)->toBe(303)
            ->and($progress['completed_lessons'])->toContain('28-fs02-5-runtime-boundaries')
            ->and($progress['completed_milestones'])->toBe(['B00', 'B01'])
            ->and($journey->body)->toContain('Runtime boundaries')
            ->and($journey->body)->not->toContain('Part 02 complete')
            ->and($journey->body)->toContain('B02')
            ->and($b02->statusCode)->toBe(200)
            ->and($part03->body)->toContain('Planned material · not yet available')
            ->and($core->statusCode)->toBe(200)
            ->and($core->body)->toContain('Foundation')
            ->and($core->body)->not->toContain('Runtime boundaries');
    } finally {
        p05RemoveTree($root);
    }
});

test('B02 requires all Part 02 lessons, completes Part 02 separately, and does not unlock unimplemented React', function () {
    $root = p05ProjectFixture();
    try {
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa', '22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure', '24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types'],
            'completed_milestones' => ['B00', 'B01'], 'last_visited_lesson' => null,
        ], JSON_THROW_ON_ERROR));
        $before = new ApplicationTestClient($root);
        $locked = $before->request('GET', '/learn/fullstack/build/b02');
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa', '22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure', '24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types', '28-fs02-5-runtime-boundaries'],
            'completed_milestones' => ['B00', 'B01'], 'last_visited_lesson' => null,
        ], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $build = $client->request('GET', '/learn/fullstack/build/b02');
        $complete = $client->request('POST', '/learn/fullstack/build/b02/complete', input: ['self_report' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token']);
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $journey = $client->request('GET', '/learn/fullstack');
        expect($locked->statusCode)->toBe(303)
            ->and($build->statusCode)->toBe(200)
            ->and($build->body)->toContain('Back to DALT Fullstack')
            ->and($build->body)->toContain('Build B02 · Part 02')
            ->and($build->body)->toContain('The trust boundary')
            ->and($build->body)->toContain('Prove it to yourself')
            ->and($complete->statusCode)->toBe(303)
            ->and($progress['completed_milestones'])->toBe(['B00', 'B01', 'B02'])
            ->and($journey->body)->toContain('Part 02 complete')
            ->and($journey->body)->toContain('Planned material · not yet available')
            ->and($journey->body)->not->toContain('/learn/fullstack/build/b03');
    } finally { p05RemoveTree($root); }
});

test('Part 03 React lessons unlock the B03 local issue tracker and record its self-reported completion', function () {
    $root = p05ProjectFixture();
    try {
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => ['20-fs00-1-browser-and-http', '21-fs00-2-forms-json-and-spa', '22-fs01-1-data-functions-transformations', '23-fs01-2-modules-async-and-failure', '24-fs02-1-typescript-mental-model', '25-fs02-2-modeling-application-data', '26-fs02-3-unions-narrowing-and-unknown', '27-fs02-4-functions-generics-and-reusable-types', '28-fs02-5-runtime-boundaries'],
            'completed_milestones' => ['B00', 'B01', 'B02'],
            'last_visited_lesson' => '28-fs02-5-runtime-boundaries',
        ], JSON_THROW_ON_ERROR));

        $client = new ApplicationTestClient($root);
        $first = $client->request('GET', '/learn/lessons/29-fs03-1-components-jsx-and-typed-props');
        $lockedSecond = $client->request('GET', '/learn/lessons/30-fs03-2-state-and-events');
        $completeFirst = $client->request('POST', '/learn/lessons/29-fs03-1-components-jsx-and-typed-props/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token']);
        $second = $client->request('GET', '/learn/lessons/30-fs03-2-state-and-events');
        $completeSecond = $client->request('POST', '/learn/lessons/30-fs03-2-state-and-events/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token']);
        $third = $client->request('GET', '/learn/lessons/31-fs03-3-forms-and-state-design');
        $completeThird = $client->request('POST', '/learn/lessons/31-fs03-3-forms-and-state-design/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token']);
        $fourth = $client->request('GET', '/learn/lessons/32-fs03-4-tailwind-and-accessible-ui');
        $completeFourth = $client->request('POST', '/learn/lessons/32-fs03-4-tailwind-and-accessible-ui/complete', input: ['continue' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token']);
        $build = $client->request('GET', '/learn/fullstack/build/b03');
        $completeBuild = $client->request('POST', '/learn/fullstack/build/b03/complete', input: ['self_report' => '1'], server: ['HTTP_X_CSRF_TOKEN' => 'known-token'], session: ['_csrf' => 'known-token']);
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $journey = $client->request('GET', '/learn/fullstack');

        expect($first->statusCode)->toBe(200)
            ->and($first->body)->toContain('A component is a function with a capital letter')
            ->and($first->body)->toContain('Describe one project screen as a small set of components')
            ->and($lockedSecond->statusCode)->toBe(303)
            ->and($completeFirst->statusCode)->toBe(303)
            ->and($second->statusCode)->toBe(200)
            ->and($second->body)->toContain('Event handlers request the next state')
            ->and($completeSecond->statusCode)->toBe(303)
            ->and($third->statusCode)->toBe(200)
            ->and($third->body)->toContain('Controlled inputs')
            ->and($completeThird->statusCode)->toBe(303)
            ->and($fourth->statusCode)->toBe(200)
            ->and($fourth->body)->toContain('Semantics carry the interaction contract')
            ->and($completeFourth->statusCode)->toBe(303)
            ->and($build->statusCode)->toBe(200)
            ->and($build->body)->toContain('Build B03 · Part 03')
            ->and($build->body)->toContain('resources/app/')
            ->and($build->body)->toContain('fullstack-build')
            ->and($build->body)->toContain('Acceptance criteria')
            ->and($completeBuild->statusCode)->toBe(303)
            ->and($progress['completed_lessons'])->toContain('32-fs03-4-tailwind-and-accessible-ui')
            ->and($progress['completed_milestones'])->toBe(['B00', 'B01', 'B02', 'B03'])
            ->and($journey->body)->toContain('The local issue tracker')
            ->and($journey->body)->toContain('Planned material · not yet available');
    } finally {
        p05RemoveTree($root);
    }
});

test('the FS01.2 observation fixture exposes deterministic success, HTTP, and invalid-JSON boundaries', function () {
    $client = new ApplicationTestClient();
    $success = $client->request('GET', '/learn/fullstack/observe/async/issue-preview');
    $missing = $client->request('GET', '/learn/fullstack/observe/async/missing-issue');
    $invalidJson = $client->request('GET', '/learn/fullstack/observe/async/invalid-json');

    expect($success->statusCode)->toBe(200)
        ->and($success->body)->toBe('{"issue":{"id":17,"title":"Broken search","status":"open"}}')
        ->and($missing->statusCode)->toBe(404)
        ->and($missing->body)->toBe('{"error":"Issue preview not found."}')
        ->and($invalidJson->statusCode)->toBe(200)
        ->and($invalidJson->body)->toBe('This course fixture intentionally is not JSON.');
});

test('lesson completion persists independently from verification and drives resume', function () {
    $root = p05ProjectFixture();

    try {
        $client = new ApplicationTestClient($root);
        $opened = $client->request('GET', '/learn/lessons/06-docker-basics');
        expect($opened->statusCode)->toBe(200)
            ->and($opened->body)->toContain('Complete &amp; continue')
            ->and(json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR)['last_visited_lesson'])
            ->toBe('06-docker-basics');

        $complete = $client->request(
            'POST',
            '/learn/lessons/06-docker-basics/complete',
            input: ['continue' => '1'],
            server: ['HTTP_X_CSRF_TOKEN' => 'known-token'],
            session: ['_csrf' => 'known-token'],
        );
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);
        $dashboard = $client->request('GET', '/learn');
        $track = $client->request('GET', '/learn/tracks/docker');

        expect($complete->statusCode)->toBe(303)
            ->and($progress['completed_lessons'])->toContain('06-docker-basics')
            ->and($progress['passed'])->toBe([])
            ->and($dashboard->body)->toContain('Writing Dockerfiles')
            ->and($track->body)->toContain('✓ Completed');
    } finally {
        p05RemoveTree($root);
    }
});

test('legacy passed progress remains effective and a completed curriculum has no lesson-one fallback', function () {
    $root = p05ProjectFixture();

    try {
        file_put_contents($root . '/.dalt/progress.json', json_encode(['passed' => ['broken-routing']], JSON_THROW_ON_ERROR));
        $client = new ApplicationTestClient($root);
        $legacy = $client->request('GET', '/learn/tracks/foundation');
        expect($legacy->body)->toContain('✓ Verified');

        $lessonIds = array_column(\Core\CourseLoader::getLessons(), 'id');
        file_put_contents($root . '/.dalt/progress.json', json_encode([
            'passed' => [],
            'completed_lessons' => $lessonIds,
            'last_visited_lesson' => '17-observability',
        ], JSON_THROW_ON_ERROR));
        $complete = $client->request('GET', '/learn');
        expect($complete->body)->toContain('All lessons complete')
            ->and($complete->body)->not->toContain('href="/learn/lessons/01-request-lifecycle" class="mt-6');
    } finally {
        p05RemoveTree($root);
    }
});

test('verification requires csrf and maps unknown and inactive challenges', function () {
    $client = new ApplicationTestClient();
    $missingToken = $client->request('POST', '/api/verify/broken-routing');
    $unknown = $client->request(
        'POST',
        '/api/verify/not-real',
        server: ['HTTP_X_CSRF_TOKEN' => 'known-token'],
        session: ['_csrf' => 'known-token'],
    );
    $inactive = $client->request(
        'POST',
        '/api/verify/broken-routing',
        server: ['HTTP_X_CSRF_TOKEN' => 'known-token'],
        session: ['_csrf' => 'known-token'],
    );

    expect($missingToken->statusCode)->toBe(419)
        ->and($missingToken->body)->toBe('CSRF token mismatch')
        ->and($unknown->statusCode)->toBe(404)
        ->and(json_decode($unknown->body, true, 512, JSON_THROW_ON_ERROR)['status'])->toBe('not_found')
        ->and($inactive->statusCode)->toBe(409)
        ->and(json_decode($inactive->body, true, 512, JSON_THROW_ON_ERROR)['status'])->toBe('not_loaded');
});

test('browser verification records progress only after a real pass', function () {
    $root = p05ProjectFixture();

    try {
        expect(p05Manager($root, 'start', 'broken-routing')['result'])->toBeTrue();
        $client = new ApplicationTestClient($root);
        $activeDashboard = $client->request('GET', '/learn');
        $activeResources = $client->request('GET', '/learn/resources');
        $activeChallenge = $client->request('GET', '/learn/challenges/broken-routing');
        expect($activeDashboard->body)->toContain('Active')
            ->and($activeDashboard->body)->toContain('Continue')
            ->and($activeResources->body)->toContain('Active')
            ->and($activeResources->body)->toContain('Continue')
            ->and($activeChallenge->body)->toContain('Status')
            ->and($activeChallenge->body)->toContain('Active');

        $request = fn () => $client->request(
            'POST',
            '/api/verify/broken-routing',
            server: ['HTTP_X_CSRF_TOKEN' => 'known-token'],
            session: ['_csrf' => 'known-token'],
        );

        $failed = $request();
        $failedData = json_decode($failed->body, true, 512, JSON_THROW_ON_ERROR);
        expect($failed->statusCode)->toBe(200)
            ->and($failedData['status'])->toBe('fail')
            ->and($failedData['tests'][0]['message'])->not->toBeEmpty()
            ->and(file_exists($root . '/.dalt/progress.json'))->toBeFalse();

        file_put_contents($root . '/routes/routes.php', <<<'PHP'
<?php
global $router;
$router->get('/posts/create', 'posts/create.php');
$router->get('/posts/{id}', 'posts/show.php');
$router->get('/posts/{id}/edit', 'posts/edit.php');
PHP);

        $passed = $request();
        $passedData = json_decode($passed->body, true, 512, JSON_THROW_ON_ERROR);
        $progress = json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR);

        expect($passed->statusCode)->toBe(200)
            ->and($passedData['status'])->toBe('pass')
            ->and($passedData['success'])->toBeTrue()
            ->and($progress)->toBe([
                'passed' => ['broken-routing'],
                'completed_lessons' => ['02-routing'],
                'completed_milestones' => [],
                'last_visited_lesson' => null,
            ]);

        $repeat = $request();
        expect($repeat->statusCode)->toBe(200)
            ->and(json_decode($repeat->body, true, 512, JSON_THROW_ON_ERROR)['status'])->toBe('pass')
            ->and(json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR))
            ->toBe([
                'passed' => ['broken-routing'],
                'completed_lessons' => ['02-routing'],
                'completed_milestones' => [],
                'last_visited_lesson' => null,
            ]);

        expect(p05Manager($root, 'stop')['result'])->toBeTrue();
        $completedDashboard = $client->request('GET', '/learn/resources');
        expect($completedDashboard->body)->toContain('Completed')
            ->and($completedDashboard->body)->toContain('Review');

        file_put_contents($root . '/.dalt/course/challenges/broken-routing/meta.json', '{broken');
        $internalError = $request();
        $internalData = json_decode($internalError->body, true, 512, JSON_THROW_ON_ERROR);
        expect($internalError->statusCode)->toBe(500)
            ->and($internalData['status'])->toBe('error')
            ->and($internalData['message'])->toBe('Verification could not be completed. Check the application log and try again.')
            ->and($internalData['message'])->not->toContain('Syntax error');
    } finally {
        p05Manager($root, 'stop');
        p05RemoveTree($root);
    }
});

test('broken-session challenge demonstrates flash precedence and request-start expiry', function () {
    $root = p05ProjectFixture();

    try {
        p05RemoveTree($root . '/vendor');
        mkdir($root . '/vendor', 0700, true);
        $baseAutoload = var_export(base_path('vendor/autoload.php'), true);
        $projectRoot = var_export($root, true);
        file_put_contents($root . '/vendor/autoload.php', <<<PHP
<?php
require {$baseAutoload};
\$projectRoot = {$projectRoot};
spl_autoload_register(static function (string \$class) use (\$projectRoot): void {
    if (!str_starts_with(\$class, 'Core' . chr(92))) {
        return;
    }
    \$relative = substr(\$class, 5);
    foreach ([
        \$projectRoot . '/framework/Core/' . str_replace(chr(92), '/', \$relative) . '.php',
        \$projectRoot . '/.dalt/Core/' . str_replace(chr(92), '/', \$relative) . '.php',
    ] as \$path) {
        if (is_file(\$path)) {
            require \$path;
            return;
        }
    }
}, true, true);
PHP);

        expect(p05Manager($root, 'start', 'broken-session')['result'])->toBeTrue();

        $client = new ApplicationTestClient($root);
        $broken = $client->request('GET', '/contact/precedence');
        expect($broken->statusCode)->toBe(200)
            ->and($broken->body)->toContain('<p id="probe-value">persistent value</p>');

        copy(base_path('framework/Core/Session.php'), $root . '/framework/Core/Session.php');

        $fixed = $client->request('GET', '/contact/precedence');
        expect($fixed->statusCode)->toBe(200)
            ->and($fixed->body)->toContain('<p id="probe-value">flash value</p>');

        $next = $client->request(
            'GET',
            '/contact/success',
            session: ['_flash' => ['new' => ['success' => 'Message sent successfully!']]],
        );
        $expired = $client->request(
            'GET',
            '/contact/success',
            session: ['_flash' => ['old' => ['success' => 'Message sent successfully!']]],
        );

        expect($next->body)->toContain('Message sent successfully!')
            ->and($expired->body)->toContain('No success message!');
    } finally {
        p05Manager($root, 'stop');
        p05RemoveTree($root);
    }
});
