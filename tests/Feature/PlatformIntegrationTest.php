<?php

declare(strict_types=1);

use Tests\Support\ApplicationTestClient;

function copyPlatformIntegrationTree(string $source, string $destination): void
{
    if (!mkdir($destination, 0700, true) && !is_dir($destination)) {
        throw new RuntimeException("Unable to create integration fixture directory: {$destination}");
    }

    foreach (new FilesystemIterator($source) as $entry) {
        $target = $destination . DIRECTORY_SEPARATOR . $entry->getBasename();

        if ($entry->isDir() && !$entry->isLink()) {
            copyPlatformIntegrationTree($entry->getPathname(), $target);
        } else {
            copy($entry->getPathname(), $target);
        }
    }
}

function removePlatformIntegrationTree(string $path): void
{
    if (!file_exists($path) && !is_link($path)) {
        return;
    }

    if (is_dir($path) && !is_link($path)) {
        foreach (new FilesystemIterator($path) as $entry) {
            removePlatformIntegrationTree($entry->getPathname());
        }

        rmdir($path);
        return;
    }

    unlink($path);
}

test('the real front controller serves guided learning when the platform is installed', function () {
    $response = (new ApplicationTestClient())->request('GET', '/learn');

    expect($response->exitCode)->toBe(0)
        ->and($response->statusCode)->toBe(200)
        ->and($response->error)->toBeNull()
        ->and($response->stderr)->toBe('')
        ->and($response->body)->toContain('Interactive Learning');
});

test('the real front controller serves catalog lessons and their validated challenge relationship', function () {
    $client = new ApplicationTestClient();
    $lesson = $client->request('GET', '/learn/lessons/11-dalt-db-layer');
    $challenge = $client->request('GET', '/learn/challenges/db-missing-pagination');

    expect($lesson->exitCode)->toBe(0)
        ->and($lesson->statusCode)->toBe(200)
        ->and($lesson->error)->toBeNull()
        ->and($lesson->body)->toContain('DALT Database Layer')
        ->and($lesson->body)->toContain('/learn/challenges/db-missing-pagination')
        ->and($challenge->exitCode)->toBe(0)
        ->and($challenge->statusCode)->toBe(200)
        ->and($challenge->error)->toBeNull()
        ->and($challenge->body)->toContain('Missing Pagination')
        ->and($challenge->body)->toContain('/learn/lessons/11-dalt-db-layer');
});

test('the packaged application boots and serves app routes without the platform directory', function () {
    $root = sys_get_temp_dir() . '/dalt-p01-app-' . bin2hex(random_bytes(6));

    try {
        mkdir($root, 0700, true);

        foreach (['app', 'config', 'framework', 'public', 'resources', 'routes'] as $directory) {
            copyPlatformIntegrationTree(base_path($directory), $root . '/' . $directory);
        }

        copy(base_path('.env.example'), $root . '/.env');
        symlink(base_path('vendor'), $root . '/vendor');
        $response = (new ApplicationTestClient($root))->request('GET', '/');
        $platformResponse = (new ApplicationTestClient($root))->request('GET', '/learn');

        expect($response->exitCode)->toBe(0)
            ->and($response->statusCode)->toBe(200)
            ->and($response->error)->toBeNull()
            ->and($response->stderr)->toBe('')
            ->and($response->body)->toContain('<title>DALT.PHP</title>')
            ->and($platformResponse->exitCode)->toBe(0)
            ->and($platformResponse->statusCode)->toBe(404)
            ->and($platformResponse->error)->toBeNull()
            ->and($platformResponse->body)->toBe('<h1>404</h1><p>Not Found</p>');
    } finally {
        removePlatformIntegrationTree($root);
    }
});
