<?php

use GustRouter\Request;
use GustRouter\Router;

require __DIR__ . '/../bootstrap/app.php';

$request = new Request();
$rutas = new Router($request);

autologin();

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        global $rutas;
        return $rutas->url($name, $params);
    }
}

// Carga de rutas en orden.
foreach (['web.php', 'api.php', 'auth.php'] as $file) {
    require __DIR__ . '/../routes/' . $file;
}

$rutas->run();
