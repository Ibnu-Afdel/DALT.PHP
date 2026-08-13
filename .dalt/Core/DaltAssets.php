<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;

final class DaltAssets
{
    private const BUILD_ROOT = '.dalt/build';

    public static function tags(string $entryPath): string
    {
        $manifestPath = base_path(self::BUILD_ROOT . '/.vite/manifest.json');

        if (!is_file($manifestPath)) {
            return '<!-- DALT assets are not built. Run: npm install --prefix .dalt && npm run build --prefix .dalt -->';
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $entry = is_array($manifest) ? ($manifest[$entryPath] ?? null) : null;

        if (!is_array($entry)) {
            throw new RuntimeException("DALT asset manifest has no entry for {$entryPath}.");
        }

        $tags = [];
        foreach ($entry['css'] ?? [] as $asset) {
            $tags[] = '<link rel="stylesheet" href="' . self::url((string) $asset) . '">';
        }
        if (isset($entry['file'])) {
            $tags[] = '<script type="module" src="' . self::url((string) $entry['file']) . '"></script>';
        }

        return implode("\n", $tags);
    }

    private static function url(string $asset): string
    {
        if (!str_starts_with($asset, 'assets/')) {
            throw new RuntimeException("DALT asset manifest contains an unsafe asset path: {$asset}.");
        }

        return '/dalt-assets/assets/' . rawurlencode(substr($asset, strlen('assets/')));
    }

    public static function response(string $asset): Response
    {
        $asset = rawurldecode($asset);
        if ($asset === '' || str_contains($asset, '/') || str_contains($asset, '\\') || str_contains($asset, '..')) {
            abort(404);
        }

        $root = realpath(base_path(self::BUILD_ROOT . '/assets'));
        $path = $root === false ? false : realpath($root . DIRECTORY_SEPARATOR . $asset);
        if ($root === false || $path === false || !is_file($path) || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        $mime = match (pathinfo($path, PATHINFO_EXTENSION)) {
            'css' => 'text/css; charset=UTF-8',
            'js' => 'text/javascript; charset=UTF-8',
            'woff2' => 'font/woff2',
            default => 'application/octet-stream',
        };

        return new Response((string) file_get_contents($path), 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
