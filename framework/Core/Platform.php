<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;

final readonly class Platform
{
    private const REQUIRED_PATHS = [
        'bootstrap.php' => 'file',
        'routes/routes.php' => 'file',
        'Core' => 'directory',
        'Http/controllers' => 'directory',
        'resources/views' => 'directory',
    ];

    private function __construct(private ?string $root)
    {
    }

    public static function discover(string $projectRoot): self
    {
        $platformPath = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . '.dalt';

        if (!file_exists($platformPath)) {
            return new self(null);
        }

        $root = realpath($platformPath);

        if ($root === false || !is_dir($root)) {
            throw new RuntimeException('Guided learning is invalid: .dalt must be a readable directory.');
        }

        $missing = [];

        foreach (self::REQUIRED_PATHS as $path => $type) {
            $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            $hasExpectedType = $type === 'file' ? is_file($fullPath) : is_dir($fullPath);

            if (!$hasExpectedType || !is_readable($fullPath)) {
                $missing[] = ".dalt/{$path}";
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Guided learning is incomplete; missing or invalid required path(s): ' . implode(', ', $missing),
            );
        }

        return new self($root);
    }

    public function isInstalled(): bool
    {
        return $this->root !== null;
    }

    /** @return list<string> */
    public function viewRoots(): array
    {
        return $this->root === null ? [] : [$this->root . DIRECTORY_SEPARATOR . 'resources/views'];
    }

    /** @return list<string> */
    public function controllerRoots(): array
    {
        return $this->root === null ? [] : [$this->root . DIRECTORY_SEPARATOR . 'Http/controllers'];
    }

    public function boot(): void
    {
        if ($this->root !== null) {
            require $this->root . DIRECTORY_SEPARATOR . 'bootstrap.php';
        }
    }

    public function registerRoutes(Router $router): void
    {
        if ($this->root !== null) {
            require $this->root . DIRECTORY_SEPARATOR . 'routes/routes.php';
        }
    }
}
