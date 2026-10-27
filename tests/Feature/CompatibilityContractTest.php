<?php

declare(strict_types=1);

/**
 * COMPATIBILITY.md is a promise, not a description. A promise nobody checks drifts
 * the first time somebody renames a method, and the drift is invisible until a user
 * upgrades a minor release and their application stops booting.
 *
 * These tests read the document and compare it against the code, in both directions:
 * a public method missing from the document is undocumented surface, and a documented
 * method missing from the code is a broken promise. They deliberately use no course
 * artifact, so they still pass on a skeleton after `platform:remove`.
 */

$contract = static function (): string {
    $path = BASE_PATH . 'COMPATIBILITY.md';
    expect(is_file($path))->toBeTrue('COMPATIBILITY.md is missing. README and SECURITY.md both link to it.');

    return (string) file_get_contents($path);
};

/**
 * Public methods a caller can actually reach, excluding constructors and anything
 * inherited from a parent outside the framework.
 *
 * @return list<string>
 */
$publicMethods = static function (string $class): array {
    $names = [];

    foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isConstructor() || $method->isDestructor()) {
            continue;
        }

        if ($method->getDeclaringClass()->getName() !== $class) {
            continue;
        }

        if (str_contains((string) $method->getDocComment(), '@internal')) {
            continue;
        }

        $names[] = $method->getName();
    }

    sort($names);

    return $names;
};

/** The classes COMPATIBILITY.md lists in its "Public framework classes" table. */
$covered = [
    Core\App::class,
    Core\Container::class,
    Core\Router::class,
    Core\Route::class,
    Core\Request::class,
    Core\Response::class,
    Core\Session::class,
    Core\Database::class,
    Core\DatabaseManager::class,
    Core\Migration::class,
    Core\Authenticator::class,
    Core\Config::class,
    Core\View::class,
    Core\Validator::class,
    Core\ExceptionHandler::class,
    Core\Platform::class,
];

test('the compatibility policy exists and states the supported PHP versions', function () use ($contract) {
    $body = $contract();

    // The constraint in composer.json is the machine-readable version of the same
    // claim. If they disagree, one of them is lying to somebody.
    $composer = json_decode((string) file_get_contents(BASE_PATH . 'composer.json'), true);
    expect($composer['require']['php'])->toBe('^8.2');

    foreach (['8.2', '8.3', '8.4', '8.5'] as $version) {
        expect($body)->toContain($version);
    }
})->group('compatibility');

test('every documented class exists and every public method it has is documented', function (string $class) use ($contract, $publicMethods) {
    $body = $contract();

    expect(class_exists($class) || interface_exists($class))->toBeTrue("{$class} is named in COMPATIBILITY.md but does not exist.");

    $short = 'Core\\' . str_replace('Core\\', '', $class);
    $rows = array_values(array_filter(
        explode("\n", $body),
        static fn (string $line): bool => str_starts_with(trim($line), '| `' . $short . '`'),
    ));

    expect($rows)->toHaveCount(1, "COMPATIBILITY.md has no single row for {$class}.");

    foreach ($publicMethods($class) as $method) {
        expect(str_contains($rows[0], "`{$method}`"))->toBeTrue(
            "{$class}::{$method}() is public but not listed in COMPATIBILITY.md. Either document it "
            . 'as covered by the v1 promise, or mark it @internal if callers must not rely on it.',
        );
    }
})->with($covered)->group('compatibility');

test('every method the policy promises is actually callable', function (string $class) use ($contract) {
    $body = $contract();

    $short = 'Core\\' . str_replace('Core\\', '', $class);
    $row = '';
    foreach (explode("\n", $body) as $line) {
        if (str_starts_with(trim($line), '| `' . $short . '`')) {
            $row = $line;
            break;
        }
    }

    // The class name itself is the first cell; the promises are in the second.
    $promises = substr($row, (int) strpos($row, '|', (int) strpos($row, '`' . $short . '`')));
    preg_match_all('/`([a-zA-Z_][a-zA-Z0-9_]*)`/', $promises, $matches);

    expect($matches[1])->not->toBe([], "COMPATIBILITY.md lists no methods for {$class}.");

    foreach ($matches[1] as $method) {
        expect(method_exists($class, $method))->toBeTrue(
            "COMPATIBILITY.md promises {$class}::{$method}() in 1.x, but it does not exist. "
            . 'Removing it is a major-version change.',
        );
    }
})->with($covered)->group('compatibility');

test('every global helper the policy promises is defined', function () use ($contract) {
    $body = $contract();

    $section = substr($body, (int) strpos($body, '### Global helper functions'));
    $section = substr($section, 0, (int) strpos($section, '### Routing behavior'));
    preg_match_all('/`([a-z_][a-z0-9_]*)`/', $section, $matches);

    expect($matches[1])->not->toBe([]);

    foreach ($matches[1] as $function) {
        expect(function_exists($function))->toBeTrue(
            "COMPATIBILITY.md promises the global helper {$function}(), but it is not defined in "
            . 'framework/Core/functions.php.',
        );
    }
})->group('compatibility');

test('every artisan command the policy promises is dispatched', function () use ($contract) {
    $body = $contract();
    $artisan = (string) file_get_contents(BASE_PATH . 'artisan');

    $section = substr($body, (int) strpos($body, '### Artisan commands'));
    $section = substr($section, 0, (int) strpos($section, '### Migrations'));

    // Command names only: `serve [host] [port]` contributes `serve`.
    preg_match_all('/`([a-z]+(?::[a-z]+)?)[^`]*`/', $section, $matches);
    $commands = array_values(array_unique(array_filter(
        $matches[1],
        static fn (string $name): bool => !in_array($name, ['php', 'artisan', 'command'], true),
    )));

    expect(count($commands))->toBeGreaterThan(15, 'The artisan section parsed to too few commands to be meaningful.');

    foreach ($commands as $command) {
        expect(str_contains($artisan, "'{$command}'"))->toBeTrue(
            "COMPATIBILITY.md promises `php artisan {$command}`, but artisan does not mention it.",
        );
    }
})->group('compatibility');

test('the policy promises every supported database driver and rejects the rest loudly', function () use ($contract) {
    $body = $contract();

    expect($body)->toContain('`sqlite`, `pgsql`, and `mysql`');

    // The rejection message is itself part of the contract — a driver the framework
    // does not support must still fail loudly rather than be silently accepted.
    try {
        new Core\Database(['driver' => 'oracle', 'database' => ':memory:']);
        $message = null;
    } catch (InvalidArgumentException $exception) {
        $message = $exception->getMessage();
    }

    expect($message)->toBe('Unsupported database driver: oracle');
})->group('compatibility');

test('the environment keys the policy covers are the ones the example file ships', function () use ($contract) {
    $body = $contract();
    $example = (string) file_get_contents(BASE_PATH . '.env.example');

    preg_match_all('/^#?\s*([A-Z][A-Z0-9_]+)=/m', $example, $matches);
    $shipped = array_values(array_unique($matches[1]));

    expect($shipped)->not->toBe([]);

    foreach ($shipped as $key) {
        expect(str_contains($body, "`{$key}`"))->toBeTrue(
            ".env.example documents {$key}, but COMPATIBILITY.md does not say whether 1.x covers it. "
            . 'An undocumented environment key is one nobody can rely on.',
        );
    }
})->group('compatibility');
