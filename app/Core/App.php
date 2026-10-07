<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

class App
{
    protected static bool $dbBooted = false;
    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 2);
    }

    public function basePath(string $path = ''): string
    {
        return $path === ''
            ? $this->basePath
            : $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    public function boot(): static
    {
        $this->loadEnv();
        Config::load($this->basePath('config'));

        date_default_timezone_set((string) config('app.timezone', 'UTC'));

        if ($this->databaseEnabled()) {
            $this->bootDatabase();
        }

        return $this;
    }

    public function loadEnv(): void
    {
        $file = $this->basePath('.env');
        if (!is_file($file)) {
            return;
        }

        if (class_exists(\Dotenv\Dotenv::class)) {
            \Dotenv\Dotenv::createMutable($this->basePath)->safeLoad();
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            putenv($line);
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }

    /**
     * La base de datos solo se activa si esta configurada.
     * Sin DB_NAME/DB_HOST (o con DB_ENABLED=false) la app arranca igual.
     */
    public function databaseEnabled(): bool
    {
        if (!filter_var(config('database.enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $connection = (string) config('database.default', 'mysql');
        $settings = (array) config("database.connections.{$connection}", []);
        $driver = $settings['driver'] ?? '';

        if ($driver === 'sqlite') {
            return ($settings['database'] ?? '') !== '';
        }

        return (string) env('DB_NAME', '') !== '' && (string) env('DB_HOST', '') !== '';
    }

    public function bootDatabase(): void
    {
        if (static::$dbBooted) {
            return;
        }

        $connection = (string) config('database.default', 'mysql');
        $settings = (array) config("database.connections.{$connection}", []);

        if (empty($settings)) {
            return;
        }

        if (($settings['driver'] ?? '') === 'sqlite') {
            $file = $settings['database'] ?? '';
            if ($file !== '' && $file !== ':memory:' && !is_file($file)) {
                @mkdir(dirname($file), 0777, true);
                @touch($file);
            }
        }

        $capsule = new Capsule();
        $capsule->addConnection($settings);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        static::$dbBooted = true;
    }

    public function databaseBooted(): bool
    {
        return static::$dbBooted;
    }
}
