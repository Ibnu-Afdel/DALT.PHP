<?php

declare(strict_types=1);

namespace Core;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class AuthScaffoldManager
{
    private const SCHEMA = 1;
    private const ROUTE_START = '// DALT auth example routes:begin';
    private const ROUTE_END = '// DALT auth example routes:end';

    /** @var list<string> */
    private const FILES = [
        'app/Http/controllers/session/create.php',
        'app/Http/controllers/session/store.php',
        'app/Http/controllers/session/destroy.php',
        'app/Http/controllers/registration/create.php',
        'app/Http/controllers/registration/store.php',
        'resources/views/auth/login.view.php',
        'resources/views/auth/register.view.php',
        'routes/auth.php',
    ];

    /** @var list<string> */
    private const ROUTES = [
        'GET /register',
        'POST /register',
        'GET /login',
        'POST /session',
        'DELETE /session',
    ];

    private string $root;
    private string $source;
    private string $manifest;
    private string $lock;

    public function __construct(string $projectRoot)
    {
        $resolved = realpath($projectRoot);
        if ($resolved === false || !is_dir($resolved)) {
            throw new AuthScaffoldException('The project root cannot be resolved.');
        }

        $this->root = rtrim($resolved, DIRECTORY_SEPARATOR);
        $this->source = $this->root . '/.dalt/stubs/auth';
        $this->manifest = $this->root . '/storage/framework/examples/auth.json';
        $this->lock = $this->root . '/storage/framework/examples/auth.lock';
    }

    public function install(): string
    {
        return $this->locked(function (): string {
            $this->assertSources();
            if (is_file($this->manifest)) {
                $manifest = $this->readManifest();
                $this->assertManagedState($manifest);

                return $this->sourcesChanged($manifest)
                    ? 'Auth example is installed; run `php artisan example:update auth` to apply scaffold updates.'
                    : 'Auth example is already installed and current.';
            }

            $routesPath = $this->path('routes/routes.php');
            $routesBefore = $this->readRequiredFile($routesPath, 'Application routes file');
            if (str_contains($routesBefore, self::ROUTE_START) || str_contains($routesBefore, self::ROUTE_END)) {
                throw new AuthScaffoldException('Auth route markers exist without an ownership manifest; no files were changed.');
            }

            $conflictingFiles = array_values(array_filter(self::FILES, fn (string $file): bool => file_exists($this->path($file))));
            if ($conflictingFiles !== []) {
                throw new AuthScaffoldException('Install would overwrite existing files: ' . implode(', ', $conflictingFiles));
            }

            $routeConflicts = $this->routeConflicts();
            if ($routeConflicts !== []) {
                throw new AuthScaffoldException('Install would register existing routes: ' . implode(', ', $routeConflicts));
            }

            $created = [];
            try {
                foreach (self::FILES as $file) {
                    $this->writeAtomically($this->path($file), $this->sourceContents($file));
                    $created[] = $file;
                }
                $this->writeAtomically($routesPath, rtrim($routesBefore) . "\n\n" . $this->routeBlock());
                $this->writeManifest($this->buildManifest());
            } catch (Throwable $exception) {
                $this->writeAtomically($routesPath, $routesBefore);
                foreach (array_reverse($created) as $file) {
                    $createdPath = $this->path($file);
                    if (is_file($createdPath)) {
                        unlink($createdPath);
                    }
                }
                if (is_file($this->manifest)) {
                    unlink($this->manifest);
                }
                throw new AuthScaffoldException('Auth installation was rolled back: ' . $exception->getMessage(), 0, $exception);
            }

            return 'Auth example installed. Run `php artisan migrate`, then visit /register or /login.';
        });
    }

    public function update(): string
    {
        return $this->locked(function (): string {
            $this->assertSources();
            $manifest = $this->readManifest();
            $this->assertManagedState($manifest);

            if (!$this->sourcesChanged($manifest)) {
                return 'Auth example is already current.';
            }

            /** @var array<string, string> $before */
            $before = [];
            try {
                foreach (self::FILES as $file) {
                    $destination = $this->path($file);
                    $before[$file] = $this->readRequiredFile($destination, "Managed file {$file}");
                    $this->writeAtomically($destination, $this->sourceContents($file));
                }
                $this->writeManifest($this->buildManifest());
            } catch (Throwable $exception) {
                foreach ($before as $file => $contents) {
                    $this->writeAtomically($this->path($file), $contents);
                }
                throw new AuthScaffoldException('Auth update was rolled back: ' . $exception->getMessage(), 0, $exception);
            }

            return 'Auth example updated. Untouched generated files now match the current scaffold.';
        });
    }

