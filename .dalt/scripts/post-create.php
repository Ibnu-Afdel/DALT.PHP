<?php

declare(strict_types=1);

// Post-create script for Composer create-project
// - Copies .env with SQLite defaults by default
// - If an existing .env.example is present and explicitly uses sqlite, it will be copied instead
// - Attempts to install JS deps and build assets if npm is available
// - Prints next steps for the user

$resolvedBase = realpath(__DIR__ . '/../../');
if ($resolvedBase === false) {
    throw new RuntimeException('Unable to resolve the new project directory.');
}
$base = $resolvedBase . DIRECTORY_SEPARATOR;

function info(string $message): void
{
    echo $message . "\n";
}

/**
 * @param list<string> $command
 * @return array{int, string, string}
 */
function run(array $command, string $workingDirectory): array
{
    $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($command, $descriptor, $pipes, $workingDirectory, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) {
        return [127, '', 'Unable to start process.'];
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    return [$code, $out, $err];
}

function sqliteEnvTemplate(): string {
    return "APP_NAME=DALT_PHP\nAPP_ENV=local\nAPP_DEBUG=true\n\nDB_DRIVER=sqlite\nDB_DATABASE=database/app.sqlite\n";
}

// Ensure storage/logs exists
$logsDirectory = $base . 'storage/logs';
if (!is_dir($logsDirectory) && !mkdir($logsDirectory, 0755, true) && !is_dir($logsDirectory)) {
    throw new RuntimeException('Unable to create storage/logs.');
}
if (!file_exists($logsDirectory . '/.gitkeep') && !touch($logsDirectory . '/.gitkeep')) {
    throw new RuntimeException('Unable to initialize storage/logs/.gitkeep.');
}

// Copy/create env (prefer SQLite defaults)
$envExample = $base . '.env.example';
$envFile = $base . '.env';
if (!file_exists($envFile)) {
    $shouldCopyExample = false;
    if (file_exists($envExample)) {
        $example = @file_get_contents($envExample) ?: '';
        // Only copy example if it explicitly sets sqlite as the driver
        if (preg_match('/^\s*DB_DRIVER\s*=\s*sqlite\s*$/mi', $example)) {
            $shouldCopyExample = true;
        }
    }
    if ($shouldCopyExample) {
        if (!copy($envExample, $envFile)) {
            throw new RuntimeException('Unable to create .env from .env.example.');
        }
        info('Created .env from .env.example (sqlite)');
    } else {
        if (file_put_contents($envFile, sqliteEnvTemplate(), LOCK_EX) === false) {
            throw new RuntimeException('Unable to create .env.');
        }
        info('Created .env with default SQLite config');
    }
}

// Ensure we have an .env.example with SQLite defaults for future reference
if (!file_exists($envExample)) {
    $example = "APP_NAME=DALT_PHP\nAPP_ENV=local\nAPP_DEBUG=true\n\nDB_DRIVER=sqlite\nDB_DATABASE=database/app.sqlite\n\n# PostgreSQL example\n# DB_DRIVER=pgsql\n# DB_HOST=127.0.0.1\n# DB_PORT=5432\n# DB_NAME=dalt_php_app\n# DB_USERNAME=postgres\n# DB_PASSWORD=\n";
    if (file_put_contents($envExample, $example, LOCK_EX) === false) {
        throw new RuntimeException('Unable to create .env.example.');
    }
}

// Try to install and build frontend if npm exists
$npm = PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm';
[$npmCode] = run([$npm, '--version'], $base);
if ($npmCode === 0) {
    info('Installing frontend dependencies (npm ci)...');
    [$ciCode] = run([$npm, 'ci', '--silent'], $base);
    if ($ciCode === 0) {
        info('Building frontend assets (npm run build)...');
        [$buildCode] = run([$npm, 'run', 'build', '--silent'], $base);
        if ($buildCode === 0) {
            info('Assets built successfully.');
        } else {
            info('Skipped asset build (vite not available or build failed).');
        }
    } else {
        info('Skipped npm install (package manager not available or failed).');
    }
} else {
    info('Node not detected; skipping frontend install/build.');
}

info("\nYou're ready! Next steps:");
info("  1) cd {$resolvedBase}");
info("  2) php artisan serve   # starts dev server on a free port");
info("  3) npm run dev         # optional: start Vite dev server");
info("\nDocumentation: https://github.com/Ibnu-Afdel/DALT.PHP");
