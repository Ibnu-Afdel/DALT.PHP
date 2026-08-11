<?php

declare(strict_types=1);

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
        [PHP_BINARY, base_path('tests/Support/run-challenge-manager.php'), $root, $action, $argument],
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
    $lesson = $client->request('GET', '/learn/lessons/11-dalt-db-layer');
    $challenge = $client->request('GET', '/learn/challenges/db-missing-pagination');

    expect($index->statusCode)->toBe(200)
        ->and($index->body)->toContain('Skip to main content')
        ->and($lesson->statusCode)->toBe(200)
        ->and($lesson->body)->toContain('Recommended prerequisites')
        ->and($lesson->body)->toContain('markdown-fallback')
        ->and($lesson->body)->toContain('##')
        ->and($challenge->statusCode)->toBe(200)
        ->and($challenge->body)->toContain('Browser verification needs JavaScript')
        ->and($challenge->body)->toContain('php artisan challenge:verify')
        ->and($challenge->body)->toContain('markdown-fallback')
        ->and($challenge->body)->toContain('meta name="csrf-token"');
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
        $activeChallenge = $client->request('GET', '/learn/challenges/broken-routing');
        expect($activeDashboard->body)->toContain('Active')
            ->and($activeDashboard->body)->toContain('Continue')
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
            ->and($progress)->toBe(['passed' => ['broken-routing']]);

        $repeat = $request();
        expect($repeat->statusCode)->toBe(200)
            ->and(json_decode($repeat->body, true, 512, JSON_THROW_ON_ERROR)['status'])->toBe('pass')
            ->and(json_decode(file_get_contents($root . '/.dalt/progress.json'), true, 512, JSON_THROW_ON_ERROR))
            ->toBe(['passed' => ['broken-routing']]);

        expect(p05Manager($root, 'stop')['result'])->toBeTrue();
        $completedDashboard = $client->request('GET', '/learn');
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
