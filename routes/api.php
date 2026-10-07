<?php

/**
 * Rutas API. Se agrupan bajo el prefijo /api.
 * Devuelven JSON (no sesion/CSRF).
 */
$rutas->group([
    'prefix' => '/api',
], function ($r) {
    $r->get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'time' => now(),
        ]);
    })->name('api.health');
});
