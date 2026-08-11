<?php

declare(strict_types=1);

namespace Core;

use FilesystemIterator;
use Throwable;

final class PlatformRemovalManager
{
    private string $root;
    private string $platform;
    private string $authManifest;
    private string $lock;

    public function __construct(string $projectRoot)
    {
        $resolved = realpath($projectRoot);
        if ($resolved === false || !is_dir($resolved)) {
            throw new PlatformRemovalException('The project root cannot be resolved.');
        }

        $this->root = rtrim($resolved, DIRECTORY_SEPARATOR);
        $this->platform = $this->root . DIRECTORY_SEPARATOR . '.dalt';
        $this->authManifest = $this->root . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'examples'
            . DIRECTORY_SEPARATOR . 'auth.json';
        $this->lock = $this->root . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'platform-removal.lock';
    }

    public function remove(): string
    {
        return $this->locked(function (): string {
            if (!file_exists($this->platform) && !is_link($this->platform)) {
                return 'Guided learning is already removed. The project is running in framework-core mode.';
            }
            if (is_link($this->platform) || !is_dir($this->platform)) {
                throw new PlatformRemovalException('Refusing to remove an unsafe .dalt path.');
            }

            $this->assertRemovableTree($this->platform);
            $this->assertSafeStatePath($this->authManifest);
            $quarantine = $this->root . DIRECTORY_SEPARATOR . '.dalt-removing-' . bin2hex(random_bytes(6));
            $manifestQuarantine = null;

            if (!rename($this->platform, $quarantine)) {
                throw new PlatformRemovalException('Unable to move .dalt into removal quarantine.');
            }

            try {
                if (file_exists($this->authManifest) || is_link($this->authManifest)) {
                    if (is_link($this->authManifest) || !is_file($this->authManifest)) {
                        throw new PlatformRemovalException('The auth ownership manifest is unsafe; no application files were changed.');
                    }

                    $manifestQuarantine = dirname($this->authManifest) . DIRECTORY_SEPARATOR
                        . '.auth.json.detaching-' . bin2hex(random_bytes(6));
                    if (!rename($this->authManifest, $manifestQuarantine)) {
                        throw new PlatformRemovalException('Unable to detach the auth ownership manifest.');
                    }
                }
            } catch (Throwable $exception) {
                if ($manifestQuarantine !== null && is_file($manifestQuarantine)) {
                    if (!rename($manifestQuarantine, $this->authManifest)) {
                        throw new PlatformRemovalException(
                            'Platform removal failed and the auth ownership manifest could not be restored.',
                            0,
                            $exception,
                        );
                    }
                }
                if (!rename($quarantine, $this->platform)) {
                    throw new PlatformRemovalException(
                        'Platform removal failed and .dalt could not be restored from ' . basename($quarantine) . '.',
                        0,
                        $exception,
                    );
                }

                throw $exception;
            }

            $this->removeTree($quarantine);
            if ($manifestQuarantine !== null && !unlink($manifestQuarantine)) {
                throw new PlatformRemovalException('Guided learning was removed, but its detached auth ownership manifest could not be deleted.');
            }

            return $manifestQuarantine === null
                ? 'Guided learning removed. The project is now running in framework-core mode.'
                : 'Guided learning removed. The installed auth example was preserved as learner-owned application code.';
        });
    }

    private function assertRemovableTree(string $directory): void
    {
        try {
            $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
        } catch (Throwable $exception) {
            throw new PlatformRemovalException("Unable to inspect platform directory {$directory}.", 0, $exception);
        }

        foreach ($items as $item) {
            $path = $item->getPathname();
            if ($item->isLink()) {
                continue;
            }
            if ($item->isDir()) {
                $this->assertRemovableTree($path);
                continue;
            }
            if (!$item->isFile()) {
                throw new PlatformRemovalException("Unsupported filesystem entry in .dalt: {$path}");
            }
        }
    }

    private function removeTree(string $directory): void
    {
        try {
            $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
        } catch (Throwable $exception) {
            throw new PlatformRemovalException("Unable to read removal quarantine {$directory}.", 0, $exception);
        }

        foreach ($items as $item) {
            $path = $item->getPathname();
            if ($item->isDir() && !$item->isLink()) {
                $this->removeTree($path);
            } elseif (!unlink($path)) {
                throw new PlatformRemovalException("Unable to remove platform entry {$path}.");
            }
        }

        if (!rmdir($directory)) {
            throw new PlatformRemovalException("Unable to remove platform directory {$directory}.");
        }
    }

    private function assertSafeStatePath(string $path): void
    {
        $relative = ltrim(substr(dirname($path), strlen($this->root)), DIRECTORY_SEPARATOR);
        $current = $this->root;
        foreach ($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            $current .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($current)) {
                throw new PlatformRemovalException("Refusing platform removal through symbolic-link directory {$current}.");
            }
            if (file_exists($current) && !is_dir($current)) {
                throw new PlatformRemovalException("Platform-removal state path is not a directory: {$current}");
            }
        }
    }

    private function locked(callable $operation): mixed
    {
        $this->assertSafeStatePath($this->lock);
        $directory = dirname($this->lock);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new PlatformRemovalException('Unable to create the platform-removal state directory.');
        }
        if (is_link($this->lock) || (file_exists($this->lock) && !is_file($this->lock))) {
            throw new PlatformRemovalException('The platform-removal lock path is unsafe.');
        }

        $handle = fopen($this->lock, 'c+');
        if (!is_resource($handle) || !flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new PlatformRemovalException('Another platform-removal operation is already running.');
        }

        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
            if (is_file($this->lock) && !unlink($this->lock)) {
                throw new PlatformRemovalException('Unable to remove the platform-removal lock.');
            }
        }
    }
}
