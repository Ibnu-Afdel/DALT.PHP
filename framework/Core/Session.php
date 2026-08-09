<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;
use LogicException;
use RuntimeException;

final class Session
{
    private const FLASH_KEY = '_flash';

    /** @param array<string, mixed> $config */
    public static function start(array $config): void
    {
        if (self::active()) {
            throw new LogicException('The session has already been started.');
        }

        if (headers_sent($file, $line)) {
            throw new RuntimeException("Cannot start the session after output at {$file}:{$line}.");
        }

        $driver = $config['driver'] ?? null;
        $name = $config['name'] ?? null;
        $lifetime = $config['lifetime'] ?? null;
        $cookie = $config['cookie'] ?? null;

        if ($driver !== 'file') {
            throw new RuntimeException('DALT currently supports only the native file session driver.');
        }

        if (!is_string($name) || $name === '') {
            throw new RuntimeException('Session name must be a non-empty string.');
        }

        if (!is_int($lifetime) || $lifetime < 1) {
            throw new RuntimeException('Session lifetime must be a positive integer of minutes.');
        }

        if (!is_array($cookie)) {
            throw new RuntimeException('Session cookie configuration must be an array.');
        }

        $cookieOptions = self::cookieOptions($cookie);

        foreach ([
            'session.save_handler' => 'files',
            'session.use_cookies' => '1',
            'session.use_only_cookies' => '1',
            'session.use_strict_mode' => '1',
            'session.use_trans_sid' => '0',
            'session.gc_maxlifetime' => (string) ($lifetime * 60),
        ] as $setting => $value) {
            if (ini_set($setting, $value) === false) {
                throw new RuntimeException("Unable to configure PHP setting: {$setting}");
            }
        }

        if (session_name($name) === false) {
            throw new RuntimeException("Invalid session name: {$name}");
        }

        if (!session_set_cookie_params($cookieOptions)) {
            throw new RuntimeException('Unable to configure the session cookie.');
        }

        if (!session_start()) {
            throw new RuntimeException('Unable to start the native PHP session.');
        }

        self::ageFlashData();
    }

