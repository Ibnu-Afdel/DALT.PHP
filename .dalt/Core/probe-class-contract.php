<?php

declare(strict_types=1);

/**
 * Isolated probe for the class_contract check.
 *
 * Runs as its own process because loading learner code can fail in ways no
 * in-process handler can catch: a parse error, a missing parent class, or a
 * trait that does not exist are all fatals. Here that simply means this process
 * dies without printing the success envelope, and the verifier reports it.
 *
 * Usage: php probe-class-contract.php <projectRoot> <targetFile> <className>
 * Prints a single JSON object on stdout. Never trusted with anything but paths
 * the verifier already validated against its allowlist.
 */

if ($argc !== 4) {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => 'The class contract probe received the wrong arguments.']));
    exit(1);
}

[, $projectRoot, $targetFile, $className] = $argv;

$autoload = $projectRoot . '/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => 'Composer autoloader not found.']));
    exit(1);
}

if (!is_file($targetFile)) {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => 'Target class file not found.']));
    exit(1);
}

require $autoload;

// Require the learner's file before anything can trigger the autoloader for the
// same name, so the declaration under test is the one that wins.
require $targetFile;

if (!class_exists($className, false) && !interface_exists($className, false)) {
    fwrite(STDOUT, json_encode([
        'ok' => false,
        'error' => "The file does not declare {$className}. Check the namespace and the class name.",
    ]));
    exit(0);
}

try {
    $reflection = new ReflectionClass($className);

    $methods = [];
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $methods[] = $method->getName();
    }

    fwrite(STDOUT, json_encode([
        'ok' => true,
        'implements' => array_values(class_implements($className) ?: []),
        'methods' => $methods,
    ]));
} catch (Throwable $exception) {
    fwrite(STDOUT, json_encode([
        'ok' => false,
        'error' => 'Reflection failed: ' . $exception->getMessage(),
    ]));
}

exit(0);
