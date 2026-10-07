<?php

use App\Core\Mail;
use App\Models\User;
use eftec\bladeone\BladeOne;
use Symfony\Component\HttpFoundation\Session\Session;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

// ENV helper
if (!function_exists('env')) {
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

// Config helper
if (!function_exists('config')) {
    function config(?string $key = null, $default = null)
    {
        if ($key === null) {
            return \App\Core\Config::all();
        }

        return \App\Core\Config::get($key, $default);
    }
}

// Services helpers
if (!function_exists('validator')) {
    function validator(array $data, array $rules): \App\Services\Validator
    {
        return \App\Services\ValidationService::make($data, $rules);
    }
}

if (!function_exists('paginate')) {
    function paginate($items, int $perPage = 15, ?int $page = null): array
    {
        return \App\Services\PaginationService::paginate($items, $perPage, $page);
    }
}

if (!function_exists('send_email')) {
    function send_email(array $params)
    {
        Mail::send(
            $params['to'],
            $params['subject'],
            $params['view'] ?? 'emails.base',
            $params['data'] ?? []
        );
    }
}

// Sesión
if (!function_exists('auth')) {
    function auth(): Session
    {
        static $session = null;
        if (!$session) {
            $session = new Session();
            if (!$session->isStarted()) {
                $session->start();
            }
        }
        return $session;
    }
}

// Token JWT
if (!function_exists('generate_jwt')) {
    function generate_jwt($user): string
    {
        $key = env('JWT_SECRET', 'secret_key_default');
        $expiration = (int) env('JWT_EXPIRATION', 3600);

        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'iat' => time(),
            'exp' => time() + $expiration
        ];

        return JWT::encode($payload, $key, 'HS256');
    }
}

if (!function_exists('verify_jwt')) {
    function verify_jwt(string $token)
    {
        try {
            $key = env('JWT_SECRET', 'secret_key_default');
            return JWT::decode($token, new Key($key, 'HS256'));
        } catch (\Throwable $e) {
            return null;
        }
    }
}

// Token CSRF
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (!auth()->has('csrf_token')) {
            auth()->set('csrf_token', bin2hex(random_bytes(32)));
        }
        return auth()->get('csrf_token');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(string $token): bool
    {
        return hash_equals(csrf_token(), $token);
    }
}

// Helpers de usuario
if (!function_exists('attempt')) {
    function attempt(string $email, string $password): bool
    {
        $user = User::where('email', $email)->first();
        if ($user && password_verify($password, $user->password)) {
            auth()->set('user', $user);
            auth()->set('token', generate_jwt($user));
            return true;
        }
        return false;
    }
}

if (!function_exists('logout')) {
    function logout(): void
    {
        if (auth()->has('user')) {
            $user = auth()->get('user');
            if ($user->remember_token) {
                setcookie('remember_token', '', time() - 3600, '/');
                $user->remember_token = null;
                $user->save();
            }
        }
        auth()->clear();
    }
}

if (!function_exists('user')) {
    function user()
    {
        return auth()->get('user');
    }
}

// Ver si el usuario está autenticado
if (!function_exists('is_authenticated')) {
    function is_authenticated(): bool
    {
        if (!auth()->has('user') || !auth()->has('token')) {
            return false;
        }

        $user = auth()->get('user');

        return User::where('id', $user->id)
            ->where('remember_token', $user->remember_token)
            ->exists();
    }
}

if (!function_exists('token')) {
    function token()
    {
        return auth()->get('token');
    }
}

if (!function_exists('autologin')) {
    function autologin()
    {
        if (!auth()->has('user') && isset($_COOKIE['remember_token'])) {
            $user = User::where('remember_token', $_COOKIE['remember_token'])->first();
            if ($user) {
                auth()->set('user', $user);
                auth()->set('token', generate_jwt($user));
                return true;
            } else {
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }
        return false;
    }
}

// Blade
if (!function_exists('flash')) {
    function flash($type = null, $message = null)
    {
        $session = auth();
        if ($type && $message) {
            $session->set('flash', ['type' => $type, 'message' => $message]);
            return null;
        }
        $data = $session->get('flash');
        $session->remove('flash');
        return $data;
    }
}

if (!function_exists('redirect')) {
    function redirect($url, $flash = null)
    {
        if ($flash && is_array($flash)) {
            flash($flash['type'], $flash['message']);
        }
        header("Location: $url");
        exit;
    }
}

if (!function_exists('old')) {
    function old($key, $default = null)
    {
        $old = auth()->get('old') ?? [];
        return $old[$key] ?? $default;
    }
}

if (!function_exists('view')) {
    function view($view, $data = [])
    {
        $views = base_path('resources/views');
        $cache = base_path('storage/framework/views');
        if (!is_dir($cache)) {
            @mkdir($cache, 0777, true);
        }

        $blade = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
        $blade->directive('csrf', function () {
            return "<?php echo csrf_field(); ?>";
        });

        return $blade->run($view, $data);
    }
}

// Debug
if (!function_exists('dd')) {
    function dd(...$args)
    {
        echo "<pre>";
        foreach ($args as $arg) {
            var_dump($arg);
        }
        echo "</pre>";
        die();
    }
}

// Fecha y hora
if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'Y-m-d'): ?string
    {
        if (!$date) return null;
        return (new DateTime($date))->format($format);
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime($date, $format = 'Y-m-d H:i:s'): ?string
    {
        if (!$date) return null;
        return (new DateTime($date))->format($format);
    }
}

if (!function_exists('formatDateTimeAgo')) {
    function formatDateTimeAgo($date): ?string
    {
        if (!$date) return null;

        $now = new DateTime();
        $dt = new DateTime($date);
        $diff = $now->diff($dt);

        return match (true) {
            $diff->y > 0 => $diff->y . ' año' . ($diff->y > 1 ? 's' : ''),
            $diff->m > 0 => $diff->m . ' mes' . ($diff->m > 1 ? 'es' : ''),
            $diff->d > 0 => $diff->d . ' día' . ($diff->d > 1 ? 's' : ''),
            $diff->h > 0 => $diff->h . ' hora' . ($diff->h > 1 ? 's' : ''),
            $diff->i > 0 => $diff->i . ' minuto' . ($diff->i > 1 ? 's' : ''),
            default => 'hace un momento',
        };
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($name, 5)));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}

// Response
if (!function_exists('response')) {
    function response(): object
    {
        return new class {
            public function json($data, int $status = 200, array $headers = [])
            {
                http_response_code($status);
                header('Content-Type: application/json');
                foreach ($headers as $key => $value) {
                    header("$key: $value");
                }
                $json = json_encode($data);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('Error al codificar JSON: ' . json_last_error_msg());
                }
                return $json;
            }
        };
    }
}

if (!function_exists('input_all')) {
    function input_all(): array
    {
        $data = [];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            $rawInput = file_get_contents('php://input');
            $json = json_decode($rawInput, true);
            if (is_array($json)) {
                $data = array_merge($data, $json);
            }
        }
        $data = array_merge($data, $_POST, $_FILES);
        $data = array_merge($data, $_GET);
        return $data;
    }
}
