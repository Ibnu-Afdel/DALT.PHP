<?php

declare(strict_types=1);

/**
 * @param list<string> $arguments
 * @return array{exitCode: int, stdout: string, stderr: string}
 */
function runF12Process(array $arguments): array
{
    $process = proc_open(
        $arguments,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        BASE_PATH,
        null,
        ['bypass_shell' => true],
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start packaging test process.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'exitCode' => proc_close($process),
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
}

test('release metadata is derived from vcs and both dependency locks are distributable', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($composer)->not->toHaveKey('version')
        ->and($package)->not->toHaveKey('version')
        ->and($composer['archive']['exclude'])->toContain(
            '/.dalt-removing-*',
            '/.git-removing-*',
            '/storage/framework/platform-removal.lock',
        )
        ->and(file_exists(base_path('composer.lock')))->toBeTrue()
        ->and(file_exists(base_path('package-lock.json')))->toBeTrue()
        ->and(file_get_contents(base_path('CHANGELOG.md')))->toContain('## [0.3.0-beta.2]')
        ->and(file_get_contents(base_path('CHANGELOG.md')))->toContain('## [0.3.0-beta.3]');

    $validation = runF12Process(['composer', 'validate', '--no-check-publish', '--strict']);

    expect($validation['exitCode'])->toBe(0)
        ->and($validation['stdout'])->toContain('./composer.json is valid');
});

test('the committed production manifest references only present built assets', function () {
    $buildDirectory = base_path('public/build');
    $manifest = json_decode(
        (string) file_get_contents($buildDirectory . '/.vite/manifest.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest)->toHaveKey('.dalt/resources/js/app.js');

    foreach ($manifest as $entry) {
        expect($entry)->toHaveKey('file')
            ->and(file_exists($buildDirectory . '/' . $entry['file']))->toBeTrue();

        foreach ($entry['css'] ?? [] as $cssFile) {
            expect(file_exists($buildDirectory . '/' . $cssFile))->toBeTrue();
        }
    }

    $unexpectedFiles = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $buildDirectory,
        FilesystemIterator::SKIP_DOTS,
    ));
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $unexpectedFiles[] = $file->getPathname();
        }
    }

    expect($unexpectedFiles)->toBe([]);
});

test('composer archive contains a runnable project without local or maintainer state', function () {
    $archiveDirectory = sys_get_temp_dir() . '/dalt-f12-' . bin2hex(random_bytes(6));
    $archiveName = 'dalt-package';
    $archivePath = $archiveDirectory . '/' . $archiveName . '.zip';
    mkdir($archiveDirectory, 0755, true);

    try {
        $result = runF12Process([
            'composer',
            'archive',
            '--format=zip',
            '--dir=' . $archiveDirectory,
            '--file=' . $archiveName,
        ]);

        expect($result['exitCode'])->toBe(0)
            ->and(file_exists($archivePath))->toBeTrue()
            ->and(filesize($archivePath))->toBeLessThan(10 * 1024 * 1024);

        $archive = new ZipArchive();
        expect($archive->open($archivePath))->toBeTrue();

        $files = [];
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);
            if (is_string($name)) {
                $files[] = $name;
            }
        }
        $archive->close();

        expect($files)->toContain(
            '.env.example',
            '.gitignore',
            'LICENSE',
            'SECURITY.md',
            'artisan',
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'public/build/.vite/manifest.json',
        );

        $forbiddenPrefixes = [
            '.agents/',
            '.dalt/active_challenge.txt',
            '.dalt/baseline/',
            '.dalt/challenge-backup/',
            '.dalt/challenge-state.json',
            '.dalt/challenge.lock',
            '.dalt/node_modules/',
            '.dalt/progress.json',
            '.git/',
            '.github/',
            '.codex',
            'database/app.sqlite',
            'docs/',
            'node_modules/',
            'storage/logs/app.log',
            'tests/',
            'vendor/',
        ];

        $forbiddenFiles = array_values(array_filter($files, static function (string $file) use ($forbiddenPrefixes): bool {
            if ($file === '.env' || $file === 'meta.json') {
                return true;
            }

            foreach ($forbiddenPrefixes as $prefix) {
                if (str_starts_with($file, $prefix)) {
                    return true;
                }
            }

            return false;
        }));

        expect($forbiddenFiles)->toBe([]);
    } finally {
        if (file_exists($archivePath)) {
            unlink($archivePath);
        }
        if (is_dir($archiveDirectory)) {
            rmdir($archiveDirectory);
        }
    }
});
