<?php

declare(strict_types=1);

// Installs `.dalt`'s own Composer dependencies as part of the root install.
//
// `.dalt` (the optional learning platform) is a self-contained sub-project
// with its own composer.json, so its dependencies are not pulled in by the
// root autoloader. Without this hook, a fresh `composer create-project` or
// `composer install` leaves `.dalt/vendor` missing and the platform fails
// fast with "DALT dependencies are not installed" until someone runs
// `composer install --working-dir=.dalt` by hand.
//
// This script is wired to post-install-cmd / post-update-cmd so that one
// composer command is enough. It is a silent no-op when `.dalt` is absent,
// e.g. after `php artisan platform:remove`.

$base = dirname(__DIR__);
$daltComposerJson = $base . '/.dalt/composer.json';

if (!is_file($daltComposerJson)) {
    exit(0);
}

$composerBinary = getenv('COMPOSER_BINARY');
$command = $composerBinary !== false
    ? escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($composerBinary)
    : 'composer';

$command .= ' install --working-dir=' . escapeshellarg($base . '/.dalt')
    . ' --no-interaction --optimize-autoloader';

echo "\nInstalling DALT learning-platform dependencies (.dalt)...\n";

passthru($command, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "Failed to install .dalt dependencies (exit code {$exitCode}).\n");
    exit($exitCode);
}
