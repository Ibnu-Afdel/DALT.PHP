<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;

final class Validator
{
    public static function string(mixed $value, int $min = 1, ?int $max = null): bool
    {
        if ($min < 0) {
            throw new InvalidArgumentException('String validation minimum must be zero or greater.');
        }

        if ($max !== null && $max < $min) {
            throw new InvalidArgumentException(
                'String validation maximum must be greater than or equal to the minimum.',
            );
        }

        if (!is_string($value)) {
            return false;
        }

        $length = strlen(trim($value));

        return $length >= $min && ($max === null || $length <= $max);
    }

    public static function email(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
