<?php

namespace App\Core;

class Config
{
    protected static array $items = [];
    protected static bool $loaded = false;

    public static function load(string $path): void
    {
        static::$items = [];

        foreach (glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            static::$items[$key] = require $file;
        }

        static::$loaded = true;
    }

    public static function all(): array
    {
        static::ensureLoaded();

        return static::$items;
    }

    public static function get(string $key, $default = null)
    {
        static::ensureLoaded();

        $value = static::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    protected static function ensureLoaded(): void
    {
        if (!static::$loaded) {
            static::load(base_path('config'));
        }
    }
}
