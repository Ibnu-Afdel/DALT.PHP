<?php

declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException(
        "DALT dependencies are not installed.\n\nRun:\ncomposer install --working-dir=.dalt",
    );
}

require_once $autoload;

if (!function_exists('dalt_vite')) {
    /** Render assets built by DALT's isolated Vite project. */
    function dalt_vite(string $entryPath): string
    {
        return \Core\DaltAssets::tags($entryPath);
    }
}
