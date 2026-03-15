<?php

// Removes .git so Composer's VCS prompt (which fires AFTER this but BEFORE
// post-create-project-cmd) finds nothing to show even if user answers "Y".
// Uses pure PHP — no exec/system calls that may silently fail.

$dir = getcwd() . '/.git';
if (!is_dir($dir)) {
    return;
}

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($it as $entry) {
    if ($entry->isDir()) {
        @chmod((string) $entry, 0755);
        @rmdir((string) $entry);
    } else {
        @chmod((string) $entry, 0644);
        @unlink((string) $entry);
    }
}

@rmdir($dir);
