<?php

namespace Core;
use PDO;
class Database {

    public $connection;
    public $statement;
    
    public function __construct($config)
    {
        $dsn = $this->buildDsn($config);
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        $this->connection = new PDO($dsn, $username, $password, options: [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    
    private function buildDsn($config)
    {
        $driver = $config['driver'] ?? 'pgsql';
        
        switch ($driver) {
            case 'pgsql':
                return sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $config['host'],
                    $config['port'],
                    $config['dbname']
                );
                
            case 'mysql':
                return sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['dbname'],
                    $config['charset'] ?? 'utf8mb4'
                );
                
            case 'sqlite':
                // Ensure the database directory exists
                $databasePath = $config['database'];
                $databaseDir = dirname($databasePath);
                if (!is_dir($databaseDir)) {
                    mkdir($databaseDir, 0755, true);
                }
                return 'sqlite:' . $databasePath;
                
            default:
                throw new \Exception("Unsupported database driver: {$driver}");
        }
    }
    public function query($query, $params = []) {

        $this->statement = $this->connection->prepare($query);

        $this->statement->execute($params);
        return $this;
    }

    public function find(){
        return $this->statement->fetch();
    }

    public function findOrFail(){
        $result = $this->find();
        if (!$result){
            abort();
        }
        return $result;
    }

    public function get(){
        return $this->statement->fetchAll();
    }
}

// scope resolution operator ? -> ::