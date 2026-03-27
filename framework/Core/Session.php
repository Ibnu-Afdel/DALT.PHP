<?php

namespace Core;

class Session
{
    public static function has(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]) || isset($_SESSION[$key]);
    }

    public static function active(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function regenerate(bool $deleteOldSession = true): void
    {
        if (!static::active()) {
            return;
        }
        session_regenerate_id($deleteOldSession);
    }

    public static function put($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        return $_SESSION['_flash'][$key] ??  $_SESSION[$key] ?? $default;
    }

    public static function flash($key, $value)
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash($key, $default = null)
    {
        return $_SESSION['_flash'][$key] ?? $default;
    }

    public static function unflash()
    {
        unset($_SESSION['_flash']);
    }

    public static function flush()
    {
        $_SESSION = [];
    }

    public static function destroy()
    {
        $cookieName = session_name();
        static::flush();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $params = session_get_cookie_params();
        setcookie($cookieName, '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'] ?? '/',
            'domain'   => $params['domain'] ?? '',
            'secure'   => $params['secure'] ?? false,
            'httponly' => $params['httponly'] ?? true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

}
