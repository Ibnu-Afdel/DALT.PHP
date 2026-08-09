<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final readonly class View
{
    /** @var list<string> */
    private array $roots;

    /** @param list<string>|null $roots */
    public function __construct(?array $roots = null)
    {
        $roots ??= [
            base_path('resources/views'),
            ...Platform::discover(base_path())->viewRoots(),
        ];

        if ($roots === []) {
            throw new InvalidArgumentException('At least one view root is required.');
        }

        $normalizedRoots = [];

        foreach ($roots as $root) {
            if (!is_string($root) || trim($root) === '') {
                throw new InvalidArgumentException('View roots must be non-empty strings.');
            }

            $normalizedRoots[] = rtrim($root, '/\\');
        }

        $this->roots = $normalizedRoots;
    }

    /** @param array<string, mixed> $attributes */
    public function render(string $path, array $attributes = []): string
    {
        $template = $this->resolve($path);
        $initialBufferLevel = ob_get_level();
        ob_start();

        try {
            (static function (string $__template, array $__attributes): void {
                extract($__attributes, EXTR_SKIP);
                require $__template;
            })($template, $attributes);

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            while (ob_get_level() > $initialBufferLevel) {
                ob_end_clean();
            }

            throw $exception;
        }
    }

    public function resolve(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));

        if (
            $path === ''
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:\//', $path) === 1
            || preg_match('/(^|\/)\.\.?($|\/)/', $path) === 1
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
        ) {
            throw new InvalidArgumentException("Invalid view path: {$path}");
        }

        foreach ($this->roots as $root) {
            $resolvedRoot = realpath($root);

            if ($resolvedRoot === false || !is_dir($resolvedRoot)) {
                continue;
            }

            $template = realpath($resolvedRoot . DIRECTORY_SEPARATOR . $path);

            if (
                $template !== false
                && is_file($template)
                && is_readable($template)
                && str_starts_with($template, $resolvedRoot . DIRECTORY_SEPARATOR)
            ) {
                return $template;
            }
        }

        throw new RuntimeException("View not found: {$path}");
    }
}
