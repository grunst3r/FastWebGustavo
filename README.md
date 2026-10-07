# FastWebGustavo

Starter minimalista **tipo Laravel** (pero liviano y sin framework externo) construido sobre el
router propio [`luigu/router-gust`](https://github.com/grunst3r/RouterGust).

Incluye **autenticación lista para usar** (login, registro, recuperación de contraseña), capa de
servicios reutilizables y una estructura pensada para **clonar y arrancar cualquier proyecto**.

---

## Requisitos

- PHP >= 8.0 (probado en 8.3)
- Composer
- Extension `pdo_sqlite` (por defecto) o MySQL

## Instalacion

```bash
git clone <repo> mi-proyecto
cd mi-proyecto
composer install
cp .env.example .env      # Windows: copy .env.example .env
php artisan migrate       # crea las tablas
php artisan db:seed       # crea el usuario admin
php artisan serve         # http://localhost:8080
```

Credenciales del seeder: se toman de `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` en `.env`
(en este repo: `admin@gmail.com` / `123456`; `.env.example` usa `admin@example.com` / `password`).

## Estructura

```
app/
  Controllers/      AuthController, DashboardController
  Core/             App, Config, Migrator, helpers, Mail
  Middleware/       AuthMiddleware
  Models/           User
  Services/         Security, Validation, Pagination, Session, Cookie
bootstrap/app.php   Arranque: variables de entorno + config + DB
config/             app.php, database.php
database/
  migrations/       esquema
  seeders/          datos iniciales
public/             index.php (front controller) + assets
resources/views/    plantillas Blade
routes/             web.php, api.php, auth.php
storage/            cache de vistas, logs, sqlite
tests/              Unit + Feature (PHPUnit)
artisan             consola de comandos
```

## Rutas

Las rutas se cargan **en orden** desde `public/index.php`:

1. `routes/web.php`  → páginas web (`/`, `/dashboard`)
2. `routes/api.php`   → JSON bajo `/api`
3. `routes/auth.php`  → login, registro, logout, recuperación

Ver [`docs/RUTAS.md`](docs/RUTAS.md).

## Servicios

| Servicio | Para qué |
|---|---|
| `SecurityService` | hash/verificación de contraseñas, tokens, CSRF, cabeceras |
| `AuthTokenService` | sesiones en BD (tabla `auth_tokens`), revocar/expulsar usuarios |
| `ValidationService` / `Validator` | validación de datos con reglas |
| `PaginationService` | paginar arrays o query builders |
| `SessionService` | leer/escribir sesión y flash |
| `CookieService` | cookies seguras (HttpOnly/SameSite) |

El login incluye **"Recordarme"** (`REMEMBER_DAYS`, por defecto 7 días). Cada sesión
se guarda como token (hash SHA-256) en `auth_tokens`, así puedes expulsar a cualquier
usuario con `revoke_user_tokens($id)`. La IP real detrás de Cloudflare/BunnyCDN se
obtiene con `client_ip()` (configura `TRUSTED_PROXIES`).

Ver [`docs/SERVICIOS.md`](docs/SERVICIOS.md).

## Base de datos (opcional)

La app **arranca sin base de datos**. La conexión se activa de forma inteligente:

- Con `DB_ENABLED=false` no se inicializa nada.
- Con SQLite (por defecto) funciona sin instalar servidor.
- Con MySQL se activa si `DB_HOST` y `DB_NAME` están definidos.

Ver [`docs/BASE-DE-DATOS.md`](docs/BASE-DE-DATOS.md).

## Comandos

```bash
php artisan migrate          # migraciones pendientes
php artisan migrate:fresh    # recrea la base
php artisan db:seed          # ejecuta seeders
php artisan make:controller Nombre
php artisan serve            # servidor de desarrollo
composer test                # PHPUnit
```

## Convenciones

- Namespace raíz `App\` → carpeta `app/`.
- Sin archivos PHP en la raíz (solo el ejecutable `artisan`).
- Los controladores resuelven dependencias (ej. `Request`) por inyección.
- El middleware sigue el contrato `handle($request, $next)` y debe llamar `$next($request)`.

Lee [`AGENTS.md`](AGENTS.md) antes de contribuir (humano o IA).
