<?php

namespace App\Controllers;

use App\Models\User;
use GustRouter\Request;

class AuthController
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $post = input_all();

        if (!verify_csrf($post['_token'] ?? '')) {
            return redirect(route('login'), ['type' => 'danger', 'message' => 'Token CSRF inválido.']);
        }

        $email = trim($post['email'] ?? '');
        $password = $post['password'] ?? '';

        if (attempt($email, $password)) {
            if (isset($post['remember'])) {
                $user = user();
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + 604800, '/');
                $user->remember_token = $token;
                $user->save();
            }

            return redirect(route('dashboard'));
        }

        return redirect(route('login'), ['type' => 'danger', 'message' => 'Credenciales inválidas.']);
    }

    public function logout()
    {
        logout();
        return redirect(route('home'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register()
    {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            return redirect(route('register'), ['type' => 'danger', 'message' => 'Token CSRF inválido.']);
        }

        $email = trim($_POST['email'] ?? '');
        if ($email === '' || empty($_POST['password'])) {
            return redirect(route('register'), ['type' => 'danger', 'message' => 'Email y contraseña son obligatorios.']);
        }

        if (User::where('email', $email)->exists()) {
            return redirect(route('register'), ['type' => 'danger', 'message' => 'Ese email ya está registrado.']);
        }

        $user = new User();
        $user->name = $_POST['name'] ?? 'Sin Nombre';
        $user->email = $email;
        $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user->save();

        auth()->set('user', $user);
        auth()->set('token', generate_jwt($user));

        return redirect(route('dashboard'), ['type' => 'success', 'message' => 'Registro exitoso.']);
    }

    public function showPasswordRequest()
    {
        return view('auth.password-request');
    }

    public function forgotPassword(Request $request)
    {
        if (!verify_csrf($request->post('_token') ?? '')) {
            return redirect(route('password.request'), ['type' => 'danger', 'message' => 'Token CSRF inválido.']);
        }

        $user = User::where('email', $request->post('email'))->first();
        if (!$user) {
            return redirect(route('password.request'), ['type' => 'danger', 'message' => 'No se encontró un usuario con ese correo electrónico.']);
        }

        $token = generate_jwt($user);
        send_email([
            'to' => $user->email,
            'subject' => 'Recupera tu contraseña',
            'data' => [
                'title' => '¿Olvidaste tu contraseña?',
                'message' => 'Haz clic para cambiarla.',
                'action_url' => route('password.reset', ['token' => $token]),
                'action_text' => 'Cambiar contraseña',
            ],
        ]);

        return redirect(route('password.request'), ['type' => 'success', 'message' => 'Se ha enviado un enlace para restablecer la contraseña a tu correo electrónico.']);
    }

    public function showResetPassword()
    {
        $token = $_GET['token'] ?? '';
        if (!verify_jwt($token)) {
            return redirect(route('password.request'), ['type' => 'danger', 'message' => 'Token inválido o expirado.']);
        }
        return view('auth.reset-password', ['token' => $token]);
    }

    public function updatePassword(Request $request)
    {
        if (!verify_csrf($request->post('_token') ?? '')) {
            return redirect(route('password.reset', ['token' => $request->post('token')]), ['type' => 'danger', 'message' => 'Token CSRF inválido.']);
        }

        $token = $request->post('token') ?? '';
        $datos = verify_jwt($token);
        if (!$datos) {
            return redirect(route('password.request'), ['type' => 'danger', 'message' => 'Token inválido o expirado.']);
        }

        $user = User::where('email', $datos->email)->first();
        if (!$user) {
            return redirect(route('password.request'), ['type' => 'danger', 'message' => 'No se encontró un usuario con ese correo electrónico.']);
        }

        if ($request->post('password') !== $request->post('password_confirmation')) {
            return redirect(route('password.reset', ['token' => $token]), ['type' => 'danger', 'message' => 'Las contraseñas no coinciden.']);
        }

        $user->password = password_hash($request->post('password'), PASSWORD_DEFAULT);
        $user->remember_token = null;
        $user->save();

        return redirect(route('login'), ['type' => 'success', 'message' => 'Contraseña actualizada correctamente. Ahora puedes iniciar sesión.']);
    }
}
