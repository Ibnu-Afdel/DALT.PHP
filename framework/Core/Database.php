<?php

namespace Core;
use PDO;
use PDOException;
class Database {

    protected $connection;
    protected $statement;

    public function getConnection(): PDO 
    {
        return $this->connection;
    }
    
    public function __construct($config)
    {
        $dsn = $this->buildDsn($config);
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        try {
            $this->connection = new PDO($dsn, $username, $password, options: [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            $driver = $config['driver'] ?? 'sqlite';
            $message = "Database connection failed.\n"
                . "Driver: {$driver}\n"
                . "DSN: {$dsn}\n"
                . "Error: " . $e->getMessage();

            throw new \RuntimeException($message, previous: $e);
        }
    }
    
    private function buildDsn($config)
    {
        $driver = $config['driver'] ?? 'sqlite';
        
        switch ($driver) {
            case 'pgsql':
                return sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $config['host'],
                    $config['port'],
                    $config['dbname']
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
                throw new \RuntimeException("Unsupported database driver: {$driver}");
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
