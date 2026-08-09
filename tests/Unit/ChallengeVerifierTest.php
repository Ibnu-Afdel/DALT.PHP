<?php

declare(strict_types=1);

use Core\ChallengeVerifier;

function verifierFixture(): string
{
    $root = sys_get_temp_dir() . '/dalt-p04-' . bin2hex(random_bytes(6));
    mkdir($root . '/.dalt/course/challenges/example', 0700, true);

    return $root;
}

function removeVerifierFixture(string $path): void
{
    if (!file_exists($path) && !is_link($path)) {
        return;
    }
    if (is_dir($path) && !is_link($path)) {
        foreach (new FilesystemIterator($path) as $entry) {
            removeVerifierFixture($entry->getPathname());
        }
        rmdir($path);
        return;
    }
    unlink($path);
}

function writeVerifierFixture(string $root, string $relative, string $contents): void
{
    $path = $root . '/' . $relative;
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0700, true);
    }
    file_put_contents($path, $contents);
}

/** @param array<string, array<string, mixed>> $tests */
function writeVerifierTests(string $root, array $tests): void
{
    writeVerifierFixture(
        $root,
        '.dalt/course/challenges/example/tests.php',
        "<?php\nreturn " . var_export($tests, true) . ";\n",
    );
}

test('base verification maps controller paths and ignores PHP comment decoys', function () {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/Http/controllers/posts/index.php', '<?php');
    writeVerifierFixture($root, 'app/Http/controllers/posts/index.php', <<<'PHP'
<?php
// password_verify($password, $hash);
$note = 'password_verify is not a call';
password_verify($password, $hash);
PHP);
    writeVerifierTests($root, [
        'has_real_call' => [
            'type' => 'function_call',
            'file' => 'Http/controllers/posts/index.php',
            'function' => 'password_verify',
            'hint' => 'Call password_verify.',
        ],
        'ignores_comment_text' => [
            'type' => 'file_not_contains',
            'file' => 'Http/controllers/posts/index.php',
            'search' => '// password_verify',
            'hint' => 'Comments are not code.',
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', true, $root))->verify();

        expect($result['status'])->toBe('pass')
            ->and($result['passed'])->toBe(2)
            ->and($result['total'])->toBe(2);
    } finally {
        removeVerifierFixture($root);
    }
});

test('function session and route checks require real uncommented PHP tokens', function () {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/framework/Core/Probe.php', <<<'PHP'
<?php
// hash_equals($left, $right);
$text = "\$_SESSION['user']";
function hash_equals_decoy() {}
PHP);
    writeVerifierFixture($root, '.dalt/course/challenges/example/routes/routes.php', <<<'PHP'
<?php
// $router->get('/posts/create', 'create.php');
$router->get('/posts/{id}', 'show.php');
PHP);
    writeVerifierTests($root, [
        'real_function_call' => ['type' => 'function_call', 'file' => 'framework/Core/Probe.php', 'function' => 'hash_equals'],
        'real_session_access' => ['type' => 'session_key', 'file' => 'framework/Core/Probe.php', 'key' => 'user'],
        'real_route' => ['type' => 'route_exists', 'route' => '/posts/create', 'method' => 'get'],
        'real_order' => ['type' => 'route_order', 'specific' => '/posts/create', 'generic' => '/posts/{id}'],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('fail')
            ->and($result['passed'])->toBe(0)
            ->and($result['failed'])->toBe(4);
    } finally {
        removeVerifierFixture($root);
    }
});

test('comment checks are explicit and verification is repeatable', function () {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/Dockerfile', "FROM php:8.4\n# TODO finish\n");
    writeVerifierTests($root, [
        'todo_is_still_visible' => [
            'type' => 'file_not_contains',
            'file' => 'Dockerfile',
            'search' => '# TODO',
            'include_comments' => true,
            'hint' => 'Remove the TODO.',
        ],
    ]);

    try {
        $verifier = new ChallengeVerifier('.dalt/course/challenges/example', false, $root);
        $first = $verifier->verify();
        $second = $verifier->verify();

        expect($first)->toBe($second)
            ->and($first['status'])->toBe('fail')
            ->and($first['hint'])->toBe('Remove the TODO.')
            ->and($first['total'])->toBe(1);
    } finally {
        removeVerifierFixture($root);
    }
});

test('missing learner targets fail without becoming verifier configuration errors', function () {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/Dockerfile', 'FROM php');
    writeVerifierTests($root, [
        'required_file' => [
            'type' => 'file_contains',
            'file' => 'Dockerfile',
            'search' => 'FROM',
            'hint' => 'Restore the Dockerfile.',
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', true, $root))->verify();

        expect($result['status'])->toBe('fail')
            ->and($result['results'][0]['message'])->toContain('does not exist')
            ->and($result['hint'])->toBe('Restore the Dockerfile.');
    } finally {
        removeVerifierFixture($root);
    }
});

test('empty malformed and unsafe specifications return actionable errors', function (string $case) {
    $root = verifierFixture();

    if ($case === 'empty') {
        writeVerifierTests($root, []);
    } elseif ($case === 'unknown type') {
        writeVerifierTests($root, ['bad' => ['type' => 'shell_command']]);
    } elseif ($case === 'unsafe target') {
        writeVerifierTests($root, ['bad' => ['type' => 'file_contains', 'file' => '../.env', 'search' => 'SECRET']]);
    } elseif ($case === 'output') {
        writeVerifierFixture($root, '.dalt/course/challenges/example/tests.php', '<?php echo "leak"; return ["valid" => []];');
    } else {
        writeVerifierFixture($root, '.dalt/course/challenges/example/tests.php', '<?php return "not an array";');
    }

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('error')
            ->and($result['total'])->toBe(0)
            ->and($result['message'])->toContain('Verification configuration error');
    } finally {
        removeVerifierFixture($root);
    }
})->with(['empty', 'unknown type', 'unsafe target', 'output', 'non-array']);

test('unsafe challenge paths and linked targets are rejected', function () {
    $root = verifierFixture();
    writeVerifierTests($root, [
        'linked' => ['type' => 'file_contains', 'file' => 'Dockerfile', 'search' => 'FROM'],
    ]);
    writeVerifierFixture($root, 'outside', 'FROM php');
    symlink($root . '/outside', $root . '/.dalt/course/challenges/example/Dockerfile');

    try {
        expect(fn () => new ChallengeVerifier('../example', false, $root))
            ->toThrow(RuntimeException::class, 'safe catalog path');

        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();
        expect($result['status'])->toBe('error')
            ->and($result['message'])->toContain('regular file');
    } finally {
        removeVerifierFixture($root);
    }
});

test('verification logging rejects untrusted result and challenge fields', function () {
    expect(fn () => ChallengeVerifier::logResult("example\nforged", ['status' => 'pass', 'passed' => 1, 'total' => 1]))
        ->toThrow(RuntimeException::class, 'invalid challenge verification result')
        ->and(fn () => ChallengeVerifier::logResult('example', ['status' => "pass\nforged", 'passed' => 1, 'total' => 1]))
        ->toThrow(RuntimeException::class, 'invalid challenge verification result')
        ->and(fn () => ChallengeVerifier::logResult('example', ['status' => 'pass', 'passed' => 2, 'total' => 1]))
        ->toThrow(RuntimeException::class, 'invalid challenge verification result');
});

test('every shipped specification is valid and rejects its broken source fixture', function () {
    $root = base_path();
    $directories = glob(base_path('.dalt/course/challenges/*'), GLOB_ONLYDIR) ?: [];
    sort($directories, SORT_STRING);
    $total = 0;

    foreach ($directories as $directory) {
        $id = basename($directory);
        $result = (new ChallengeVerifier(".dalt/course/challenges/{$id}", false, $root))->verify();
        $total += $result['total'];

        expect($result['status'], $id)->toBe('fail')
            ->and($result['failed'], $id)->toBeGreaterThan(0);
    }

    expect($directories)->toHaveCount(20)
        ->and($total)->toBe(83);
});
