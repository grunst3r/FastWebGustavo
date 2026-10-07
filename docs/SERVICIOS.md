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
