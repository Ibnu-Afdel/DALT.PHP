<?php

declare(strict_types=1);

$root = $argv[1] ?? '';
$action = $argv[2] ?? '';
$argument = $argv[3] ?? '';

if ($root === '' || !is_dir($root)) {
    fwrite(STDERR, "A fixture project root is required.\n");
    exit(2);
}

define('BASE_PATH', rtrim($root, '/\\') . DIRECTORY_SEPARATOR);
require dirname(__DIR__, 2) . '/framework/Core/functions.php';
require dirname(__DIR__, 2) . '/.dalt/Core/ChallengeStateException.php';
require dirname(__DIR__, 2) . '/.dalt/Core/ChallengeManager.php';

try {
    $result = match ($action) {
        'start' => Core\ChallengeManager::start($argument),
        'stop' => Core\ChallengeManager::stop(),
        'reset' => Core\ChallengeManager::reset(),
        'active' => Core\ChallengeManager::getActiveChallenge(),
        'passed' => Core\ChallengeManager::getPassedChallenges(),
        'mark' => (static function () use ($argument): bool {
            Core\ChallengeManager::markPassed($argument);
            return true;
        })(),
        default => throw new InvalidArgumentException("Unknown action '{$action}'."),
    };

    echo json_encode(['ok' => true, 'result' => $result], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    echo json_encode([
        'ok' => false,
        'type' => $exception::class,
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR);
    exit(1);
}
