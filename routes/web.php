<?php

use App\Controllers\DashboardController;
use App\Middleware\AuthMiddleware;

/**
 * Rutas web generales.
 */
$rutas->get('/', [DashboardController::class, 'welcome'])->name('home');

// Rutas protegidas (requieren sesion iniciada).
$rutas->group([
    'prefix' => '/dashboard',
    'middleware' => AuthMiddleware::class,
], function ($r) {
    $r->get('/', [DashboardController::class, 'index'])->name('dashboard');
});