    public function uninstall(bool $force = false): string
    {
        return $this->locked(function () use ($force): string {
            $manifest = $this->readManifest();
            foreach (self::FILES as $file) {
                $path = $this->path($file);
                $this->assertSafeParent($path);
                if (is_link($path)) {
                    throw new AuthScaffoldException("Refusing to remove symbolic-link destination {$file}.");
                }
            }
            if (!$force) {
                $this->assertManagedState($manifest);
            } else {
                $this->assertRouteBlockPresent();
            }

            $routesPath = $this->path('routes/routes.php');
            $routes = $this->readRequiredFile($routesPath, 'Application routes file');
            $updatedRoutes = $this->removeRouteBlock($routes);
            $this->writeAtomically($routesPath, $updatedRoutes);

            foreach (self::FILES as $file) {
                $path = $this->path($file);
                if (is_file($path) || is_link($path)) {
                    if (!unlink($path)) {
                        throw new AuthScaffoldException("Unable to remove managed file {$file}.");
                    }
                }
            }
            if (is_file($this->manifest) && !unlink($this->manifest)) {
                throw new AuthScaffoldException('Unable to remove the auth ownership manifest.');
            }

            return $force
                ? 'Auth example uninstalled, including modified generated files.'
                : 'Auth example uninstalled. Application-owned files were not touched.';
        });
    }

    /** @return array{schema: int, files: array<string, string>, route_block: string} */
    private function buildManifest(): array
    {
        $files = [];
        foreach (self::FILES as $file) {
            $files[$file] = hash('sha256', $this->sourceContents($file));
        }

        return ['schema' => self::SCHEMA, 'files' => $files, 'route_block' => hash('sha256', $this->routeBlock())];
    }

