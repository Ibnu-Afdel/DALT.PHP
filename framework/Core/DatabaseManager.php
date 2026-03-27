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
        $driver = $this->config['driver'] ?? 'sqlite';
        
        switch ($driver) {
            case 'sqlite':
                $this->setupSQLite();
                break;
            case 'pgsql':
                $this->setupPostgreSQL();
                break;
            default:
                throw new \RuntimeException("Unsupported database driver: {$driver}");
        }
        
        $this->database = new Database($this->config);
        $this->ensureTablesExist();
    }
    
    private function setupSQLite()
    {
        if (isset($this->config['database']) && !str_starts_with($this->config['database'], '/')) {
            $this->config['database'] = BASE_PATH . $this->config['database'];
        }
        
        $dbPath = $this->config['database'];
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        if (!file_exists($dbPath)) {
            touch($dbPath);
        }
    }
    
    private function setupPostgreSQL()
    {
    }
    
    private function ensureTablesExist()
    {
        try {
            $this->database->query("SELECT 1 FROM users LIMIT 1");
        } catch (\Exception $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'no such table') || str_contains($msg, 'does not exist')) {
                $this->runMigrations();
            } else {
                throw $e;
            }
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
