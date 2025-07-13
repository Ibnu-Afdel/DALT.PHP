<?php

namespace Core;

class DatabaseManager
{
    private $config;
    private $database;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->setupDatabase();
    }
    
    private function setupDatabase()
    {
        $driver = $this->config['driver'];
        
        switch ($driver) {
            case 'sqlite':
                $this->setupSQLite();
                break;
            case 'pgsql':
                $this->setupPostgreSQL();
                break;
            case 'mysql':
                $this->setupMySQL();
                break;
            default:
                throw new \Exception("Unsupported database driver: {$driver}");
        }
        
        $this->database = new Database($this->config);
        $this->ensureTablesExist();
    }
    
    private function setupSQLite()
    {
        // Ensure absolute path for SQLite
        if (isset($this->config['database']) && !str_starts_with($this->config['database'], '/')) {
            $this->config['database'] = BASE_PATH . $this->config['database'];
        }
        
        // Create database directory if it doesn't exist
        $dbPath = $this->config['database'];
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Create empty database file if it doesn't exist
        if (!file_exists($dbPath)) {
            touch($dbPath);
        }
    }
    
    private function setupPostgreSQL()
    {
        // For PostgreSQL, we assume the database already exists
        // In a real application, you might want to create the database if it doesn't exist
        // This requires connecting to the postgres database first
    }
    
    private function setupMySQL()
    {
        // For MySQL, we assume the database already exists
        // Similar to PostgreSQL
    }
    
    private function ensureTablesExist()
    {
        try {
            // Check if users table exists
            $this->database->query("SELECT 1 FROM users LIMIT 1");
        } catch (\Exception $e) {
            // Table doesn't exist, run migrations
            $this->runMigrations();
        }
    }
    
    private function runMigrations()
    {
        $migration = new Migration($this->database);
        $migration->runMigrations();
    }
    
    public function getDatabase()
    {
        return $this->database;
    }
    
    public static function create($config)
    {
        $manager = new self($config);
        return $manager->getDatabase();
    }
}
