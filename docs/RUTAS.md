# Rutas

Se cargan en este orden desde `public/index.php`:

```php
foreach (['web.php', 'api.php', 'auth.php'] as $file) {
    require __DIR__ . '/../routes/' . $file;
}
```

- `web.php`  → páginas web
- `api.php`  → API JSON (prefijo `/api`)
- `auth.php` → autenticación

## Definir una ruta

```php
$rutas->get('/clientes', [ClientController::class, 'index'])->name('clients.index');
$rutas->post('/clientes', [ClientController::class, 'store'])->name('clients.store');
```

## Grupos y middleware

```php
$rutas->group([
    'prefix' => '/admin',
    'middleware' => AuthMiddleware::class,
], function ($r) {
    $r->get('/', [AdminController::class, 'index'])->name('admin');
});
```

> **Importante:** el middleware usa pipeline. Debe recibir `($request, $next)` y
> llamar `return $next($request);` para continuar. Si no, la ruta no se ejecuta.

## Parametros

```php
$rutas->get('/users/{id}', [UserController::class, 'show'])->name('users.show');
$rutas->get('/posts/{slug:[a-z0-9-]+}', [PostController::class, 'show']);
```

Generar URLs por nombre dentro de vistas: `{{ route('users.show', ['id' => $user->id]) }}`.

## API

```php
$rutas->group(['prefix' => '/api'], function ($r) {
    $r->get('/health', fn () => response()->json(['status' => 'ok']))->name('api.health');
});
```
