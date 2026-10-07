<?php

namespace App\Services;

/**
 * Manejo de cookies con opciones seguras por defecto (HttpOnly + SameSite).
 *
 * Uso:
 *   CookieService::set('visto', '1', 60);      // 60 minutos
 *   CookieService::get('visto');
 *   CookieService::forget('visto');
 */
class CookieService
{
    public static function set(
        string $name,
        string $value,
        int $minutes = 60,
        string $path = '/',
        bool $httpOnly = true,
        bool $secure = false,
        string $sameSite = 'Lax'
    ): void {
        setcookie($name, $value, [
            'expires' => time() + ($minutes * 60),
            'path' => $path,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);

        $_COOKIE[$name] = $value;
    }

    public static function get(string $name, $default = null)
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    public static function forget(string $name, string $path = '/'): void
    {
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => $path,
        ]);

        unset($_COOKIE[$name]);
    }
}
