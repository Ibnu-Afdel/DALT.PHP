<?php

declare(strict_types=1);

use Core\Database;
use Core\DatabaseManager;
use Core\Migration;

function migrationDatabase(): Database
{
    return DatabaseManager::create([
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);
}

/** @param array<string, string> $files */
function withMigrations(array $files, Closure $test): void
{
    $directory = sys_get_temp_dir() . '/dalt-f09-' . bin2hex(random_bytes(6));

    if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the migration fixture directory.');
    }

    try {
        foreach ($files as $name => $sql) {
            file_put_contents($directory . DIRECTORY_SEPARATOR . $name, $sql);
        }

        $test($directory);
    } finally {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($directory);
    }
}

test('migrations run in filename order and are only applied once', function () {
    withMigrations([
        '002_add_email.sql' => 'ALTER TABLE learners ADD COLUMN email TEXT',
        '001_create_learners.sql' => 'CREATE TABLE learners (id INTEGER PRIMARY KEY, name TEXT)',
    ], function (string $directory): void {
        $database = migrationDatabase();
        $migrations = new Migration($database, $directory);

        ob_start();
        $firstRun = $migrations->runMigrations();
        $firstOutput = ob_get_clean();

        ob_start();
        $secondRun = $migrations->runMigrations();
        $secondOutput = ob_get_clean();

        expect($firstRun)->toBe(['001_create_learners.sql', '002_add_email.sql'])
            ->and($secondRun)->toBe([])
            ->and($firstOutput)->toContain('Ran 2 migrations.')
            ->and($secondOutput)->toContain('No migrations to run.')
            ->and($database->query('SELECT migration, batch FROM migrations ORDER BY id')->get())->toBe([
                ['migration' => '001_create_learners.sql', 'batch' => 1],
                ['migration' => '002_add_email.sql', 'batch' => 1],
            ]);
    });
});

test('a failed migration rolls back its schema changes and tracking record', function () {
    withMigrations([
        '001_broken.sql' => 'CREATE TABLE partial_work (id INTEGER); INVALID SQL',
    ], function (string $directory): void {
        $database = migrationDatabase();
        $migrations = new Migration($database, $directory);

        ob_start();
        try {
            $migrations->runMigrations();
            test()->fail('The broken migration should fail.');
        } catch (RuntimeException $exception) {
            expect($exception->getMessage())->toContain("Migration failed.\nDriver: sqlite\nFile: 001_broken.sql");
        } finally {
            ob_end_clean();
        }

        expect($database->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'partial_work'",
        )->find())->toBeFalse()
            ->and($database->query('SELECT migration FROM migrations')->get())->toBe([]);
    });
});

test('a failed migration rolls back to a savepoint inside an existing transaction', function () {
    withMigrations([
        '001_broken.sql' => 'CREATE TABLE nested_partial_work (id INTEGER); INVALID SQL',
    ], function (string $directory): void {
        $database = migrationDatabase();
        $migration = new Migration($database, $directory);
        $migration->createMigrationsTable();
        $database->getConnection()->beginTransaction();

        ob_start();
        try {
            expect(fn () => $migration->runMigrations())
                ->toThrow(RuntimeException::class, 'Migration failed.');
        } finally {
            ob_end_clean();
        }

        expect($database->getConnection()->inTransaction())->toBeTrue()
            ->and($database->query(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'nested_partial_work'",
            )->find())->toBeFalse();

        $database->getConnection()->rollBack();
    });
});

test('an empty migration fails without being marked as run', function () {
    withMigrations(['001_empty.sql' => " \n"], function (string $directory): void {
        $database = migrationDatabase();
        $migrations = new Migration($database, $directory);

        ob_start();
        try {
            expect(fn () => $migrations->runMigrations())
                ->toThrow(RuntimeException::class, 'Migration file is empty: 001_empty.sql');
        } finally {
            ob_end_clean();
        }

        expect($database->query('SELECT migration FROM migrations')->get())->toBe([]);
    });
});

test('the tracking table enforces unique migration names and validates batches', function () {
    withMigrations([], function (string $directory): void {
        $migration = new Migration(migrationDatabase(), $directory);
        $migration->createMigrationsTable();
        $migration->markAsRun('001_once.sql', 1);

        expect(fn () => $migration->markAsRun('', 1))
            ->toThrow(InvalidArgumentException::class)
            ->and(fn () => $migration->markAsRun('001_once.sql', 1))
            ->toThrow(PDOException::class);
    });
});

test('a missing migrations directory has an explicit error', function () {
    $directory = sys_get_temp_dir() . '/dalt-f09-missing-' . bin2hex(random_bytes(6));

    expect(fn () => (new Migration(migrationDatabase(), $directory))->runMigrations())
        ->toThrow(RuntimeException::class, 'Migrations directory not found or unreadable:');
});
