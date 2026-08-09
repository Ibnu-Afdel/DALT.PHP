<?php

declare(strict_types=1);

use Core\ChallengeManager;
use Core\ChallengeStateException;

function challengeFixture(): string
{
    $root = sys_get_temp_dir() . '/dalt-p03-' . bin2hex(random_bytes(6));
    mkdir($root . '/.dalt/course/challenges', 0700, true);

    return $root;
}

function removeChallengeFixture(string $path): void
{
    if (!file_exists($path) && !is_link($path)) {
        return;
    }
    if (is_dir($path) && !is_link($path)) {
        foreach (new FilesystemIterator($path) as $entry) {
            removeChallengeFixture($entry->getPathname());
        }
        rmdir($path);
        return;
    }
    unlink($path);
}

function writeChallengeFixture(string $root, string $relative, string $contents): void
{
    $path = $root . '/' . $relative;
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0700, true);
    }
    file_put_contents($path, $contents);
}

/** @return array{exitCode: int, ok: bool, result?: mixed, type?: string, message?: string} */
function runChallengeManager(string $root, string $action, string $argument = ''): array
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
        throw new RuntimeException('Unable to start challenge manager probe.');
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

test('start and stop restore only the transaction file set exactly', function () {
    $root = challengeFixture();
    writeChallengeFixture($root, 'routes/routes.php', 'original routes');
    chmod($root . '/routes/routes.php', 0640);
    writeChallengeFixture($root, 'notes.txt', 'learner-owned');
    writeChallengeFixture($root, '.dalt/baseline/routes/routes.php', 'stale shared backup');
    writeChallengeFixture($root, '.dalt/course/challenges/example/routes/routes.php', 'broken routes');
    writeChallengeFixture($root, '.dalt/course/challenges/example/Http/controllers/posts/index.php', 'broken controller');
    writeChallengeFixture($root, '.dalt/course/challenges/example/README.md', 'content only');
    writeChallengeFixture($root, '.dalt/course/challenges/example/meta.json', '{}');
    writeChallengeFixture($root, '.dalt/course/challenges/example/tests.php', '<?php');

    try {
        $started = runChallengeManager($root, 'start', 'example');
        expect($started['exitCode'])->toBe(0)
            ->and($started['result'])->toBeTrue()
            ->and(file_get_contents($root . '/routes/routes.php'))->toBe('broken routes')
            ->and(file_get_contents($root . '/app/Http/controllers/posts/index.php'))->toBe('broken controller')
            ->and(file_exists($root . '/README.md'))->toBeFalse();

        file_put_contents($root . '/routes/routes.php', 'learner solution');
        file_put_contents($root . '/notes.txt', 'learner-owned changed outside challenge');
        $stopped = runChallengeManager($root, 'stop');

        expect($stopped['exitCode'])->toBe(0)
            ->and(file_get_contents($root . '/routes/routes.php'))->toBe('original routes')
            ->and(fileperms($root . '/routes/routes.php') & 0777)->toBe(0640)
            ->and(file_get_contents($root . '/notes.txt'))->toBe('learner-owned changed outside challenge')
            ->and(file_exists($root . '/app/Http/controllers/posts/index.php'))->toBeFalse()
            ->and(file_exists($root . '/app/Http/controllers/posts'))->toBeFalse()
            ->and(file_exists($root . '/.dalt/challenge-state.json'))->toBeFalse()
            ->and(file_exists($root . '/.dalt/challenge-backup'))->toBeFalse()
            ->and(file_get_contents($root . '/.dalt/baseline/routes/routes.php'))->toBe('stale shared backup');
    } finally {
        removeChallengeFixture($root);
    }
});

test('reset reapplies the broken files without replacing the original snapshot', function () {
    $root = challengeFixture();
    writeChallengeFixture($root, 'Dockerfile', 'original');
    writeChallengeFixture($root, '.dalt/course/challenges/container/Dockerfile', 'broken');

    try {
        runChallengeManager($root, 'start', 'container');
        file_put_contents($root . '/Dockerfile', 'attempted fix');

        expect(runChallengeManager($root, 'reset')['result'])->toBeTrue()
            ->and(file_get_contents($root . '/Dockerfile'))->toBe('broken');

        runChallengeManager($root, 'stop');
        expect(file_get_contents($root . '/Dockerfile'))->toBe('original');
    } finally {
        removeChallengeFixture($root);
    }
});

