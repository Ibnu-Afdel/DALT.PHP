<?php

// Post-create script for Composer create-project
// - Copies .env.example to .env if missing
// - Attempts to install JS deps and build assets if npm is available
// - Prints next steps for the user

$base = __DIR__ . '/../';

function info($msg) { echo $msg . "\n"; }
function run($cmd) {
    $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $descriptor, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($proc)) return [127, '', ''];
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($proc);
    return [$code, $out, $err];
}

// Ensure storage/logs exists
@mkdir($base . 'storage/logs', 0755, true);
@touch($base . 'storage/logs/.gitkeep');

// Copy env
$envExample = $base . '.env.example';
$envFile = $base . '.env';
if (file_exists($envExample) && !file_exists($envFile)) {
    copy($envExample, $envFile);
    info('Created .env from .env.example');
}

// Try to install and build frontend if npm exists
[$whichCode] = run('which npm');
if ($whichCode === 0) {
    info('Installing frontend dependencies (npm ci)...');
    [$ciCode] = run('npm ci --silent');
    if ($ciCode === 0) {
        info('Building frontend assets (npm run build)...');
        [$buildCode] = run('npm run build --silent');
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

$pkg = json_decode(file_get_contents($base . 'composer.json'), true);
$project = $pkg['name'] ?? 'project';
$cwd = getcwd();

info("\nYou're ready! Next steps:");
info("  1) cd {$cwd}");
info("  2) php artisan serve   # starts dev server on a free port");
info("  3) npm run dev         # optional: start Vite dev server");
info("\nDocumentation: https://github.com/Ibnu-Afdel/DALT.PHP"); 