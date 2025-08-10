<?php

namespace Core;

use PDO;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Migration
{
    protected $database;
    protected $capsule;
    
    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->setupCapsule();
    }
    
    private function setupCapsule()
    {
        $this->capsule = new Capsule;
        
        // Get database config from your existing database connection
        $pdo = $this->database->connection;
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        $config = [];
        
        switch ($driver) {
            case 'sqlite':
                // For SQLite, use the database path from environment or config
                $config = [
                    'driver' => 'sqlite',
                    'database' => $_ENV['DB_DATABASE'] ?? BASE_PATH . 'database/app.sqlite',
                    'prefix' => '',
                ];
                break;
                
            case 'pgsql':
                // For PostgreSQL, we'll need to parse the DSN or get config differently
                $config = [
                    'driver' => 'pgsql',
                    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                    'port' => $_ENV['DB_PORT'] ?? 5432,
                    'database' => $_ENV['DB_NAME'] ?? 'dalt_php_app',
                    'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
                    'password' => $_ENV['DB_PASSWORD'] ?? '',
                    'charset' => 'utf8',
                    'prefix' => '',
                ];
                break;
                
            case 'mysql':
                $config = [
                    'driver' => 'mysql',
                    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                    'port' => $_ENV['DB_PORT'] ?? 3306,
                    'database' => $_ENV['DB_NAME'] ?? 'dalt_php_app',
                    'username' => $_ENV['DB_USERNAME'] ?? 'root',
                    'password' => $_ENV['DB_PASSWORD'] ?? '',
                    'charset' => 'utf8mb4',
                    'prefix' => '',
                ];
                break;
        }
        
        $this->capsule->addConnection($config);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }
    
    public function createMigrationsTable()
    {
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
                $table->timestamps();
            });
        }
    }
    
    public function hasRun($migration)
    {
        $result = $this->database->query(
            'SELECT migration FROM migrations WHERE migration = ?',
            [$migration]
        )->find();
        
        return $result !== false;
    }
    
    public function markAsRun($migration, $batch)
    {
        $this->database->query(
            'INSERT INTO migrations (migration, batch) VALUES (?, ?)',
            [$migration, $batch]
        );
    }
    
    public function getNextBatch()
    {
        $result = $this->database->query(
            'SELECT MAX(batch) as max_batch FROM migrations'
        )->find();
        
        return ($result['max_batch'] ?? 0) + 1;
    }
    
    public function runMigrations()
    {
        $this->createMigrationsTable();
        
        $migrationsPath = base_path('database/migrations');
        
        if (!is_dir($migrationsPath)) {
            echo "Migrations directory not found.\n";
            return;
        }
        
        $files = glob($migrationsPath . '/*.php');
        sort($files);
        
        $batch = $this->getNextBatch();
        $ranMigrations = 0;
        
        foreach ($files as $file) {
            $migration = basename($file, '.php');
            
            if (!$this->hasRun($migration)) {
                echo "Running migration: $migration\n";
                
                $migrationInstance = require $file;
                
                if (is_callable($migrationInstance)) {
                    // New Schema Builder migration (no parameters)
                    $migrationInstance();
                }
                
                $this->markAsRun($migration, $batch);
                $ranMigrations++;
            }
        }
        
        if ($ranMigrations > 0) {
            echo "Ran $ranMigrations migrations.\n";
        } else {
            echo "No migrations to run.\n";
        }
    }
}
