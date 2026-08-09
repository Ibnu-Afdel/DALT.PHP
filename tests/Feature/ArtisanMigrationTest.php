<?php

declare(strict_types=1);

test('the artisan migrate command boots helpers and runs the real migration path', function () {
    $process = proc_open(
        [PHP_BINARY, base_path('artisan'), 'migrate'],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        BASE_PATH,
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
