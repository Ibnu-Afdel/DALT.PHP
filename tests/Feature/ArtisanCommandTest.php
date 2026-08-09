<?php

declare(strict_types=1);

/**
 * @param list<string> $arguments
 * @param array<string, string> $environment
 * @return array{exitCode: int, stdout: string, stderr: string}
 */
function runF11Artisan(
    array $arguments,
    array $environment = [],
    string $input = '',
    ?string $workingDirectory = null,
): array {
    $process = proc_open(
        [PHP_BINARY, base_path('artisan'), ...$arguments],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $workingDirectory ?? BASE_PATH,
        [...getenv(), ...$environment],
        ['bypass_shell' => true],
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the artisan test process.');
    }

    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'exitCode' => proc_close($process),
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
}

test('non-database commands do not load database configuration', function () {
    $result = runF11Artisan(['platform:status'], ['DB_DRIVER' => 'unsupported']);

    expect($result['exitCode'])->toBe(0)
        ->and($result['stderr'])->toBe('')
        ->and($result['stdout'])->toContain('Guided learning: installed');
});

test('the test command forwards arguments and returns the child exit code', function () {
    $result = runF11Artisan(['test', '--definitely-invalid-option']);

    expect($result['exitCode'])->toBe(2)
        ->and($result['stdout'])->toContain('Unknown option "--definitely-invalid-option"');
});

test('serve validates host and port before starting a process', function (array $arguments, string $message) {
    $result = runF11Artisan(['serve', ...$arguments]);

    expect($result['exitCode'])->toBe(1)
        ->and($result['stderr'])->toContain($message);
})->with([
    'host containing shell syntax' => [['127.0.0.1;touch-pwned', '8000'], 'Invalid host'],
    'non-numeric port' => [['127.0.0.1', '8000;touch-pwned'], 'Invalid port'],
    'out-of-range port' => [['127.0.0.1', '65536'], 'Invalid port'],
]);

test('database commands resolve relative sqlite paths from the project root', function () {
    $relativePath = 'database/f11_cli_' . bin2hex(random_bytes(6)) . '.sqlite';
    $databasePath = base_path($relativePath);
    if (file_exists($databasePath)) {
        unlink($databasePath);
    }

    try {
        $result = runF11Artisan(
            ['migrate'],
            ['DB_DRIVER' => 'sqlite', 'DB_DATABASE' => $relativePath],
            workingDirectory: sys_get_temp_dir(),
        );

        expect($result['exitCode'])->toBe(0)
            ->and($result['stderr'])->toBe('')
            ->and($result['stdout'])->toContain('Migration process completed.')
            ->and(file_exists($databasePath))->toBeTrue()
            ->and(file_exists(sys_get_temp_dir() . '/' . $relativePath))->toBeFalse();
    } finally {
        @unlink($databasePath);
    }
});

test('migrate fresh refuses destructive work without confirmation', function () {
    $relativePath = 'database/f11_fresh_' . bin2hex(random_bytes(6)) . '.sqlite';
    $databasePath = base_path($relativePath);
    $database = new PDO('sqlite:' . $databasePath);
    $database->exec('CREATE TABLE preserved (id INTEGER PRIMARY KEY)');
    $database = null;

    try {
        $result = runF11Artisan(
            ['migrate:fresh'],
            ['DB_DRIVER' => 'sqlite', 'DB_DATABASE' => $relativePath],
            workingDirectory: sys_get_temp_dir(),
        );

        $check = new PDO('sqlite:' . $databasePath);
        $table = $check->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'preserved'")?->fetchColumn();

        expect($result['exitCode'])->toBe(1)
            ->and($result['stdout'])->toContain('Cancelled. No changes made.')
            ->and($table)->toBe('preserved');
    } finally {
        @unlink($databasePath);
    }
});

test('make migration rejects names that collapse to an invalid table identifier', function () {
    $result = runF11Artisan(
        ['make:migration', 'create__table'],
        ['DB_DRIVER' => 'sqlite', 'DB_DATABASE' => ':memory:'],
    );

    expect($result['exitCode'])->toBe(1)
        ->and($result['stdout'])->toContain('must produce a valid table identifier');
});

test('make migration generates a quoted portable table identifier', function () {
    $name = 'table_f11_' . bin2hex(random_bytes(4));
    $result = runF11Artisan(
        ['make:migration', $name],
        ['DB_DRIVER' => 'sqlite', 'DB_DATABASE' => ':memory:'],
    );
    preg_match('/Migration created: (database\/migrations\/[0-9]{14}_[a-z0-9_]+\.sql)/', $result['stdout'], $matches);
    $relativePath = $matches[1] ?? null;

    try {
        expect($result['exitCode'])->toBe(0)
            ->and($relativePath)->toBeString()
            ->and(file_get_contents(base_path($relativePath)))->toContain('CREATE TABLE IF NOT EXISTS "' . $name . '"');
    } finally {
        if (is_string($relativePath) && file_exists(base_path($relativePath))) {
            unlink(base_path($relativePath));
        }
    }
});

test('the post create hook initializes a project without relying on a shell or caller directory', function () {
    $project = sys_get_temp_dir() . '/dalt-f11-' . bin2hex(random_bytes(6));
    $scripts = $project . '/.dalt/scripts';
    mkdir($scripts, 0755, true);
    copy(base_path('.dalt/scripts/post-create.php'), $scripts . '/post-create.php');

    $process = proc_open(
        [PHP_BINARY, $scripts . '/post-create.php'],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        sys_get_temp_dir(),
        null,
        ['bypass_shell' => true],
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the post-create test process.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    try {
        expect($exitCode)->toBe(0)
            ->and($stderr)->toBe('')
            ->and(file_get_contents($project . '/.env'))->toContain('DB_DRIVER=sqlite')
            ->and(file_exists($project . '/.env.example'))->toBeTrue()
            ->and(file_exists($project . '/storage/logs/.gitkeep'))->toBeTrue()
            ->and($stdout)->toContain('cd ' . $project);
    } finally {
        foreach ([$project . '/storage/logs/.gitkeep', $project . '/.env.example', $project . '/.env', $scripts . '/post-create.php'] as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        rmdir($project . '/storage/logs');
        rmdir($project . '/storage');
        rmdir($scripts);
        rmdir($project . '/.dalt');
        rmdir($project);
    }
});

test('the dev command returns a failed frontend child status', function () {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('The fake npm executable in this test is POSIX-only.');
    }

    $binDirectory = sys_get_temp_dir() . '/dalt-f11-bin-' . bin2hex(random_bytes(6));
    $npm = $binDirectory . '/npm';
    mkdir($binDirectory, 0755, true);
    file_put_contents($npm, "#!/usr/bin/env php\n<?php exit((\$argv[1] ?? '') === '--version' ? 0 : 7);\n");
    chmod($npm, 0755);

    try {
        $result = runF11Artisan(
            ['dev'],
            ['PATH' => $binDirectory . PATH_SEPARATOR . (getenv('PATH') ?: '')],
        );

        expect($result['exitCode'])->toBe(7)
            ->and($result['stdout'])->toContain('Starting DALT.PHP development environment');
    } finally {
        unlink($npm);
        rmdir($binDirectory);
    }
});
