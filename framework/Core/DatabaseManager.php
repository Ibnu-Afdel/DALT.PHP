<?php

declare(strict_types=1);

namespace Core;

final class DatabaseManager
{
    private ?Database $database = null;

    /** @param array<string, mixed> $config */
    public function __construct(private array $config)
    {
    }

    public function getDatabase(): Database
    {
        return $this->database ??= new Database($this->normalizedConfig());
    }

    /** @param array<string, mixed> $config */
    public static function create(array $config): Database
    {
        return (new self($config))->getDatabase();
    }

    /** @return array<string, mixed> */
    private function normalizedConfig(): array
    {
        $driver = $this->config['driver'] ?? 'sqlite';
        $database = $this->config['database'] ?? null;

        if ($driver !== 'sqlite'
            || !is_string($database)
            || $database === ''
            || $database === ':memory:'
            || self::isAbsolutePath($database)) {
            return $this->config;
        }

        return [...$this->config, 'database' => base_path($database)];
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/\A[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
