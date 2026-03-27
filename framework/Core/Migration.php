<?php

namespace Core;

use PDO;

/**
 * Simple Migration System
 * 
 * Runs raw SQL migration files from database/migrations/
 * No abstractions - learners see real SQL!
 */
class Migration
{
    protected $database;
    
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    private function driver(): string
    {
        return (string) $this->database->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    private function hasSQLiteOnlySql(string $sql): bool
    {
        $needlePatterns = [
            '/\bAUTOINCREMENT\b/i',
            '/\bDATETIME\b/i',
            '/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i',
        ];

        foreach ($needlePatterns as $pattern) {
            if (preg_match($pattern, $sql) === 1) {
                return true;
            }
        }

        return false;
    }

    private function convertSqliteSqlToPgsql(string $sql): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $sql);
        $filtered = [];

        foreach ($lines as $line) {
            if (preg_match('/^\s*PRAGMA\b/i', $line) === 1) {
                continue;
            }
            $filtered[] = $line;
        }

        $sql = implode("\n", $filtered);

        $sql = preg_replace('/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i', 'BIGSERIAL PRIMARY KEY', $sql) ?? $sql;
        $sql = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $sql) ?? $sql;
        $sql = preg_replace('/\bAUTOINCREMENT\b/i', '', $sql) ?? $sql;

        return trim($sql);
    }

    private function pgsqlConversionPrompt(string $migrationFile, string $sqliteSql): string
    {
        $sqliteSql = trim($sqliteSql);
        if (strlen($sqliteSql) > 4000) {
            $sqliteSql = substr($sqliteSql, 0, 4000) . "\n... (truncated)";
        }

        return "You are converting a SQLite migration to PostgreSQL for a small PHP framework.\n"
            . "Output ONLY the PostgreSQL SQL (no explanations).\n"
            . "Preserve table/index names and constraints.\n"
            . "Use BIGSERIAL for autoincrement primary keys.\n"
            . "Use TIMESTAMP for DATETIME.\n\n"
            . "Migration file: {$migrationFile}\n\n"
            . "SQLite SQL:\n{$sqliteSql}\n";
    }
    
    /**
     * Create migrations tracking table
     */
    public function createMigrationsTable()
    {
        $driver = $this->driver();

        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } elseif ($driver === 'pgsql') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id BIGSERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            throw new \RuntimeException("Unsupported database driver: {$driver}");
        }
        
        $this->database->getConnection()->exec($sql);
    }
    
    /**
     * Check if migration has already run
     */
    public function hasRun($migration)
    {
        $result = $this->database->query(
            'SELECT migration FROM migrations WHERE migration = ?',
            [$migration]
        )->find();
        
        return $result !== false;
    }
    
    /**
     * Mark migration as run
     */
    public function markAsRun($migration, $batch)
    {
        $this->database->query(
            'INSERT INTO migrations (migration, batch) VALUES (?, ?)',
            [$migration, $batch]
        );
    }
    
    /**
     * Get next batch number
     */
    public function getNextBatch()
    {
        $result = $this->database->query(
            'SELECT MAX(batch) as max_batch FROM migrations'
        )->find();
        
        return ($result['max_batch'] ?? 0) + 1;
    }
    
    /**
     * Run all pending migrations
     */
    public function runMigrations()
    {
        $this->createMigrationsTable();

        $driver = $this->driver();
        if (!in_array($driver, ['sqlite', 'pgsql'], true)) {
            throw new \RuntimeException("Unsupported database driver: {$driver}");
        }

        $migrationsPath = base_path('database/migrations');
        
        if (!is_dir($migrationsPath)) {
            throw new \RuntimeException("Migrations directory not found: {$migrationsPath}");
        }
        
        // Get all .sql files
        $files = glob($migrationsPath . '/*.sql');
        sort($files);
        
        $batch = $this->getNextBatch();
        $ranMigrations = 0;
        
        foreach ($files as $file) {
            $migration = basename($file);
            
            if (!$this->hasRun($migration)) {
                echo "Running migration: $migration\n";
                
                // Read and execute raw SQL
                $sql = file_get_contents($file);
                if ($driver === 'pgsql' && $this->hasSQLiteOnlySql($sql)) {
                    $sql = $this->convertSqliteSqlToPgsql($sql);

                    if ($this->hasSQLiteOnlySql($sql)) {
                        $message = "Migration needs PostgreSQL SQL, but still contains SQLite-only syntax after conversion.\n"
                            . "Driver: {$driver}\n"
                            . "File: {$migration}\n\n"
                            . "Fix:\n"
                            . "- Convert this migration to PostgreSQL SQL.\n"
                            . "- Then run: php artisan migrate\n\n"
                            . "Copy/paste prompt for your AI:\n"
                            . "----------------------------------------\n"
                            . $this->pgsqlConversionPrompt($migration, file_get_contents($file))
                            . "\n----------------------------------------\n";

                        throw new \RuntimeException($message);
                    }
                }
                
                try {
                    $this->database->getConnection()->exec($sql);
                    $this->markAsRun($migration, $batch);
                    $ranMigrations++;
                    echo "✓ Success\n";
                } catch (\PDOException $e) {
                    $message = "Migration failed.\n"
                        . "Driver: {$driver}\n"
                        . "File: {$migration}\n"
                        . "Error: " . $e->getMessage() . "\n";

                    if (stripos($migration, 'create_users_table') !== false || (stripos($sql, 'create table') !== false && stripos($sql, 'users') !== false)) {
                        $message .= $driver === 'pgsql'
                            ? "Hint: If this file was originally SQLite, convert it to PostgreSQL SQL.\n"
                            : "Hint: Ensure this migration uses compatible SQL for '{$driver}'.\n";
                    }

                    throw new \RuntimeException($message, previous: $e);
                }
            }
        }
        
        if ($ranMigrations > 0) {
            echo "\nRan $ranMigrations migrations.\n";
        } else {
            echo "No migrations to run.\n";
        }
    }
}
