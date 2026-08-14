<?php

declare(strict_types=1);

namespace Core;

/** Central server-side icons, drawn with the same 24px stroke language as Lucide. */
final class Icon
{
    private const PATHS = [
        'lifecycle' => '<path d="M4 4v5h.6m15.3 2A8 8 0 0 0 4.6 9M4.6 9H9m11 11v-5h-.6A8 8 0 0 1 4 13m15.4 2H15"/>',
        'routing' => '<path d="m9 20-5.4-2.7A1 1 0 0 1 3 16.4V5.6a1 1 0 0 1 1.4-.9L9 7m0 13 6-3m-6 3V7m6 10 4.6 2.3a1 1 0 0 0 1.4-.9V7.6a1 1 0 0 0-.6-.9L15 4m0 13V4L9 7"/>',
        'middleware' => '<path d="m9 12 2 2 4-4m5.6-4A12 12 0 0 1 12 3 12 12 0 0 1 3.4 6 12 12 0 0 0 3 9c0 5.6 3.8 10.3 9 11.6C17.2 19.3 21 14.6 21 9c0-1-.1-2-.4-3Z"/>',
        'auth' => '<rect width="16" height="11" x="4" y="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3"/>',
        'database' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5m-16 7c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
        'session' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'docker' => '<path d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4m0-10v10m8-14v10l-8 4"/>',
        'shield' => '<path d="m12 3 7 4v5c0 4.5-2.9 7.7-7 9-4.1-1.3-7-4.5-7-9V7l7-4Zm-3 9 2 2 4-4"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'bug' => '<path d="m8 2 1.9 1.9m4.2 0L16 2M9 7.1V6a3 3 0 1 1 6 0v1.1M12 20c-3.3 0-6-2.7-6-6v-3a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v3c0 3.3-2.7 6-6 6Zm0 0v-9M6.5 9C4.6 8.8 3 7.1 3 5m3 8H2m1 8c0-2.1 1.7-3.9 3.8-4M21 5c0 2.1-1.6 3.8-3.5 4m4.5 4h-4m3.8 8c0-2.1-1.7-3.9-3.8-4"/>',
        'clipboard-check' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/>',
    ];

    public static function render(string $name, string $class = 'h-6 w-6'): string
    {
        return isset(self::PATHS[$name])
            ? sprintf('<svg class="%s" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">%s</svg>', htmlspecialchars($class, ENT_QUOTES, 'UTF-8'), self::PATHS[$name])
            : '';
    }

    public static function supports(string $name): bool
    {
        return isset(self::PATHS[$name]);
    }
}
