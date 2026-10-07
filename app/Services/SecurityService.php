<?php

namespace App\Services;

/**
 * Utilidades de seguridad: hash de contraseñas, tokens y CSRF.
 *
 * Uso:
 *   SecurityService::hash('secreto');
 *   SecurityService::check($plano, $hash);
 *   SecurityService::token();
 *   SecurityService::escape($html);
 */
class SecurityService
{
    public static function hash(string $value): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    public static function check(string $value, string $hash): bool
    {
        return password_verify($value, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    public static function token(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', false);
    }

    public static function csrfToken(): string
    {
        return csrf_token();
    }

    public static function csrfField(): string
    {
        return csrf_field();
    }

    public static function verifyCsrf(string $token): bool
    {
        return verify_csrf($token);
    }

    /**
     * Cabeceras de seguridad basicas para respuestas HTML.
     */
    public static function headers(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
    }
}
