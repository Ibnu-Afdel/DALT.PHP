<?php

declare(strict_types=1);

/** @throws RuntimeException */
function removeVcsTree(string $directory): void
{
    try {
        $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
    } catch (Throwable $exception) {
        throw new RuntimeException("Unable to inspect VCS metadata at {$directory}.", 0, $exception);
    }

    foreach ($items as $item) {
        $path = $item->getPathname();
        if ($item->isDir() && !$item->isLink()) {
            removeVcsTree($path);
        } elseif (!unlink($path)) {
            throw new RuntimeException("Unable to remove VCS entry {$path}.");
        }
    }

    if (!rmdir($directory)) {
        throw new RuntimeException("Unable to remove VCS directory {$directory}.");
    }
}

try {
    $resolvedRoot = realpath(dirname(__DIR__, 2));
    if ($resolvedRoot === false) {
        throw new RuntimeException('Unable to resolve the new project directory.');
    }

    $git = $resolvedRoot . DIRECTORY_SEPARATOR . '.git';
    if (!file_exists($git) && !is_link($git)) {
        return;
    }
    if (is_link($git)) {
        throw new RuntimeException('Refusing to remove symbolic-link VCS metadata.');
    }

    $quarantine = $resolvedRoot . DIRECTORY_SEPARATOR . '.git-removing-' . bin2hex(random_bytes(6));
    if (!rename($git, $quarantine)) {
        throw new RuntimeException('Unable to move VCS metadata into removal quarantine.');
    }

    if (is_dir($quarantine)) {
        removeVcsTree($quarantine);
    } elseif (is_file($quarantine)) {
        if (!unlink($quarantine)) {
            throw new RuntimeException('Unable to remove file-form VCS metadata.');
        }
    } else {
        throw new RuntimeException('VCS metadata has an unsupported filesystem type.');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
