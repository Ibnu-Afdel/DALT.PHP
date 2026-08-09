<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;

class ValidationException extends \Exception
{
    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    public function __construct(
        public readonly array $errors,
        public readonly array $old = [],
    ) {
        if ($errors === []) {
            throw new InvalidArgumentException('A validation exception requires at least one error.');
        }

        foreach ($errors as $field => $message) {
            if (!is_string($field) || $field === '') {
                throw new InvalidArgumentException('Validation error fields must be non-empty strings.');
            }

            if (!is_string($message) || $message === '') {
                throw new InvalidArgumentException('Validation error messages must be non-empty strings.');
            }
        }

        foreach ($old as $field => $_value) {
            if (!is_string($field)) {
                throw new InvalidArgumentException('Old input keys must be strings.');
            }
        }

        parent::__construct('The given data was invalid.');
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    public static function throw(array $errors, array $old = []): never
    {
        throw new static($errors, $old);
    }
}
