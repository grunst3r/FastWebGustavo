<?php

namespace App\Services;

/**
 * Acceso sencillo a la sesion (Symfony HttpFoundation).
 *
 * Uso:
 *   SessionService::set('clave', 'valor');
 *   SessionService::get('clave', 'default');
 *   SessionService::flash('success', 'Guardado!');
 */
class SessionService
{
    public static function session(): \Symfony\Component\HttpFoundation\Session\Session
    {
        return auth();
    }

    public static function get(string $key, $default = null)
    {
        return static::session()->get($key, $default);
    }

    public static function set(string $key, $value): void
    {
        static::session()->set($key, $value);
    }

    public static function has(string $key): bool
    {
        return static::session()->has($key);
    }

    public static function remove(string $key): void
    {
        static::session()->remove($key);
    }

    public static function all(): array
    {
        return static::session()->all();
    }

    public static function clear(): void
    {
        static::session()->clear();
    }

    public static function regenerate(): void
    {
        static::session()->migrate(true);
    }

    public static function flash(string $type, string $message): void
    {
        flash($type, $message);
    }

    public static function getFlash(): ?array
    {
        return flash();
    }
}
