<?php

use App\Controllers\AuthController;

/**
 * Rutas de autenticacion.
 */
$rutas->get('/login', [AuthController::class, 'showLogin'])->name('login');
$rutas->post('/login', [AuthController::class, 'login'])->name('login.post');
$rutas->get('/register', [AuthController::class, 'showRegister'])->name('register');
$rutas->post('/register', [AuthController::class, 'register'])->name('register.post');
$rutas->get('/logout', [AuthController::class, 'logout'])->name('logout');

$rutas->get('/verify-account', function () {
    return view('auth.verify-account');
})->name('verify-account');

$rutas->get('/password/request', [AuthController::class, 'showPasswordRequest'])->name('password.request');
$rutas->post('/forgot_password', [AuthController::class, 'forgotPassword'])->name('password.email');
$rutas->get('/password/reset', [AuthController::class, 'showResetPassword'])->name('password.reset');
$rutas->post('/password/update', [AuthController::class, 'updatePassword'])->name('password.update');
