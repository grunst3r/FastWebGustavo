# Base de datos

La base de datos es **opcional y perezosa**: la app arranca aunque no haya `.env`, conexión ni tablas.
Solo se inicializa cuando una ruta realmente la usa.

## Como se decide la conexion

En `app/Core/App.php`:

1. Si `DB_ENABLED=false` → no se inicializa nada.
2. Driver `sqlite` → crea el archivo (`touch`) si no existe y lo usa.
3. Driver `mysql` → se activa solo si `DB_HOST` y `DB_NAME` estan definidos.
4. Si algo falla, la app sigue viva (se registra en `storage/logs`).

## SQLite (por defecto)

No requiere instalar servidor.

```env
DB_ENABLED=true
DB_CONNECTION=sqlite
# DB_DATABASE=storage/database.sqlite   # ruta opcional
```

## MySQL

```env
DB_ENABLED=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=fastweb
DB_USER=root
DB_PASS=
```

Crear la base antes: `CREATE DATABASE fastweb CHARACTER SET utf8mb4;`

## Desactivar la base

```env
DB_ENABLED=false
```

## Migraciones

Viven en `database/migrations` como clases anonimas con `up()` y `down()`:

```php
<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        Capsule::schema()->create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('products');
    }
};
```

Se ejecutan en orden alfabetico (usa el prefijo de fecha) y se registran en la tabla `migrations`:

```bash
php artisan migrate
php artisan migrate:fresh
```

## Seeders

En `database/seeders`. El seeder por defecto crea el usuario admin a partir de `.env`:

```bash
php artisan db:seed
```

## Acceso

Dentro del proyecto se usa Eloquent/Query Builder via Capsule:

```php
use App\Models\User;

$users = User::all();
$user  = User::where('email', $email)->first();
```
