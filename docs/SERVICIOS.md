# Servicios

Componentes reutilizables en `app/Services`. Todos se usan de forma estatica.

## SecurityService

```php
use App\Services\SecurityService;

$hash = SecurityService::hash('secreto');
SecurityService::check('secreto', $hash);   // true
SecurityService::token();                    // 64 chars
SecurityService::verifyCsrf($token);
SecurityService::headers();                  // cabeceras de seguridad
```

## ValidationService / Validator

Reglas: `required`, `nullable`, `email`, `url`, `numeric`, `integer`, `boolean`,
`min:n`, `max:n`, `in:a,b,c`, `same:campo`, `confirmed`, `date`.

```php
$v = validator($datos, [
    'name'     => 'required|min:2',
    'email'    => 'required|email',
    'password' => 'required|min:8|confirmed',
]);

if ($v->fails()) {
    // $v->errors(), $v->firstError('email'), $v->flattenErrors()
}

$limpio = $v->validated();   // solo campos que pasaron
```

Lanzando excepcion:

```php
$limpio = ValidationService::validate($datos, $reglas); // InvalidArgumentException si falla
```

## PaginationService

```php
$page = paginate($users, 15);

$page['data'];          // items de la pagina actual
$page['total'];
$page['current_page'];
$page['last_page'];
$page['has_more'];
```

Funciona con arrays y con Query/Eloquent Builder (`User::query()`).

## SessionService

```php
use App\Services\SessionService;

SessionService::set('clave', 'valor');
SessionService::get('clave', 'default');
SessionService::has('clave');
SessionService::remove('clave');
SessionService::flash('success', 'Guardado!');
SessionService::getFlash();
```

## CookieService

```php
use App\Services\CookieService;

CookieService::set('visto', '1', 60);   // 60 minutos, HttpOnly + SameSite=Lax
CookieService::get('visto');
CookieService::forget('visto');
```

## AuthTokenService

Tokens de sesión persistidos en BD (tabla `auth_tokens`). Cada inicio de sesión
crea una fila; en la cookie viaja el token en claro y en la base de datos se guarda
su hash SHA-256. Esto permite **revocar** una sesión, un dispositivo o a un usuario
completo en cualquier momento.

```php
use App\Services\AuthTokenService;

$issued = AuthTokenService::issue($user, remember: true);
// $issued['raw']   -> token para la cookie
// $issued['token'] -> modelo AuthToken (ya guardado)

$token = AuthTokenService::find($raw);   // null si no existe o expiro
AuthTokenService::touch($token);          // marca last_used_at
AuthTokenService::revoke($token);         // cierra ESA sesion
AuthTokenService::revokeUser($userId);    // expulsa TODAS las sesiones del usuario
AuthTokenService::revokeAll();            // cierra todas las sesiones
AuthTokenService::forUser($userId);       // lista dispositivos/sesiones
AuthTokenService::pruneExpired();         // limpia tokens vencidos
```

Atajos globales:

```php
revoke_user_tokens($userId);   // expulsar a un usuario
revoke_token($id);             // expulsar una sesion puntual
auth_tokens($userId);          // sesiones activas de un usuario
```

Duracion de "recordarme" configurable con `REMEMBER_DAYS` (`.env`, por defecto 7).
Al cambiar la contraseña se revocan todos los tokens del usuario.

## IP del cliente y proxies (Cloudflare / BunnyCDN)

`client_ip()` devuelve la IP real aunque estes detras de un CDN o balanceador.
Lee, por orden: `CF-Connecting-IP` (Cloudflare), `True-Client-IP`, `X-Real-IP`,
`X-Forwarded-For` y `Forwarded` (RFC 7239), validando siempre con `filter_var` y
priorizando IPs publicas.

Por seguridad, las cabeceras de proxy solo se confian si el proxy es de confianza
(`TRUSTED_PROXIES` en `.env`):

```env
# confiar en cualquier proxy (Cloudflare, BunnyCDN gestionado)
TRUSTED_PROXIES=*

# o solo en proxies concretos (IP o rango CIDR, separados por coma)
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,10.0.0.0/8
```

Si esta vacio, se usa directamente `REMOTE_ADDR`. Helpers disponibles:
`client_ip()`, `is_valid_ip()`, `is_public_ip()`, `trust_proxy_headers()`,
`ip_matches_proxy()`.

## Crear un servicio nuevo

```php
namespace App\Services;

class MiServicio
{
    public static function hacer(string $valor): string
    {
        return strtoupper($valor);
    }
}
```

Colocalo en `app/Services` y usalo en cualquier parte con `\App\Services\MiServicio::hacer(...)`.
