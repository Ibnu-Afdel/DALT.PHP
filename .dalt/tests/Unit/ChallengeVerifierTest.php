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

function copyTree(string $source, string $destination): void
{
    mkdir($destination, 0700, true);
    foreach (new FilesystemIterator($source) as $entry) {
        $target = $destination . '/' . $entry->getFilename();
        if ($entry->isDir()) {
            copyTree($entry->getPathname(), $target);
        } else {
            copy($entry->getPathname(), $target);
        }
    }
}

test('the broken-session specification rejects its fixture and accepts the verified base session', function () {
    $path = '.dalt/course/challenges/broken-session';
    $challenge = base_path($path);

    $broken = (new ChallengeVerifier($path, false))->verify();

    // The handler_result checks need Http/controllers/contact/{precedence,success}.php
    // in place under app/ — challenge-only demo routes the tracked base skeleton
    // does not (and should not) ship — so simulate what challenge:start would
    // have copied in, against a real, correctly-fixed framework/Core/Session.php.
    // framework is copied rather than symlinked: verification walks every parent
    // directory of a target and refuses a symbolic link anywhere in that chain.
    $root = verifierFixture();
    symlink(base_path('vendor'), $root . '/vendor');
    copyTree(base_path('framework'), $root . '/framework');
    mkdir($root . '/app/Http/controllers/contact', 0700, true);
    foreach (['precedence.php', 'success.php'] as $controller) {
        copy(
            $challenge . '/Http/controllers/contact/' . $controller,
            $root . '/app/Http/controllers/contact/' . $controller,
        );
    }
    copyTree($challenge, $root . '/.dalt/course/challenges/broken-session');

    try {
        $fixed = (new ChallengeVerifier($path, true, $root))->verify();

        expect($broken['status'])->toBe('fail')
            ->and($broken['failed'])->toBeGreaterThan(0)
            ->and($fixed['status'])->toBe('pass')
            ->and($fixed['failed'])->toBe(0)
            ->and($fixed['total'])->toBe(9);
    } finally {
        removeVerifierFixture($root);
    }
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

    // A floor, not an exact count. Adding checks to a challenge is the change we
    // want people making freely; an exact total turns every such edit into an
    // unrelated test failure. The floor still catches silent deletion.
    expect($directories)->toHaveCount(20)
        ->and($total)->toBeGreaterThanOrEqual(95);
});

