# AGENTS.md

Guia para agentes de IA (y humanos) que trabajen en **FastWebGustavo**.
Leer completo antes de modificar el proyecto.

## Que es

Starter PHP minimalista **tipo Laravel**, sin framework completo, construido sobre el router
`luigu/router-gust`. Trae autenticacion funcional, capa de servicios y estructura para clonar
y empezar cualquier proyecto. El dominio CRM (clientes, tratos, tareas, etc.) fue **eliminado**
a propósito: esta base solo incluye usuarios + auth.

## Reglas de oro

1. **Nada de PHP en la raiz** salvo el ejecutable `artisan`. El resto va en `app/`, `config/`,
   `database/`, `routes/`, `bootstrap/`, `resources/`, `storage/`, `tests/`, `public/`.
2. **Todo pasa por `public/index.php`**. No crear front controllers alternativos.
3. **Rutas separadas** y cargadas en orden: `web.php`, `api.php`, `auth.php`
   (ver `public/index.php`). No cambiar ese orden sin motivo.
4. **La base de datos es opcional.** Nunca asumir que hay conexion. Usar helpers/`App::databaseEnabled()`.
5. **No commitear `.env`** ni secretos. Usar `.env.example` como plantilla.
6. **No agregar dependencias** sin justificarlo (el proyecto es deliberadamente liviano).
7. **Idioma de mensajes al usuario:** espanol. Nombres de codigo en ingles.
8. **No agregar comentarios** salvo que aporten contexto no obvio (docblocks de servicios estan bien).

## Estructura

```
app/
  Controllers/    controladores HTTP
  Core/           App, Config, Migrator, Mail, helpers.php
  Middleware/      AuthMiddleware
  Models/         User
  Services/       Security, Validation, Validator, Pagination, Session, Cookie
bootstrap/app.php arranque (env + config + return App)
config/           app.php, database.php
database/         migrations/, seeders/
public/index.php  front controller
resources/views/  plantillas Blade
routes/           web.php, api.php, auth.php
storage/          app/, framework/views, logs, database.sqlite
tests/            Unit/, Feature/, TestCase.php
```

Namespaces: `App\` → `app/`, `Database\Seeders\` → `database/seeders/`, `Tests\` → `tests/`.

## Comandos

```bash
composer install
composer dump-autoload
composer test                                   # PHPUnit
php vendor/phpunit/phpunit/phpunit --no-coverage

php artisan migrate
php artisan migrate:fresh
php artisan db:seed
php artisan make:controller Nombre
php artisan serve
```

Lint rapido de sintaxis:

```bash
Get-ChildItem -Recurse -Filter *.php app,config,database,routes | ForEach-Object { php -l $_.FullName }
```

## Router y middleware (CRITICO)

El router usa un **pipeline**. La firma correcta de un middleware es:

```php
public function handle($request, $next)
{
    if (!is_authenticated()) {
        return redirect('/login');
    }

    return $next($request);   // <-- SIEMPRE llamar para continuar
}
```

- Si no llamas `$next($request)`, la cadena se corta (respuesta vacia / 200 raro).
- `redirect()` hace `exit`, no esperes que continue el flujo.
- `AuthMiddleware` (`app/Middleware/AuthMiddleware.php`) protege `/dashboard`.
- Para actualizar el router: `composer update luigu/router-gust --with-dependencies`.

## Definir rutas

```php
$rutas->get('/', [HomeController::class, 'index'])->name('home');
$rutas->group(['prefix' => '/admin', 'middleware' => AuthMiddleware::class], function ($r) {
    $r->get('/', [AdminController::class, 'index'])->name('admin');
});
```

URLs en vistas: `{{ route('admin') }}`.

## Agregar una funcionalidad

1. **Migracion** en `database/migrations/AAAA_MM_DD_HHMMSS_create_x_table.php`
   (clase anonima con `up()`/`down()`; ver `docs/BASE-DE-DATOS.md`).
2. **Modelo** en `app/Models/X.php` (`extends Illuminate\Database\Eloquent\Model`).
3. **Controlador** en `app/Controllers/XController.php`; inyectar `Request` en el metodo.
4. **Rutas** en `routes/web.php` (o `api.php` si es JSON).
5. **Vista** en `resources/views/x.blade.php`.
6. **Tests** en `tests/`.
7. Verificar: `composer dump-autoload`, lint, `composer test`.

## Servicios

Usar la capa `app/Services` en lugar de repetir logica:

- `SecurityService` — `hash`, `check`, `token`, `verifyCsrf`, `headers`.
- `AuthTokenService` — tokens de sesion en BD (`auth_tokens`), revocacion de sesiones.
- `validator($datos, $reglas)` / `ValidationService` — validacion (ver `docs/SERVICIOS.md`).
- `paginate($items, $perPage)` / `PaginationService`.
- `SessionService` — sesion y flash.
- `CookieService` — cookies seguras.

## Autenticacion, sesiones y revocacion

- Cada login crea una fila en `auth_tokens` (hash SHA-256); la cookie solo lleva el token en claro.
- `is_authenticated()` valida el token contra la BD en cada request: **borrar la fila expulsa al usuario**.
- Helpers: `login_user($user, $remember)`, `attempt($email, $pass, $remember)`, `logout()`,
  `revoke_user_tokens($userId)`, `revoke_token($id)`, `auth_tokens($userId)`.
- Duracion de "recordarme": `REMEMBER_DAYS`. Al cambiar contraseña se revocan los tokens.
- IP real detras de CDN/proxy: usar `client_ip()` (Cloudflare, BunnyCDN, X-Forwarded-For).
  Configurar `TRUSTED_PROXIES` (`*` o lista de IPs/CIDR). No leer `X-Forwarded-For` a mano.

## Entorno y configuracion

- `.env` se carga con `vlucas/phpdotenv`. Helpers: `env($key, $default)`, `config($key, $default)`.
- `config/app.php`, `config/database.php`. `APP_DEBUG=true` solo en local.
- Rutas de interes: `base_path()`, `storage_path()`.
- La BD por defecto es SQLite (`storage/database.sqlite`) para funcionar sin instalar servidor.

## Pruebas

- Framework: PHPUnit (`tests/Unit`, `tests/Feature`).
- Al terminar cambios: `composer test` debe pasar en verde.
- No romper los tests existentes de rutas/auth.

## No hacer

- No reintroducir Inertia/Vue/Vite ni `package.json` (el starter es Blade puro).
- No crear archivos PHP en la raiz.
- No asumir MySQL disponible.
- No commitear `.env`, `storage/*.sqlite`, `vendor/` ni logs.
- No hacer commits ni push salvo que el usuario lo pida explicitamente.
