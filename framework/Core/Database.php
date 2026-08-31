<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;
use LogicException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Database
{
    protected PDO $connection;
    protected ?PDOStatement $statement = null;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $driver = self::driver($config);
        $username = self::nullableString($config, 'username', $driver);
        $password = self::nullableString($config, 'password', $driver);
        $charset = $driver === 'pgsql' ? self::pgsqlValue($config, 'charset', 'utf8') : '';
        $dsn = $this->buildDsn($driver, $config);

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            if ($driver === 'pgsql') {
                $this->connection->exec('SET NAMES ' . $this->connection->quote($charset));
            }
        } catch (PDOException $exception) {
            throw new RuntimeException(
                "Database connection failed for {$driver}.",
                previous: $exception,
            );
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /** @param array<int|string, mixed> $params */
    public function query(string $query, array $params = []): static
    {
        $this->statement = $this->connection->prepare($query);
        $this->statement->execute($params);

        return $this;
    }

    /** @return array<string, mixed>|false */
    public function find(): array|false
    {
        return $this->statement()->fetch();
    }

    /** @return array<string, mixed> */
    public function findOrFail(): array
    {
        $result = $this->find();

        if ($result === false) {
            abort();
        }

        return $result;
    }

    /** @return list<array<string, mixed>> */
    public function get(): array
    {
        return $this->statement()->fetchAll();
    }

    /** @param array<string, mixed> $config */
    private function buildDsn(string $driver, array $config): string
    {
        if ($driver === 'pgsql') {
            $host = self::pgsqlValue($config, 'host');
            $port = self::pgsqlPort($config);
            $database = self::pgsqlValue($config, 'dbname');

            return "pgsql:host={$host};port={$port};dbname={$database}";
        }

        if ($driver === 'mysql') {
            $host = self::mysqlValue($config, 'host', '127.0.0.1');
            $port = self::mysqlPort($config);
            $database = self::mysqlValue($config, 'dbname');
            $charset = self::mysqlValue($config, 'charset', 'utf8mb4');

            return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
        }

        $database = $config['database'] ?? null;

        if (!is_string($database)) {
            throw new InvalidArgumentException(
                "Database configuration 'database' must be a string for sqlite.",
            );
        }

        if ($database !== '' && $database !== ':memory:') {
            $this->ensureSqliteDirectory($database);
        }

        return 'sqlite:' . $database;
    }

    private function ensureSqliteDirectory(string $database): void
    {
        $directory = dirname($database);

        if (is_dir($directory)) {
            return;
        }

        if (file_exists($directory)) {
            throw new RuntimeException(
                "SQLite database directory path is not a directory: {$directory}",
            );
        }

        if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(
                "Unable to create SQLite database directory: {$directory}",
            );
        }
    }

    private function statement(): PDOStatement
    {
        if (!$this->statement instanceof PDOStatement) {
            throw new LogicException('Run query() before fetching results.');
        }

        return $this->statement;
    }

    /** @param array<string, mixed> $config */
    private static function driver(array $config): string
    {
        $driver = $config['driver'] ?? 'sqlite';

        if (!is_string($driver) || !in_array($driver, ['sqlite', 'pgsql', 'mysql'], true)) {
            $display = is_scalar($driver) ? (string) $driver : get_debug_type($driver);

            throw new InvalidArgumentException("Unsupported database driver: {$display}");
        }

        return $driver;
    }

    /** @param array<string, mixed> $config */
    private static function nullableString(array $config, string $key, string $driver): ?string
    {
        $value = $config[$key] ?? null;

        if ($value !== null && !is_string($value)) {
            throw new InvalidArgumentException(
                "Database configuration '{$key}' must be a string or null for {$driver}.",
            );
        }

        return $value;
    }

    /** @param array<string, mixed> $config */
    private static function pgsqlValue(
        array $config,
        string $key,
        ?string $default = null,
    ): string {
        $value = $config[$key] ?? $default;
        $pattern = $key === 'host' ? '/\A[A-Za-z0-9_.:\/-]+\z/' : '/\A[A-Za-z0-9_.-]+\z/';

        if (!is_string($value) || $value === '' || preg_match($pattern, $value) !== 1) {
            throw new InvalidArgumentException(
                "Database configuration '{$key}' must be a safe non-empty string for pgsql.",
            );
        }

        return $value;
    }

    /** @param array<string, mixed> $config */
    private static function pgsqlPort(array $config): int
    {
        $port = $config['port'] ?? null;

        if (!is_int($port) || $port < 1 || $port > 65535) {
            throw new InvalidArgumentException(
                "Database configuration 'port' must be an integer from 1 to 65535 for pgsql.",
            );
        }

        return $port;
    }

    /** @param array<string, mixed> $config */
    private static function mysqlValue(
        array $config,
        string $key,
        ?string $default = null,
    ): string {
        $value = $config[$key] ?? $default;
        $pattern = $key === 'host' ? '/\A[A-Za-z0-9_.:\/-]+\z/' : '/\A[A-Za-z0-9_.-]+\z/';

        if (!is_string($value) || $value === '' || preg_match($pattern, $value) !== 1) {
            throw new InvalidArgumentException(
                "Database configuration '{$key}' must be a safe non-empty string for mysql.",
            );
        }

        return $value;
    }

    /** @param array<string, mixed> $config */
    private static function mysqlPort(array $config): int
    {
        $port = $config['port'] ?? 3306;

        if (!is_int($port) || $port < 1 || $port > 65535) {
            throw new InvalidArgumentException(
                "Database configuration 'port' must be an integer from 1 to 65535 for mysql.",
            );
        }

        return $port;
    }
}
