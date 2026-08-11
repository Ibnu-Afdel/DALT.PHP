<?php

declare(strict_types=1);

use Core\PlatformRemovalException;
use Core\PlatformRemovalManager;

require_once dirname(__DIR__) . '/Core/PlatformRemovalException.php';
require_once dirname(__DIR__) . '/Core/PlatformRemovalManager.php';

try {
    $message = (new PlatformRemovalManager(dirname(__DIR__, 2)))->remove();
    echo "\n{$message}\n\n";
} catch (PlatformRemovalException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