    public static function active(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function regenerate(bool $deleteOldSession = false): void
    {
        if (!self::active()) {
            throw new LogicException('Cannot regenerate an inactive session.');
        }

        if (!session_regenerate_id($deleteOldSession)) {
            throw new RuntimeException('Unable to regenerate the session ID.');
        }
    }

    public static function put(string $key, mixed $value): void
    {
        self::assertUserKey($key);
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::assertUserKey($key);
        $flash = self::flashValue($key);

        if ($flash['found']) {
            return $flash['value'];
        }

        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    public static function exists(string $key): bool
    {
        self::assertUserKey($key);
        return self::flashValue($key)['found'] || array_key_exists($key, $_SESSION);
    }

    public static function has(string $key): bool
    {
        return self::exists($key) && self::get($key) !== null;
    }

    public static function flash(string $key, mixed $value): void
    {
        self::assertUserKey($key);
        self::normalizeFlashStore();
        $_SESSION[self::FLASH_KEY]['new'][$key] = $value;
        unset($_SESSION[self::FLASH_KEY]['old'][$key]);
    }

    public static function now(string $key, mixed $value): void
    {
        self::assertUserKey($key);
        self::normalizeFlashStore();
        $_SESSION[self::FLASH_KEY]['old'][$key] = $value;
        unset($_SESSION[self::FLASH_KEY]['new'][$key]);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::assertUserKey($key);
        $flash = self::flashValue($key);

        return $flash['found'] ? $flash['value'] : $default;
    }

    /** @param string|list<string> $keys */
    public static function keep(string|array $keys): void
    {
        self::normalizeFlashStore();

        foreach (is_array($keys) ? $keys : [$keys] as $key) {
            self::assertUserKey($key);

            if (array_key_exists($key, $_SESSION[self::FLASH_KEY]['old'])) {
                $_SESSION[self::FLASH_KEY]['new'][$key] = $_SESSION[self::FLASH_KEY]['old'][$key];
                unset($_SESSION[self::FLASH_KEY]['old'][$key]);
            }
        }
    }

    public static function reflash(): void
    {
        self::normalizeFlashStore();
        $_SESSION[self::FLASH_KEY]['new'] = [
            ...$_SESSION[self::FLASH_KEY]['old'],
            ...$_SESSION[self::FLASH_KEY]['new'],
        ];
        $_SESSION[self::FLASH_KEY]['old'] = [];
    }

    public static function ageFlashData(): void
    {
        $store = $_SESSION[self::FLASH_KEY] ?? [];
        $store = is_array($store) ? $store : [];
        $new = is_array($store['new'] ?? null) ? $store['new'] : [];
        $legacy = array_diff_key($store, ['new' => true, 'old' => true]);

        if (array_key_exists('new', $store) && !is_array($store['new'])) {
            $legacy['new'] = $store['new'];
        }

        if (array_key_exists('old', $store) && !is_array($store['old'])) {
            $legacy['old'] = $store['old'];
        }

        // Data created on the previous request is readable now. Data that was
        // already old is deliberately omitted and therefore expires.
        $_SESSION[self::FLASH_KEY] = [
            'new' => [],
            'old' => [...$legacy, ...$new],
        ];
    }

    public static function unflash(): void
    {
        unset($_SESSION[self::FLASH_KEY]);
    }

    /** @param string|list<string> $keys */
    public static function forget(string|array $keys): void
    {
        self::normalizeFlashStore();

        foreach (is_array($keys) ? $keys : [$keys] as $key) {
            self::assertUserKey($key);
            unset(
                $_SESSION[$key],
                $_SESSION[self::FLASH_KEY]['new'][$key],
                $_SESSION[self::FLASH_KEY]['old'][$key],
            );
        }
    }

    public static function flush(): void
    {
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        $cookieName = session_name();
        $cookie = session_get_cookie_params();

        self::flush();

        if (self::active() && !session_destroy()) {
            throw new RuntimeException('Unable to destroy the native PHP session.');
        }

        if ((bool) ini_get('session.use_cookies')) {
            $deleted = setcookie($cookieName, '', [
                'expires' => time() - 42000,
                'path' => $cookie['path'] ?? '/',
                'domain' => $cookie['domain'] ?? '',
                'secure' => $cookie['secure'] ?? false,
                'httponly' => $cookie['httponly'] ?? true,
                'samesite' => $cookie['samesite'] ?? 'Lax',
            ]);

            if (!$deleted) {
                throw new RuntimeException('Unable to expire the session cookie.');
            }
        }

        unset($_COOKIE[$cookieName]);
    }

    /** @param array<string, mixed> $cookie */
    private static function cookieOptions(array $cookie): array
    {
        $lifetime = $cookie['lifetime'] ?? null;
        $path = $cookie['path'] ?? null;
        $domain = $cookie['domain'] ?? null;
        $secure = $cookie['secure'] ?? null;
        $httpOnly = $cookie['httponly'] ?? null;
        $sameSite = $cookie['samesite'] ?? null;

        if (!is_int($lifetime) || $lifetime < 0) {
            throw new RuntimeException('Session cookie lifetime must be a non-negative integer.');
        }

        if (!is_string($path) || $path === '' || !is_string($domain)) {
            throw new RuntimeException('Session cookie path/domain must be strings and path cannot be empty.');
        }

        if ($secure !== null && !is_bool($secure)) {
            throw new RuntimeException('Session cookie secure must be boolean or null for automatic detection.');
        }

        if (!is_bool($httpOnly)) {
            throw new RuntimeException('Session cookie httponly must be boolean.');
        }

        if (!is_string($sameSite) || !in_array($sameSite, ['Lax', 'Strict'], true)) {
            throw new RuntimeException('Session cookie samesite must be Lax or Strict.');
        }

        $https = $_SERVER['HTTPS'] ?? null;
        $secure ??= is_string($https) && $https !== '' && strtolower($https) !== 'off';

        return [
            'lifetime' => $lifetime,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ];
    }

    /** @return array{found: bool, value: mixed} */
    private static function flashValue(string $key): array
    {
        self::normalizeFlashStore();

        foreach (['new', 'old'] as $age) {
            if (array_key_exists($key, $_SESSION[self::FLASH_KEY][$age])) {
                return ['found' => true, 'value' => $_SESSION[self::FLASH_KEY][$age][$key]];
            }
        }

        return ['found' => false, 'value' => null];
    }

    private static function normalizeFlashStore(): void
    {
        $store = $_SESSION[self::FLASH_KEY] ?? [];
        $store = is_array($store) ? $store : [];
        $new = is_array($store['new'] ?? null) ? $store['new'] : [];
        $old = is_array($store['old'] ?? null) ? $store['old'] : [];
        $legacy = array_diff_key($store, ['new' => true, 'old' => true]);

        if (array_key_exists('new', $store) && !is_array($store['new'])) {
            $legacy['new'] = $store['new'];
        }

        if (array_key_exists('old', $store) && !is_array($store['old'])) {
            $legacy['old'] = $store['old'];
        }

        $_SESSION[self::FLASH_KEY] = [
            'new' => $new,
            'old' => [...$legacy, ...$old],
        ];
    }

    private static function assertUserKey(string $key): void
    {
        if ($key === '' || $key === self::FLASH_KEY) {
            throw new InvalidArgumentException(
                "Session keys must be non-empty and cannot use the reserved '_flash' key.",
            );
        }
    }
}
