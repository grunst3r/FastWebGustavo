<?php

namespace App\Middleware;

class AuthMiddleware
{
    /**
     * Middleware pipeline. Debe llamar a $next($request) para continuar.
     */
    public function handle($request, $next)
    {
        if (!is_authenticated()) {
            return redirect(route('login'), ['type' => 'danger', 'message' => 'Debes iniciar sesión.']);
        }

        return $next($request);
    }
}