test('a second challenge cannot replace an active transaction', function () {
    $root = challengeFixture();
    writeChallengeFixture($root, 'routes/routes.php', 'original');
    writeChallengeFixture($root, '.dalt/course/challenges/first/routes/routes.php', 'first broken');
    writeChallengeFixture($root, '.dalt/course/challenges/second/routes/routes.php', 'second broken');

    try {
        runChallengeManager($root, 'start', 'first');
        $second = runChallengeManager($root, 'start', 'second');

        expect($second['exitCode'])->toBe(1)
            ->and($second['type'])->toBe(ChallengeStateException::class)
            ->and($second['message'])->toContain("'first' is already active")
            ->and(file_get_contents($root . '/routes/routes.php'))->toBe('first broken')
            ->and(runChallengeManager($root, 'active')['result'])->toBe('first');
    } finally {
        runChallengeManager($root, 'stop');
        removeChallengeFixture($root);
    }
});

test('path-like IDs and files outside the destination allowlist cannot mutate the project', function () {
    $root = challengeFixture();
    writeChallengeFixture($root, '.dalt/course/challenges/unsafe/public/index.php', 'pwned');

    try {
        $traversal = runChallengeManager($root, 'start', '../unsafe');
        $unsafe = runChallengeManager($root, 'start', 'unsafe');

        expect($traversal['exitCode'])->toBe(0)
            ->and($traversal['result'])->toBeFalse()
            ->and($unsafe['exitCode'])->toBe(1)
            ->and($unsafe['message'])->toContain('mutable allowlist')
            ->and(file_exists($root . '/public/index.php'))->toBeFalse()
            ->and(file_exists($root . '/.dalt/challenge-state.json'))->toBeFalse();
    } finally {
        removeChallengeFixture($root);
    }
});

test('source and destination symbolic links are rejected without touching their targets', function () {
    if (!function_exists('symlink')) {
        $this->markTestSkipped('Symbolic links are unavailable.');
    }

    $root = challengeFixture();
    $outside = $root . '-outside';
    mkdir($outside, 0700);
    file_put_contents($outside . '/target.php', 'outside');
    mkdir($root . '/.dalt/course/challenges/source-link/routes', 0700, true);
    symlink($outside . '/target.php', $root . '/.dalt/course/challenges/source-link/routes/routes.php');
    writeChallengeFixture($root, '.dalt/course/challenges/destination-link/Http/controllers/posts/index.php', 'broken');
    mkdir($root . '/app/Http/controllers', 0700, true);
    symlink($outside, $root . '/app/Http/controllers/posts');

    try {
        $source = runChallengeManager($root, 'start', 'source-link');
        $destination = runChallengeManager($root, 'start', 'destination-link');

        expect($source['exitCode'])->toBe(1)
            ->and($source['message'])->toContain('symbolic links')
            ->and($destination['exitCode'])->toBe(1)
            ->and($destination['message'])->toContain('symbolic-link directory')
            ->and(file_get_contents($outside . '/target.php'))->toBe('outside');
    } finally {
        removeChallengeFixture($root);
        removeChallengeFixture($outside);
    }
});

test('hard-linked challenge sources are rejected before mutation', function () {
    if (!function_exists('link')) {
        $this->markTestSkipped('Hard links are unavailable.');
    }

    $root = challengeFixture();
    writeChallengeFixture($root, 'outside-source', 'broken');
    mkdir($root . '/.dalt/course/challenges/hard-link', 0700, true);
    if (!link($root . '/outside-source', $root . '/.dalt/course/challenges/hard-link/Dockerfile')) {
        removeChallengeFixture($root);
        $this->markTestSkipped('The fixture filesystem does not permit hard links.');
    }

    try {
        $result = runChallengeManager($root, 'start', 'hard-link');
        expect($result['exitCode'])->toBe(1)
            ->and($result['message'])->toContain('hard-link count')
            ->and(file_exists($root . '/Dockerfile'))->toBeFalse();
    } finally {
        removeChallengeFixture($root);
    }
});

test('reset refuses a changed active challenge file set', function () {
    $root = challengeFixture();
    writeChallengeFixture($root, 'Dockerfile', 'original');
    writeChallengeFixture($root, '.dalt/course/challenges/changing/Dockerfile', 'broken');

    try {
        runChallengeManager($root, 'start', 'changing');
        writeChallengeFixture($root, '.dalt/course/challenges/changing/docker-compose.yml', 'new source');
        $reset = runChallengeManager($root, 'reset');

        expect($reset['exitCode'])->toBe(1)
            ->and($reset['message'])->toContain('file set changed')
            ->and(file_exists($root . '/docker-compose.yml'))->toBeFalse();
        runChallengeManager($root, 'stop');
        expect(file_get_contents($root . '/Dockerfile'))->toBe('original');
    } finally {
        removeChallengeFixture($root);
    }
});

