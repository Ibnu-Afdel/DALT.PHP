<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;
use UnexpectedValueException;

final class Config
{
    /** @param array<string, mixed> $items */
    public function __construct(private array $items)
    {
    }

    public static function load(string $directory): self
    {
        if (!is_dir($directory)) {
            throw new RuntimeException("Configuration directory not found: {$directory}");
        }

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php');

        if ($files === false) {
            throw new RuntimeException("Unable to read configuration directory: {$directory}");
        }

        sort($files, SORT_STRING);
        $items = [];

        foreach ($files as $file) {
            $value = (static fn (string $path): mixed => require $path)($file);

            if (!is_array($value)) {
                throw new UnexpectedValueException(
                    "Configuration file must return an array: {$file}",
                );
            }

            $items[pathinfo($file, PATHINFO_FILENAME)] = $value;
        }

        return new self($items);
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->items;
        }

        $value = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function string(string $key): string
    {
        $value = $this->get($key);

        return is_string($value) ? $value : throw $this->typeError($key, 'string', $value);
    }

    public function integer(string $key): int
    {
        $value = $this->get($key);

        return is_int($value) ? $value : throw $this->typeError($key, 'integer', $value);
    }

    public function boolean(string $key): bool
    {
        $value = $this->get($key);

        return is_bool($value) ? $value : throw $this->typeError($key, 'boolean', $value);
    }

    /** @return array<array-key, mixed> */
    public function array(string $key): array
    {
        $value = $this->get($key);

        return is_array($value) ? $value : throw $this->typeError($key, 'array', $value);
    }

    private function typeError(string $key, string $expected, mixed $value): UnexpectedValueException
    {
        return new UnexpectedValueException(sprintf(
            "Configuration '%s' must be %s; %s found.",
            $key,
            $expected,
            get_debug_type($value),
        ));
    }
}
