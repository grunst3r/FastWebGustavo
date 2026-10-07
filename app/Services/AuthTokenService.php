<?php

namespace App\Services;

use App\Models\AuthToken;
use App\Models\User;
use Carbon\Carbon;

/**
 * Tokens de autenticacion persistidos en base de datos.
 *
 * Cada inicio de sesion (con o sin "recordarme") crea una fila en `auth_tokens`.
 * En la cookie SOLO viaja el token en claro; en la base de datos se guarda su
 * hash SHA-256. Asi se puede revocar una sesion, un dispositivo o a un usuario
 * completo en cualquier momento.
 *
 * Uso:
 *   $issued = AuthTokenService::issue($user, remember: true);
 *   $token  = AuthTokenService::find($raw);          // null si no existe/expiro
 *   AuthTokenService::revoke($token);
 *   AuthTokenService::revokeUser($user->id);         // expulsa todas sus sesiones
 */
class AuthTokenService
{
    public const COOKIE = 'remember_token';

    /**
     * @return array{token: AuthToken, raw: string}
     */
    public static function issue(User $user, bool $remember = false): array
    {
        $raw = bin2hex(random_bytes(32));
        $days = max(1, (int) env('REMEMBER_DAYS', 7));

        $token = AuthToken::create([
            'user_id' => $user->id,
            'token' => self::hash($raw),
            'remember' => $remember,
            'expires_at' => $remember ? Carbon::now()->addDays($days) : null,
            'user_agent' => self::userAgent(),
            'ip' => self::ip(),
        ]);

        return ['token' => $token, 'raw' => $raw];
    }

    public static function find(string $raw): ?AuthToken
    {
        if ($raw === '') {
            return null;
        }

        $token = AuthToken::where('token', self::hash($raw))->first();

        if (!$token || $token->isExpired()) {
            return null;
        }

        return $token;
    }

    public static function touch(AuthToken $token): void
    {
        $token->last_used_at = Carbon::now();
        $token->save();
    }

    public static function revoke(AuthToken $token): void
    {
        $token->delete();
    }

    /**
     * Expulsa todas las sesiones de un usuario. Devuelve cuantas elimino.
     */
    public static function revokeUser(int $userId): int
    {
        return AuthToken::where('user_id', $userId)->delete();
    }

    /**
     * Expulsa todas las sesiones de todos los usuarios.
     */
    public static function revokeAll(): int
    {
        return AuthToken::query()->delete();
    }

    public static function forUser(int $userId)
    {
        return AuthToken::where('user_id', $userId)->orderByDesc('last_used_at')->get();
    }

    /**
     * Elimina los tokens "recordarme" ya vencidos.
     */
    public static function pruneExpired(): int
    {
        return AuthToken::whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->delete();
    }

    public static function hash(string $raw): string
    {
        return hash('sha256', $raw);
    }

    private static function userAgent(): ?string
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        return $ua === '' ? null : substr($ua, 0, 200);
    }

    private static function ip(): ?string
    {
        return client_ip();
    }
}
