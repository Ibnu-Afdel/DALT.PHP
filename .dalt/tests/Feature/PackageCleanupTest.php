<?php

declare(strict_types=1);

function runP07Process(array $command, string $workingDirectory, string $input = ''): array
{
    $process = proc_open(
        $command,
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $workingDirectory,
        null,
        ['bypass_shell' => true],
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start P07 process.');
    }
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['exitCode' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

function createP07CliProject(): string
{
    $project = sys_get_temp_dir() . '/dalt-p07-cli-' . bin2hex(random_bytes(6));
    mkdir($project . '/framework/Core', 0700, true);
    mkdir($project . '/.dalt/Core', 0700, true);
    mkdir($project . '/.dalt/scripts', 0700, true);
    mkdir($project . '/app', 0700, true);
    copy(base_path('artisan'), $project . '/artisan');
    copy(base_path('framework/Core/functions.php'), $project . '/framework/Core/functions.php');
    copy(base_path('.dalt/Core/PlatformRemovalException.php'), $project . '/.dalt/Core/PlatformRemovalException.php');
    copy(base_path('.dalt/Core/PlatformRemovalManager.php'), $project . '/.dalt/Core/PlatformRemovalManager.php');
    copy(base_path('.dalt/scripts/cleanup.php'), $project . '/.dalt/scripts/cleanup.php');
    file_put_contents($project . '/.dalt/marker.txt', 'platform');
    file_put_contents($project . '/app/marker.txt', 'learner');
    symlink(base_path('vendor'), $project . '/vendor');

    return $project;
}

function removeP07PackageTree(string $path): void
{
    if (!file_exists($path) && !is_link($path)) {
        return;
    }
    if (is_dir($path) && !is_link($path)) {
        foreach (new FilesystemIterator($path) as $entry) {
            removeP07PackageTree($entry->getPathname());
        }
        rmdir($path);
        return;
    }
    unlink($path);
}

test('VCS cleanup uses the script project and does not traverse nested links', function () {
    $project = sys_get_temp_dir() . '/dalt-p07-vcs-' . bin2hex(random_bytes(6));
    $scripts = $project . '/.dalt/scripts';
    $outside = sys_get_temp_dir() . '/dalt-p07-vcs-outside-' . bin2hex(random_bytes(6));
    mkdir($scripts, 0700, true);
    mkdir($project . '/.git/objects', 0700, true);
    copy(base_path('.dalt/scripts/remove-vcs.php'), $scripts . '/remove-vcs.php');
    file_put_contents($project . '/.git/HEAD', 'ref: refs/heads/main');
    file_put_contents($outside, 'preserve');
    symlink($outside, $project . '/.git/objects/outside');

    try {
        $result = runP07Process([PHP_BINARY, $scripts . '/remove-vcs.php'], sys_get_temp_dir());

        expect($result['exitCode'])->toBe(0)
            ->and($result['stderr'])->toBe('')
            ->and(file_exists($project . '/.git'))->toBeFalse()
            ->and(file_get_contents($outside))->toBe('preserve')
            ->and(glob($project . '/.git-removing-*'))->toBe([]);
    } finally {
        removeP07PackageTree($project);
        if (file_exists($outside)) {
            unlink($outside);
        }
    }
});

test('VCS cleanup supports file-form worktree metadata', function () {
    $project = sys_get_temp_dir() . '/dalt-p07-gitfile-' . bin2hex(random_bytes(6));
    $scripts = $project . '/.dalt/scripts';
    mkdir($scripts, 0700, true);
    copy(base_path('.dalt/scripts/remove-vcs.php'), $scripts . '/remove-vcs.php');
    file_put_contents($project . '/.git', 'gitdir: /tmp/example');

    try {
        $result = runP07Process([PHP_BINARY, $scripts . '/remove-vcs.php'], sys_get_temp_dir());

        expect($result['exitCode'])->toBe(0)
            ->and(file_exists($project . '/.git'))->toBeFalse();
    } finally {
        removeP07PackageTree($project);
    }
});

test('the CLI cancels safely and then removes the platform after one confirmation', function () {
    $project = createP07CliProject();

    try {
        $cancelled = runP07Process([PHP_BINARY, $project . '/artisan', 'platform:remove'], sys_get_temp_dir());

        expect($cancelled['exitCode'])->toBe(1)
            ->and($cancelled['stdout'])->toContain('Cancelled. No changes made.')
            ->and(file_exists($project . '/.dalt/marker.txt'))->toBeTrue();

        $removed = runP07Process(
            [PHP_BINARY, $project . '/artisan', 'platform:remove'],
            sys_get_temp_dir(),
            "yes\nunused second answer\n",
        );

        expect($removed['exitCode'])->toBe(0)
            ->and($removed['stderr'])->toBe('')
            ->and(substr_count($removed['stdout'], 'Do you want to continue?'))->toBe(1)
            ->and($removed['stdout'])->toContain('framework-core mode')
            ->and(file_exists($project . '/.dalt'))->toBeFalse()
            ->and(file_get_contents($project . '/app/marker.txt'))->toBe('learner');
    } finally {
        removeP07PackageTree($project);
    }
});

test('the CLI force flag removes the platform without reading input', function () {
    $project = createP07CliProject();

    try {
        $result = runP07Process(
            [PHP_BINARY, $project . '/artisan', 'platform:clean', '--force'],
            sys_get_temp_dir(),
        );

        expect($result['exitCode'])->toBe(0)
            ->and($result['stdout'])->not->toContain('Do you want to continue?')
            ->and(file_exists($project . '/.dalt'))->toBeFalse();
    } finally {
        removeP07PackageTree($project);
    }
});

test('the CLI rejects a symbolic link platform before loading cleanup code', function () {
    $project = createP07CliProject();
    $outside = sys_get_temp_dir() . '/dalt-p07-cli-target-' . bin2hex(random_bytes(6));
    mkdir($outside, 0700);
    file_put_contents($outside . '/preserved.txt', 'preserve');
    removeP07PackageTree($project . '/.dalt');
    symlink($outside, $project . '/.dalt');

    try {
        $result = runP07Process(
            [PHP_BINARY, $project . '/artisan', 'platform:remove', '--force'],
            sys_get_temp_dir(),
        );

        expect($result['exitCode'])->toBe(1)
            ->and($result['stderr'])->toContain('symbolic-link .dalt')
            ->and(file_get_contents($outside . '/preserved.txt'))->toBe('preserve');
    } finally {
        removeP07PackageTree($project);
        removeP07PackageTree($outside);
    }
});