test('class contract checks load the learner class and report what it really declares', function (
    string $body,
    bool $expectedPass,
    string $expectedFragment,
) {
    $root = verifierFixture();
    writeVerifierFixture($root, 'vendor/autoload.php', '<?php');
    writeVerifierFixture($root, '.dalt/course/challenges/example/framework/Core/Widget.php', $body);
    writeVerifierTests($root, [
        'widget_contract' => [
            'type' => 'class_contract',
            'file' => 'framework/Core/Widget.php',
            'class' => 'Probe\Widget',
            'implements' => ['Probe\WidgetContract'],
            'methods' => ['handle'],
            'hint' => 'Implement the contract.',
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe($expectedPass ? 'pass' : 'fail')
            ->and($result['total'])->toBe(1)
            ->and($result['results'][0]['message'])->toContain($expectedFragment);
    } finally {
        removeVerifierFixture($root);
    }
})->with([
    'satisfies the contract' => [
        "<?php\nnamespace Probe;\ninterface WidgetContract { public function handle(); }\nclass Widget implements WidgetContract { public function handle() {} }\n",
        true,
        'satisfies the expected contract',
    ],
    'implements nothing' => [
        "<?php\nnamespace Probe;\ninterface WidgetContract { public function handle(); }\nclass Widget { public function handle() {} }\n",
        false,
        'does not implement',
    ],
    'drops a required method' => [
        "<?php\nnamespace Probe;\ninterface WidgetContract {}\nclass Widget implements WidgetContract { public function other() {} }\n",
        false,
        'missing the method(s): handle',
    ],
    'does not parse' => [
        "<?php\nnamespace Probe;\nclass Widget { public function handle( {}\n",
        false,
        'failed outright',
    ],
    'declares the wrong name' => [
        "<?php\nnamespace Probe;\ninterface WidgetContract {}\nclass Gadget implements WidgetContract { public function handle() {} }\n",
        false,
        'does not declare',
    ],
]);

test('class contract checks refuse targets that execute on require', function () {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/Http/controllers/posts/index.php', '<?php');
    writeVerifierTests($root, [
        'controller_contract' => [
            'type' => 'class_contract',
            'file' => 'Http/controllers/posts/index.php',
            'class' => 'Probe\Widget',
            'methods' => ['handle'],
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('error')
            ->and($result['message'])->toContain('may only inspect a framework/Core class file');
    } finally {
        removeVerifierFixture($root);
    }
});

test('handler result checks execute the controller and judge what it returned', function (
    string $body,
    bool $expectedPass,
    string $expectedFragment,
) {
    $root = base_path();
    $dir = sys_get_temp_dir() . '/dalt-hr-' . bin2hex(random_bytes(6));
    mkdir($dir . '/.dalt/course/challenges/example/Http/controllers/db/posts', 0700, true);
    symlink($root . '/vendor', $dir . '/vendor');
    symlink($root . '/framework', $dir . '/framework');
    file_put_contents($dir . '/.dalt/course/challenges/example/Http/controllers/db/posts/index.php', $body);
    writeVerifierTests($dir, [
        'returns_every_post_with_author' => [
            'type' => 'handler_result',
            'file' => 'Http/controllers/db/posts/index.php',
            'seed' => [
                'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)',
                'CREATE TABLE posts (id INTEGER PRIMARY KEY, user_id INT, title TEXT)',
                "INSERT INTO users VALUES (5, 'Alice')",
                "INSERT INTO posts VALUES (1, 5, 'first')",
            ],
            'expect' => ['status' => 200, 'count' => 1, 'contains' => 'Alice'],
            'hint' => 'Join on the foreign key.',
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $dir))->verify();

        expect($result['status'])->toBe($expectedPass ? 'pass' : 'fail')
            ->and($result['results'][0]['message'])->toContain($expectedFragment);
    } finally {
        removeVerifierFixture($dir);
    }
})->with([
    'correct join' => [
        "<?php\n\$db = \\Core\\App::resolve(\\Core\\Database::class);\nreturn \$db->query('SELECT posts.id, users.name AS author FROM posts LEFT JOIN users ON posts.user_id = users.id')->get();\n",
        true,
        'expected response',
    ],
    'dead code carrying the right words' => [
        "<?php\n\$db = \\Core\\App::resolve(\\Core\\Database::class);\n\$unused = 'LEFT JOIN users ON posts.user_id = users.id';\nreturn \$db->query('SELECT posts.id, users.name AS author FROM posts JOIN users ON posts.id = users.id')->get();\n",
        false,
        "missing 'Alice'",
    ],
    'handler that throws' => [
        "<?php\nthrow new RuntimeException('boom');\n",
        false,
        'handler threw',
    ],
]);

test('handler result checks refuse non-controller targets and malformed expectations', function (array $check, string $fragment) {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/framework/Core/Widget.php', '<?php');
    writeVerifierFixture($root, '.dalt/course/challenges/example/Http/controllers/posts/index.php', '<?php');
    writeVerifierTests($root, ['bad' => $check]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('error')
            ->and($result['message'])->toContain($fragment);
    } finally {
        removeVerifierFixture($root);
    }
})->with([
    'class file target' => [
        ['type' => 'handler_result', 'file' => 'framework/Core/Widget.php', 'seed' => ['SELECT 1'], 'expect' => ['status' => 200]],
        'may only execute a controller',
    ],
    'no seed' => [
        ['type' => 'handler_result', 'file' => 'Http/controllers/posts/index.php', 'expect' => ['status' => 200]],
        "non-empty 'seed' list",
    ],
    'no expectation' => [
        ['type' => 'handler_result', 'file' => 'Http/controllers/posts/index.php', 'seed' => ['SELECT 1']],
        "requires an 'expect' block",
    ],
    'unknown expectation' => [
        ['type' => 'handler_result', 'file' => 'Http/controllers/posts/index.php', 'seed' => ['SELECT 1'], 'expect' => ['body' => 'x']],
        'unsupported expectation',
    ],
    'inspect source without an inspect query' => [
        ['type' => 'handler_result', 'file' => 'Http/controllers/posts/index.php', 'seed' => ['SELECT 1'], 'expect' => ['source' => 'inspect', 'contains' => 'x']],
        "sets no 'inspect' query",
    ],
    'unsupported source' => [
        ['type' => 'handler_result', 'file' => 'Http/controllers/posts/index.php', 'seed' => ['SELECT 1'], 'expect' => ['source' => 'headers', 'contains' => 'x']],
        "unsupported expectation 'source'",
    ],
    'session must be an array' => [
        ['type' => 'handler_result', 'file' => 'Http/controllers/posts/index.php', 'seed' => ['SELECT 1'], 'session' => 'not-an-array', 'expect' => ['status' => 200]],
        "'session' to be an array",
    ],
    'before without after' => [
        ['type' => 'handler_result', 'file' => 'Http/controllers/posts/index.php', 'seed' => ['SELECT 1'], 'expect' => ['before' => 'x']],
        "must set 'before' and 'after' together",
    ],
]);

test('handler result checks can assert relative order with before/after', function (
    string $body,
    bool $expectedPass,
    string $expectedFragment,
) {
    $root = verifierFixture();
    symlink(base_path('vendor'), $root . '/vendor');
    symlink(base_path('framework'), $root . '/framework');
    writeVerifierFixture($root, '.dalt/course/challenges/example/Http/controllers/posts/ranked.php', $body);
    writeVerifierTests($root, [
        'strong_match_ranks_first' => [
            'type' => 'handler_result',
            'file' => 'Http/controllers/posts/ranked.php',
            'seed' => ['SELECT 1'],
            'expect' => ['before' => 'strong-match', 'after' => 'weak-match'],
            'hint' => 'Sort by relevance, not recency.',
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe($expectedPass ? 'pass' : 'fail')
            ->and($result['results'][0]['message'])->toContain($expectedFragment);
    } finally {
        removeVerifierFixture($root);
    }
})->with([
    'ranked correctly, strong match first' => [
        "<?php\nreturn ['results' => ['strong-match', 'weak-match']];\n",
        true,
        'expected response',
    ],
    'sorted the other way round' => [
        "<?php\nreturn ['results' => ['weak-match', 'strong-match']];\n",
        false,
        "has 'weak-match' before 'strong-match', not after",
    ],
    'missing one of the two markers entirely' => [
        "<?php\nreturn ['results' => ['strong-match']];\n",
        false,
        "must contain both 'strong-match' and 'weak-match'",
    ],
]);

test('handler result checks can assert on session state left behind by the handler', function (
    array $session,
    string $body,
    bool $expectedPass,
    string $expectedFragment,
) {
    $root = verifierFixture();
    symlink(base_path('vendor'), $root . '/vendor');
    symlink(base_path('framework'), $root . '/framework');
    writeVerifierFixture($root, '.dalt/course/challenges/example/Http/controllers/contact/precedence.php', $body);
    writeVerifierTests($root, [
        'flash_wins_over_persistent' => [
            'type' => 'handler_result',
            'file' => 'Http/controllers/contact/precedence.php',
            'seed' => ['SELECT 1'],
            'session' => $session,
            'expect' => ['source' => 'session', 'contains' => 'probe'],
            'hint' => 'Flash should win.',
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe($expectedPass ? 'pass' : 'fail')
            ->and($result['results'][0]['message'])->toContain($expectedFragment);
    } finally {
        removeVerifierFixture($root);
    }
})->with([
    'seeded flash data survives ageFlashData and is visible after the handler runs' => [
        ['_flash' => ['new' => ['probe' => 'flash value'], 'old' => ['stale' => 'must not survive']]],
        "<?php\n\\Core\\Session::put('probe', 'ignored');\nreturn ['done' => true];\n",
        true,
        'expected response',
    ],
    'session state without the expected key fails' => [
        ['_flash' => ['new' => [], 'old' => []]],
        "<?php\nreturn ['done' => true];\n",
        false,
        'session state is missing',
    ],
]);

test('handler result checks can assert on a post-handler inspect query', function (
    string $body,
    bool $expectedPass,
    string $expectedFragment,
) {
    $root = verifierFixture();
    symlink(base_path('vendor'), $root . '/vendor');
    symlink(base_path('framework'), $root . '/framework');
    writeVerifierFixture($root, '.dalt/course/challenges/example/Http/controllers/db/transfer.php', $body);
    writeVerifierTests($root, [
        'balance_is_untouched_after_a_failed_transfer' => [
            'type' => 'handler_result',
            'file' => 'Http/controllers/db/transfer.php',
            'seed' => [
                'CREATE TABLE users (id INTEGER PRIMARY KEY, credits INTEGER CHECK (credits <= 100))',
                'INSERT INTO users VALUES (1, 50)',
                'INSERT INTO users VALUES (2, 80)',
            ],
            'inspect' => 'SELECT credits FROM users WHERE id = 1',
            'expect' => ['source' => 'inspect', 'contains' => '"credits":50'],
            'hint' => 'A failed second update must not leave the first one committed.',
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe($expectedPass ? 'pass' : 'fail')
            ->and($result['results'][0]['message'])->toContain($expectedFragment);
    } finally {
        removeVerifierFixture($root);
    }
})->with([
    'genuine rollback leaves the balance untouched' => [
        <<<'PHP'
<?php
$db = \Core\App::resolve(\Core\Database::class);
$pdo = $db->getConnection();
$pdo->beginTransaction();
try {
    $db->query('UPDATE users SET credits = credits - 10 WHERE id = 1');
    $db->query('UPDATE users SET credits = credits + 30 WHERE id = 2');
    $pdo->commit();
    return ['success' => true];
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    return ['success' => false];
}
PHP,
        true,
        'expected response',
    ],
    'a fix that catches the error but still commits leaves a partial write' => [
        <<<'PHP'
<?php
$db = \Core\App::resolve(\Core\Database::class);
$pdo = $db->getConnection();
$pdo->beginTransaction();
try {
    $db->query('UPDATE users SET credits = credits - 10 WHERE id = 1');
    $db->query('UPDATE users SET credits = credits + 30 WHERE id = 2');
} catch (\Throwable $e) {
    // caught, but forgets to roll back
} finally {
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
}
return ['success' => false];
PHP,
        false,
        "is missing '\"credits\":50'",
    ],
]);

test('a pgsql handler result check accepts an empty seed but reports a loud, actionable failure when Postgres is unreachable', function () {
    $root = verifierFixture();
    symlink(base_path('vendor'), $root . '/vendor');
    symlink(base_path('framework'), $root . '/framework');
    // Deliberately unreachable: port 1 refuses connections immediately, no daemon required.
    writeVerifierFixture($root, 'config/database.php', <<<'PHP'
<?php
return ['database' => [
    'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 1,
    'dbname' => 'nope', 'username' => 'nope', 'password' => 'nope', 'charset' => 'utf8',
]];
PHP);
    writeVerifierFixture($root, '.dalt/course/challenges/example/Http/controllers/posts/index.php', "<?php\nreturn ['data' => []];\n");
    writeVerifierTests($root, [
        'reads_live_catalog_state' => [
            'type' => 'handler_result',
            'driver' => 'pgsql',
            'file' => 'Http/controllers/posts/index.php',
            // No 'seed' at all — pgsql checks may read state the learner already built.
            'expect' => ['status' => 200],
            'hint' => 'Confirm Postgres is reachable.',
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('fail')
            ->and($result['results'][0]['message'])->toContain('could not reach PostgreSQL')
            ->and($result['results'][0]['message'])->toContain('database container is running');
    } finally {
        removeVerifierFixture($root);
    }
});

test('a pgsql handler result check against a project still configured for sqlite fails loud instead of silently passing', function () {
    $root = verifierFixture();
    symlink(base_path('vendor'), $root . '/vendor');
    symlink(base_path('framework'), $root . '/framework');
    writeVerifierFixture($root, 'config/database.php', <<<'PHP'
<?php
return ['database' => ['driver' => 'sqlite', 'database' => ':memory:']];
PHP);
    writeVerifierFixture($root, '.dalt/course/challenges/example/Http/controllers/posts/index.php', "<?php\nreturn ['data' => []];\n");
    writeVerifierTests($root, [
        'reads_live_catalog_state' => [
            'type' => 'handler_result',
            'driver' => 'pgsql',
            'file' => 'Http/controllers/posts/index.php',
            'expect' => ['status' => 200],
            'hint' => 'Confirm Postgres is reachable.',
        ],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('fail')
            ->and($result['results'][0]['message'])->toContain('This check requires PostgreSQL')
            ->and($result['results'][0]['message'])->toContain('DB_DRIVER=pgsql');
    } finally {
        removeVerifierFixture($root);
    }
});

test('sqlite handler result checks still reject an empty seed', function () {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/Http/controllers/posts/index.php', '<?php');
    writeVerifierTests($root, [
        'bad' => ['type' => 'handler_result', 'file' => 'Http/controllers/posts/index.php', 'seed' => [], 'expect' => ['status' => 200]],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('error')
            ->and($result['message'])->toContain("non-empty 'seed' list");
    } finally {
        removeVerifierFixture($root);
    }
});

const COMPOSE_WITH_HEALTHCHECK = <<<'YAML'
services:
  app:
    build: .
    depends_on:
      db:
        condition: service_healthy
  db:
    image: postgres:16-alpine
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
YAML;

const COMPOSE_WITHOUT_HEALTHCHECK = <<<'YAML'
services:
  app:
    build: .
    depends_on:
      - db
  db:
    image: postgres:16-alpine
YAML;

test('compose_config checks assert on the normalized structure, not raw text', function (
    string $composeYaml,
    array $checkConfig,
    bool $expectedPass,
    string $expectedFragment,
) {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/docker-compose.yml', $composeYaml);
    writeVerifierTests($root, ['probe' => $checkConfig + ['type' => 'compose_config', 'file' => 'docker-compose.yml']]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe($expectedPass ? 'pass' : 'fail')
            ->and($result['results'][0]['message'])->toContain($expectedFragment);
    } finally {
        removeVerifierFixture($root);
    }
})->with([
    'exists passes when the path is present' => [
        COMPOSE_WITH_HEALTHCHECK,
        ['path' => 'services.db.healthcheck.test', 'exists' => true],
        true,
        'is present',
    ],
    'exists fails when the path is absent' => [
        COMPOSE_WITHOUT_HEALTHCHECK,
        ['path' => 'services.db.healthcheck.test', 'exists' => true],
        false,
        'is missing from the compose configuration',
    ],
    'equals passes on the real value' => [
        COMPOSE_WITH_HEALTHCHECK,
        ['path' => 'services.app.depends_on.db.condition', 'equals' => 'service_healthy'],
        true,
        "equals 'service_healthy'",
    ],
    'equals fails against a decoy value under the wrong condition' => [
        COMPOSE_WITHOUT_HEALTHCHECK,
        ['path' => 'services.app.depends_on.db.condition', 'equals' => 'service_healthy'],
        false,
        "expected 'service_healthy'",
    ],
    'equals fails outright when the path is missing' => [
        COMPOSE_WITHOUT_HEALTHCHECK,
        ['path' => 'services.db.healthcheck.test', 'equals' => 'pg_isready'],
        false,
        'is missing from the compose configuration',
    ],
    'contains checks the value at the path, not the whole file' => [
        COMPOSE_WITH_HEALTHCHECK,
        ['path' => 'services.db.healthcheck.test', 'contains' => 'pg_isready'],
        true,
        "contains 'pg_isready'",
    ],
    'contains does not match a keyword sitting under the wrong key' => [
        <<<'YAML'
        services:
          app:
            build: .
            environment:
              DECOY: "pg_isready mentioned here but unused"
          db:
            image: postgres:16-alpine
        YAML,
        ['path' => 'services.db.healthcheck.test', 'contains' => 'pg_isready'],
        false,
        'is missing from the compose configuration',
    ],
]);

test('compose_config reports a malformed compose file as a failed check, not a crash', function () {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/docker-compose.yml', "services: [this is not valid\n");
    writeVerifierTests($root, [
        'probe' => ['type' => 'compose_config', 'file' => 'docker-compose.yml', 'path' => 'services.db.image', 'exists' => true],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('fail')
            ->and($result['results'][0]['message'])->toContain('could not parse the file');
    } finally {
        removeVerifierFixture($root);
    }
});

test('compose_config reports a missing Docker CLI as a failure, never a silent pass', function () {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/docker-compose.yml', COMPOSE_WITH_HEALTHCHECK);
    writeVerifierTests($root, [
        'probe' => ['type' => 'compose_config', 'file' => 'docker-compose.yml', 'path' => 'services.db.healthcheck.test', 'exists' => true],
    ]);

    $originalPath = getenv('PATH');
    putenv('PATH=' . sys_get_temp_dir());
    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('fail')
            ->and($result['results'][0]['passed'])->toBeFalse()
            ->and($result['results'][0]['message'])->toContain('Docker CLI');
    } finally {
        putenv($originalPath === false ? 'PATH' : "PATH={$originalPath}");
        removeVerifierFixture($root);
    }
});

test('compose_config refuses any target other than docker-compose.yml', function () {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/Dockerfile', "FROM php:8.4\n");
    writeVerifierTests($root, [
        'probe' => ['type' => 'compose_config', 'file' => 'Dockerfile', 'path' => 'services.db.image', 'exists' => true],
    ]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('error')
            ->and($result['message'])->toContain('may only inspect docker-compose.yml');
    } finally {
        removeVerifierFixture($root);
    }
});

test('compose_config assertion config must name exactly one mode', function (array $checkConfig, string $expectedFragment) {
    $root = verifierFixture();
    writeVerifierFixture($root, '.dalt/course/challenges/example/docker-compose.yml', COMPOSE_WITH_HEALTHCHECK);
    writeVerifierTests($root, ['probe' => $checkConfig + ['type' => 'compose_config', 'file' => 'docker-compose.yml']]);

    try {
        $result = (new ChallengeVerifier('.dalt/course/challenges/example', false, $root))->verify();

        expect($result['status'])->toBe('error')
            ->and($result['message'])->toContain($expectedFragment);
    } finally {
        removeVerifierFixture($root);
    }
})->with([
    'neither mode set' => [
        ['path' => 'services.db.image'],
        "exactly one of 'equals', 'exists', or 'contains'",
    ],
    'both equals and exists set' => [
        ['path' => 'services.db.image', 'equals' => 'postgres:16-alpine', 'exists' => true],
        "exactly one of 'equals', 'exists', or 'contains'",
    ],
    'invalid path syntax' => [
        ['path' => 'services..db', 'exists' => true],
        "invalid 'path'",
    ],
]);
