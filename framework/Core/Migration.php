<?php

declare(strict_types=1);

namespace Core;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Runs ordered, raw SQL migrations without hiding the SQL from learners.
 */
final class Migration
{
    private readonly string $migrationsPath;

    public function __construct(
        private readonly Database $database,
        ?string $migrationsPath = null,
    ) {
        $this->migrationsPath = $migrationsPath ?? base_path('database/migrations');
    }

    public function createMigrationsTable(): void
    {
        $sql = match ($this->driver()) {
            'sqlite' => 'CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )',
            'pgsql' => 'CREATE TABLE IF NOT EXISTS migrations (
                id BIGSERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )',
        };

        $this->database->getConnection()->exec($sql);
    }

    public function hasRun(string $migration): bool
    {
        $result = $this->database->query(
            'SELECT 1 FROM migrations WHERE migration = ? LIMIT 1',
            [$migration],
        )->find();

        return $result !== false;
    }

    public function markAsRun(string $migration, int $batch): void
    {
        if ($migration === '' || $batch < 1) {
            throw new \InvalidArgumentException('A migration name and positive batch number are required.');
        }

        $this->database->query(
            'INSERT INTO migrations (migration, batch) VALUES (?, ?)',
            [$migration, $batch],
        );
    }

    public function getNextBatch(): int
    {
        $result = $this->database->query(
            'SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations',
        )->find();

        return (int) ($result['max_batch'] ?? 0) + 1;
    }

    /**
     * Run every pending migration and return the filenames that were applied.
     *
     * @return list<string>
     */
    public function runMigrations(): array
    {
        $this->assertMigrationsDirectory();
        $this->createMigrationsTable();

        $batch = $this->getNextBatch();
        $ran = [];

        foreach ($this->migrationFiles() as $file) {
            $migration = basename($file);

            if ($this->hasRun($migration)) {
                continue;
            }

            echo "Running migration: {$migration}\n";
            $sql = $this->migrationSql($file, $migration);
            $this->runOne($migration, $sql, $batch);
            $ran[] = $migration;
            echo "✓ Success\n";
        }

        if ($ran === []) {
            echo "No migrations to run.\n";
        } else {
            echo "\nRan " . count($ran) . " migrations.\n";
        }

        return $ran;
    }

    private function runOne(string $migration, string $sql, int $batch): void
    {
        $connection = $this->database->getConnection();
        $ownsTransaction = !$connection->inTransaction();
        $savepoint = 'dalt_migration';

        try {
            if ($ownsTransaction) {
                $connection->beginTransaction();
            } else {
                $connection->exec("SAVEPOINT {$savepoint}");
            }

            $connection->exec($sql);
            $this->markAsRun($migration, $batch);

            if ($ownsTransaction) {
                $connection->commit();
            } else {
                $connection->exec("RELEASE SAVEPOINT {$savepoint}");
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $connection->inTransaction()) {
                $connection->rollBack();
            } elseif ($connection->inTransaction()) {
                $connection->exec("ROLLBACK TO SAVEPOINT {$savepoint}");
                $connection->exec("RELEASE SAVEPOINT {$savepoint}");
            }

            throw new RuntimeException(
                "Migration failed.\nDriver: {$this->driver()}\nFile: {$migration}\nError: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }

    /** @return list<string> */
    private function migrationFiles(): array
    {
        $files = glob($this->migrationsPath . DIRECTORY_SEPARATOR . '*.sql');

        if ($files === false) {
            throw new RuntimeException("Unable to read migrations directory: {$this->migrationsPath}");
        }

        sort($files, SORT_STRING);

        return array_values($files);
    }

    private function migrationSql(string $file, string $migration): string
    {
        $sql = file_get_contents($file);

        if (!is_string($sql)) {
            throw new RuntimeException("Unable to read migration file: {$migration}");
        }

        if (trim($sql) === '') {
            throw new RuntimeException("Migration file is empty: {$migration}");
        }

        if ($this->driver() === 'pgsql' && $this->hasSQLiteOnlySql($sql)) {
            $sql = $this->convertSqliteSqlToPgsql($sql);

            if ($this->hasSQLiteOnlySql($sql)) {
                throw new RuntimeException(
                    "Migration contains SQLite-only syntax that cannot be converted for PostgreSQL: {$migration}",
                );
            }
        }

        return $sql;
    }

    private function assertMigrationsDirectory(): void
    {
        if (!is_dir($this->migrationsPath) || !is_readable($this->migrationsPath)) {
            throw new RuntimeException("Migrations directory not found or unreadable: {$this->migrationsPath}");
        }
    }

    private function driver(): string
    {
        $driver = (string) $this->database->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if (!in_array($driver, ['sqlite', 'pgsql'], true)) {
            throw new RuntimeException("Unsupported database driver: {$driver}");
        }

        return $driver;
    }

    private function hasSQLiteOnlySql(string $sql): bool
    {
        return preg_match('/\b(?:AUTOINCREMENT|DATETIME)\b/i', $sql) === 1
            || preg_match('/^\s*PRAGMA\b/im', $sql) === 1;
    }

    private function convertSqliteSqlToPgsql(string $sql): string
    {
        $sql = preg_replace('/^\s*PRAGMA\b[^;]*;?\s*$/im', '', $sql) ?? $sql;
        $sql = preg_replace(
            '/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i',
            'BIGSERIAL PRIMARY KEY',
            $sql,
        ) ?? $sql;
        $sql = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $sql) ?? $sql;
        $sql = preg_replace('/\bAUTOINCREMENT\b/i', '', $sql) ?? $sql;

        return trim($sql);
    }
}
