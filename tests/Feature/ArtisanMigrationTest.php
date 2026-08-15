<?php

declare(strict_types=1);

/**
 * A disposable project root containing only the migrations this framework ships.
 *
 * This test asserts an exact migration count, which made it a test of the *learner's*
 * `database/migrations/` directory rather than of the framework's migration machinery.
 * B05 tells the learner to add workspaces, projects and issues migrations, and the moment
 * they do, "Ran 1 migrations." is false and this fails — while B05's own acceptance
 * criteria require `php artisan test` to pass. Worse, those migrations are PostgreSQL
 * (`BIGSERIAL`, `btrim`), so running them under the SQLite this test forces is a hard
 * error regardless of the count.
 *
 * Root tests must not depend on learner application content, for the same reason they must
 * not depend on `.dalt` content. Symlinking the framework and copying only the shipped
 * migration keeps the subject of the test the thing it was named after.
 *
 * @return array{root: string, cleanup: callable(): void}
 */
function isolatedProjectRoot(): array
{
    $root = sys_get_temp_dir() . '/dalt_migrate_' . bin2hex(random_bytes(6));
    mkdir($root . '/database/migrations', 0o777, true);

    $linked = [];
    foreach (['framework', 'vendor', 'config', 'public', 'bootstrap'] as $shared) {
        $path = base_path($shared);
        if (file_exists($path)) {
            symlink($path, $root . '/' . $shared);
            $linked[] = $shared;
        }
    }
    copy(base_path('artisan'), $root . '/artisan');
    copy(
        base_path('database/migrations/001_create_users_table.sql'),
        $root . '/database/migrations/001_create_users_table.sql',
    );

    return [
        'root' => $root,
        'cleanup' => static function () use ($root, $linked): void {
            foreach ($linked as $shared) {
                unlink($root . '/' . $shared);
            }
            @unlink($root . '/artisan');
            @unlink($root . '/database/migrations/001_create_users_table.sql');
            @rmdir($root . '/database/migrations');
            @rmdir($root . '/database');
            @rmdir($root);
        },
    ];
}

test('the artisan migrate command boots helpers and runs the real migration path', function () {
    ['root' => $root, 'cleanup' => $cleanup] = isolatedProjectRoot();

    try {
        $process = proc_open(
            [PHP_BINARY, $root . '/artisan', 'migrate'],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $root,
            [
                'DB_DRIVER' => 'sqlite',
                'DB_DATABASE' => ':memory:',
            ],
            ['bypass_shell' => true],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start the artisan test process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        expect($exitCode)->toBe(0)
            ->and($stderr)->toBe('')
            ->and($stdout)->toContain('Running migration: 001_create_users_table.sql')
            ->and($stdout)->toContain('Ran 1 migrations.')
            ->and($stdout)->toContain('Migration process completed.');
    } finally {
        $cleanup();
    }
});

test('make migration rejects names that could escape the migrations directory', function () {
    $process = proc_open(
        [PHP_BINARY, base_path('artisan'), 'make:migration', '../../outside'],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        BASE_PATH,
        ['DB_DRIVER' => 'sqlite', 'DB_DATABASE' => ':memory:'],
        ['bypass_shell' => true],
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the artisan test process.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    expect($exitCode)->toBe(1)
        ->and($stderr)->toBe('')
        ->and($stdout)->toContain('Migration names must start with a lowercase letter');
});
