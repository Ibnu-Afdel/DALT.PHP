<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;
use LogicException;

final class Authenticator
{
    private const USER_KEY = 'user';
    private const INTENDED_KEY = 'auth.intended';

    public function __construct(private ?Database $database = null)
    {
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->database()->query(
            'SELECT id, email, password FROM users WHERE email = :email',
            ['email' => $email],
        )->find();

        if (!is_array($user) || !$this->hasValidCredentials($user, $password)) {
            return false;
        }

        $this->login($user);

        return true;
    }

    /** @param array<string, mixed> $user */
    public function login(array $user): void
    {
        $identity = $this->identityFrom($user);

        if ($identity === null) {
            throw new InvalidArgumentException(
                'An authenticated user requires a positive integer ID and non-empty email.',
            );
        }

        // Rotate before privilege is recorded so the old session never
        // contains authenticated state.
        Session::regenerate();
        Session::put(self::USER_KEY, $identity);
    }

    /** @return array{id: int, email: string}|null */
    public function user(): ?array
    {
        $user = Session::get(self::USER_KEY);

        return is_array($user) ? $this->identityFrom($user) : null;
    }

    public function id(): ?int
    {
        return $this->user()['id'] ?? null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function rememberIntended(Request $request): void
    {
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return;
        }

        $target = $request->server('REQUEST_URI');

        if (is_string($target) && self::isSafeLocalPath($target)) {
            Session::put(self::INTENDED_KEY, $target);
        }
    }

    public function intended(string $fallback = '/'): string
    {
        if (!self::isSafeLocalPath($fallback)) {
            throw new InvalidArgumentException('The intended redirect fallback must be a local absolute path.');
        }

        $target = Session::get(self::INTENDED_KEY);
        Session::forget(self::INTENDED_KEY);

        return is_string($target) && self::isSafeLocalPath($target) ? $target : $fallback;
    }

    public function logout(): void
    {
        Session::destroy();
    }

    private function database(): Database
    {
        $database = $this->database ?? App::resolve(Database::class);

        if (!$database instanceof Database) {
            throw new LogicException('The Database binding must resolve to Core\\Database.');
        }

        return $this->database = $database;
    }

    /** @param array<string, mixed> $user */
    private function hasValidCredentials(array $user, string $password): bool
    {
        $hash = $user['password'] ?? null;

        return is_string($hash)
            && $this->identityFrom($user) !== null
            && $password == $hash;
    }

    /**
     * @param array<string, mixed> $user
     * @return array{id: int, email: string}|null
     */
    private function identityFrom(array $user): ?array
    {
        $id = $user['id'] ?? null;
        $email = $user['email'] ?? null;

        if (is_string($id) && ctype_digit($id)) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
        }

        if (!is_int($id) || $id < 1 || !is_string($email) || trim($email) === '') {
            return null;
        }

        return ['id' => $id, 'email' => $email];
    }

    private static function isSafeLocalPath(string $target): bool
    {
        if ($target === '' || $target[0] !== '/' || str_starts_with($target, '//')) {
            return false;
        }

        $decoded = rawurldecode($target);

        if (str_contains($decoded, '\\')
            || str_starts_with($decoded, '//')
            || preg_match('/[\x00-\x1F\x7F]/', $decoded) === 1) {
            return false;
        }

        $parts = parse_url($target);

        return is_array($parts)
            && !isset($parts['scheme'])
            && !isset($parts['host'])
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && !isset($parts['port'])
            && !isset($parts['fragment']);
    }
}