test('a prepared recovery manifest remains stoppable after interruption', function () {
    $root = challengeFixture();
    writeChallengeFixture($root, 'docker-compose.yml', 'original');
    writeChallengeFixture($root, '.dalt/course/challenges/interrupted/docker-compose.yml', 'broken');

    try {
        runChallengeManager($root, 'start', 'interrupted');
        $statePath = $root . '/.dalt/challenge-state.json';
        $state = json_decode(file_get_contents($statePath), true, 512, JSON_THROW_ON_ERROR);
        $state['phase'] = 'prepared';
        file_put_contents($statePath, json_encode($state, JSON_THROW_ON_ERROR));
        unlink($root . '/.dalt/active_challenge.txt');
        file_put_contents($root . '/docker-compose.yml', 'partial mutation');

        expect(runChallengeManager($root, 'active')['result'])->toBe('interrupted')
            ->and(runChallengeManager($root, 'stop')['result'])->toBeTrue()
            ->and(file_get_contents($root . '/docker-compose.yml'))->toBe('original');
    } finally {
        removeChallengeFixture($root);
    }
});

test('a tampered recovery manifest cannot redirect restoration outside its transaction', function () {
    $root = challengeFixture();
    writeChallengeFixture($root, 'routes/routes.php', 'original');
    writeChallengeFixture($root, 'protected.txt', 'preserved');
    writeChallengeFixture($root, '.dalt/course/challenges/tamper/routes/routes.php', 'broken');

    try {
        runChallengeManager($root, 'start', 'tamper');
        $statePath = $root . '/.dalt/challenge-state.json';
        $state = json_decode(file_get_contents($statePath), true, 512, JSON_THROW_ON_ERROR);
        $validState = $state;
        $state['entries'][0]['backup'] = '.dalt/challenge-backup/files/routes/../../../protected.txt';
        file_put_contents($statePath, json_encode($state, JSON_THROW_ON_ERROR));

        $stopped = runChallengeManager($root, 'stop');
        expect($stopped['exitCode'])->toBe(1)
            ->and($stopped['message'])->toContain('invalid file entry')
            ->and(file_get_contents($root . '/protected.txt'))->toBe('preserved')
            ->and(file_get_contents($root . '/routes/routes.php'))->toBe('broken');

        file_put_contents($statePath, json_encode($validState, JSON_THROW_ON_ERROR));
        expect(runChallengeManager($root, 'stop')['result'])->toBeTrue()
            ->and(file_get_contents($root . '/routes/routes.php'))->toBe('original');
    } finally {
        removeChallengeFixture($root);
    }
});

test('challenge mutations wait for the project operation lock', function () {
    $root = challengeFixture();
    writeChallengeFixture($root, '.dalt/course/challenges/locked/Dockerfile', 'broken');
    $lock = fopen($root . '/.dalt/challenge.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Unable to hold fixture challenge lock.');
    }

    $process = proc_open(
        [PHP_BINARY, base_path('tests/Support/run-challenge-manager.php'), $root, 'start', 'locked'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
        null,
        ['bypass_shell' => true],
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start locked challenge manager probe.');
    }
    fclose($pipes[0]);

    try {
        usleep(100_000);
        expect(proc_get_status($process)['running'])->toBeTrue();
        flock($lock, LOCK_UN);
        fclose($lock);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        expect(proc_close($process))->toBe(0)
            ->and($stderr)->toBe('')
            ->and(json_decode($stdout, true, 512, JSON_THROW_ON_ERROR)['result'])->toBeTrue();
        runChallengeManager($root, 'stop');
    } finally {
        if (is_resource($lock)) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        if (is_resource($process)) {
            proc_terminate($process);
            proc_close($process);
        }
        removeChallengeFixture($root);
    }
});

test('progress writes are idempotent and malformed state fails visibly', function () {
    $root = challengeFixture();

    try {
        runChallengeManager($root, 'mark', 'first-challenge');
        runChallengeManager($root, 'mark', 'first-challenge');
        expect(runChallengeManager($root, 'passed')['result'])->toBe(['first-challenge']);

        file_put_contents($root . '/.dalt/progress.json', '{broken');
        $malformed = runChallengeManager($root, 'passed');
        expect($malformed['exitCode'])->toBe(1)
            ->and($malformed['message'])->toContain('malformed JSON');
    } finally {
        removeChallengeFixture($root);
    }
});

test('every shipped challenge mutable file satisfies the P03 allowlist', function () {
    $method = new ReflectionMethod(ChallengeManager::class, 'buildPlan');
    foreach (ChallengeManager::listChallenges() as $challenge) {
        $plan = $method->invoke(null, base_path('.dalt/course/challenges/' . $challenge));
        expect($plan)->not->toBeEmpty();
    }
});
