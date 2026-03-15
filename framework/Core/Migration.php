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
    
    /**
     * Create migrations tracking table
     */
    public function createMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL,
            batch INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->database->connection->exec($sql);
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
        
        $migrationsPath = base_path('database/migrations');
        
        if (!is_dir($migrationsPath)) {
            echo "Migrations directory not found.\n";
            return;
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
                
                try {
                    $this->database->connection->exec($sql);
                    $this->markAsRun($migration, $batch);
                    $ranMigrations++;
                    echo "✓ Success\n";
                } catch (\PDOException $e) {
                    echo "✗ Failed: " . $e->getMessage() . "\n";
                    break;
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
