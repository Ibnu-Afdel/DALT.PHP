<?php

namespace Core;

class Session
{
    public static function has($key)
    {
        return (bool) static::get($key);
    }
    
    public static function put($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        // BUG: Wrong order - checks regular session before flash
        // Should check flash first, then regular session
        return $_SESSION[$key] ?? $_SESSION['_flash'][$key] ?? $default;
    }

    public static function flash($key, $value)
    {
        $_SESSION['_flash'][$key] = $value;
    }

    // BUG: unflash() is commented out - flash data never gets cleaned up!
    public static function unflash()
    {
        // unset($_SESSION['_flash']);
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
        setcookie($cookieName, '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
}