    /** @return array{schema: int, files: array<string, string>, route_block: string} */
    private function readManifest(): array
    {
        $this->assertSafeParent($this->manifest);
        if (!is_file($this->manifest) || is_link($this->manifest)) {
            throw new AuthScaffoldException('The auth example is not installed.');
        }

        try {
            $data = json_decode($this->readRequiredFile($this->manifest, 'Auth ownership manifest'), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new AuthScaffoldException('The auth ownership manifest is invalid.', 0, $exception);
        }

        if (!is_array($data) || ($data['schema'] ?? null) !== self::SCHEMA || !is_array($data['files'] ?? null)) {
            throw new AuthScaffoldException('The auth ownership manifest has an unsupported schema.');
        }
        if (array_keys($data['files']) !== self::FILES || !is_string($data['route_block'] ?? null)) {
            throw new AuthScaffoldException('The auth ownership manifest does not describe the expected files.');
        }
        foreach ($data['files'] as $hash) {
            if (!is_string($hash) || preg_match('/\A[a-f0-9]{64}\z/', $hash) !== 1) {
                throw new AuthScaffoldException('The auth ownership manifest contains an invalid file hash.');
            }
        }

        /** @var array{schema: int, files: array<string, string>, route_block: string} $data */
        return $data;
    }

    /** @param array{schema: int, files: array<string, string>, route_block: string} $manifest */
    private function assertManagedState(array $manifest): void
    {
        $modified = [];
        foreach ($manifest['files'] as $file => $hash) {
            $path = $this->path($file);
            $this->assertSafeParent($path);
            if (!is_file($path) || is_link($path) || hash_file('sha256', $path) !== $hash) {
                $modified[] = $file;
            }
        }
        if ($modified !== []) {
            throw new AuthScaffoldException('Generated files are missing or modified: ' . implode(', ', $modified) . '. Preserve your work or use `example:uninstall auth --force`.');
        }

        $this->assertRouteBlockPresent($manifest['route_block']);
    }

    private function assertRouteBlockPresent(?string $expectedHash = null): void
    {
        $routes = $this->readRequiredFile($this->path('routes/routes.php'), 'Application routes file');
        $block = $this->extractRouteBlock($routes);
        if ($block === null || ($expectedHash !== null && hash('sha256', $block) !== $expectedHash)) {
            throw new AuthScaffoldException('The managed auth route block is missing or modified; no files were changed.');
        }
    }

    /** @param array{schema: int, files: array<string, string>, route_block: string} $manifest */
    private function sourcesChanged(array $manifest): bool
    {
        return $manifest['files'] !== $this->buildManifest()['files'];
    }

    /** @return list<string> */
    private function routeConflicts(): array
    {
        $found = [];
        $routesDirectory = $this->path('routes');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($routesDirectory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $item) {
            if (!$item->isFile() || $item->isLink() || $item->getExtension() !== 'php') {
                continue;
            }
            $contents = $this->readRequiredFile($item->getPathname(), 'Route source');
            foreach ($this->staticRoutes($contents) as $route) {
                if (in_array($route, self::ROUTES, true)) {
                    $found[] = $route;
                }
            }
        }

        return array_values(array_unique($found));
    }

    /** @return list<string> */
    private function staticRoutes(string $php): array
    {
        $routes = [];
        $tokens = array_values(array_filter(
            token_get_all($php),
            static fn (array|string $token): bool => !is_array($token)
                || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));

        for ($index = 0, $count = count($tokens); $index + 4 < $count; $index++) {
            $variable = $tokens[$index];
            $operator = $tokens[$index + 1];
            $method = $tokens[$index + 2];
            $opening = $tokens[$index + 3];
            $path = $tokens[$index + 4];
            if (!is_array($variable) || $variable[0] !== T_VARIABLE || $variable[1] !== '$router'
                || (!is_array($operator) || $operator[0] !== T_OBJECT_OPERATOR)
                || !is_array($method) || $method[0] !== T_STRING
                || $opening !== '(' || !is_array($path) || $path[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            $verb = strtoupper($method[1]);
            if (in_array($verb, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $literal = $path[1];
                $quote = $literal[0];
                $value = substr($literal, 1, -1);
                $value = $quote === "'"
                    ? str_replace(["\\\\", "\\'"], ["\\", "'"], $value)
                    : stripcslashes($value);
                $routes[] = $verb . ' ' . $value;
            }
        }

        return $routes;
    }

    private function assertSources(): void
    {
        foreach (self::FILES as $file) {
            $source = $this->sourcePath($file);
            if (!is_file($source) || is_link($source)) {
                throw new AuthScaffoldException("Auth scaffold source is missing or unsafe: {$file}");
            }
        }
    }

    private function sourceContents(string $destination): string
    {
        return $this->readRequiredFile($this->sourcePath($destination), "Auth scaffold source {$destination}");
    }

    private function sourcePath(string $destination): string
    {
        return $this->source . '/' . match (true) {
            str_starts_with($destination, 'app/') => substr($destination, 4),
            str_starts_with($destination, 'resources/') => $destination,
            $destination === 'routes/auth.php' => 'routes/auth.php',
            default => throw new AuthScaffoldException("Unsupported auth scaffold destination: {$destination}"),
        };
    }

    private function routeBlock(): string
    {
        return self::ROUTE_START . "\nrequire base_path('routes/auth.php');\n" . self::ROUTE_END . "\n";
    }

    private function extractRouteBlock(string $routes): ?string
    {
        $start = strpos($routes, self::ROUTE_START);
        $end = strpos($routes, self::ROUTE_END);
        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        $end += strlen(self::ROUTE_END);
        if (substr($routes, $end, 1) === "\n") {
            $end++;
        }

        return substr($routes, $start, $end - $start);
    }

    private function removeRouteBlock(string $routes): string
    {
        $block = $this->extractRouteBlock($routes);
        if ($block === null) {
            throw new AuthScaffoldException('The managed auth route block is missing or malformed.');
        }

        return rtrim(str_replace($block, '', $routes)) . "\n";
    }

    private function writeManifest(array $manifest): void
    {
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $this->writeAtomically($this->manifest, $json);
    }

    private function writeAtomically(string $path, string $contents): void
    {
        $this->assertSafeParent($path);
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new AuthScaffoldException("Unable to create directory {$directory}.");
        }
        if (is_link($path)) {
            throw new AuthScaffoldException("Refusing to replace symbolic link {$path}.");
        }

        $temporary = $directory . '/.' . basename($path) . '.tmp-' . bin2hex(random_bytes(6));
        if (file_put_contents($temporary, $contents, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new AuthScaffoldException("Unable to write {$path}.");
        }
    }

    private function readRequiredFile(string $path, string $label): string
    {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new AuthScaffoldException("{$label} cannot be read: {$path}");
        }

        return $contents;
    }

    private function path(string $relative): string
    {
        return $this->root . '/' . ltrim($relative, '/');
    }

    private function assertSafeParent(string $path): void
    {
        $directory = dirname($path);
        if ($directory !== $this->root && !str_starts_with($directory, $this->root . DIRECTORY_SEPARATOR)) {
            throw new AuthScaffoldException("Scaffold path is outside the project root: {$path}");
        }

        $relative = ltrim(substr($directory, strlen($this->root)), DIRECTORY_SEPARATOR);
        $current = $this->root;
        foreach ($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            $current .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($current)) {
                throw new AuthScaffoldException("Refusing scaffold operation through symbolic-link directory {$current}.");
            }
            if (file_exists($current) && !is_dir($current)) {
                throw new AuthScaffoldException("Scaffold parent path is not a directory: {$current}");
            }
        }
    }

    private function locked(callable $operation): mixed
    {
        $this->assertSafeParent($this->lock);
        $directory = dirname($this->lock);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new AuthScaffoldException('Unable to create the auth scaffold state directory.');
        }
        $handle = fopen($this->lock, 'c+');
        if (!is_resource($handle) || !flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new AuthScaffoldException('Another auth scaffold operation is already running.');
        }

        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
            @unlink($this->lock);
        }
    }
}
