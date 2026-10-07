<?php

return [
    // Activa/desactiva la base de datos. Si es false la app arranca sin DB.
    'enabled' => env('DB_ENABLED', true),

    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', base_path('storage/database.sqlite')),
            'prefix'   => '',
        ],

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_NAME', ''),
            'username'  => env('DB_USER', 'root'),
            'password'  => env('DB_PASS', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ],
    ],
];
