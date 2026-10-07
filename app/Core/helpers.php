<?php

use App\Core\Mail;
use App\Models\AuthToken;
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

// IP real del cliente (Cloudflare, BunnyCDN y proxies)
if (!function_exists('client_ip')) {
    function client_ip(): ?string
    {
        if (!trust_proxy_headers()) {
            return is_valid_ip($_SERVER['REMOTE_ADDR'] ?? null);
        }

        $candidates = [];

        // Cabeceras de CDN/proxy que apuntan al cliente original.
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_X_REAL_IP'] as $key) {
            if (!empty($_SERVER[$key])) {
                $candidates[] = $_SERVER[$key];
            }
        }

        // X-Forwarded-For: el primer valor es el cliente original.
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $part) {
                $candidates[] = $part;
            }
        }

        // RFC 7239: Forwarded: for=...
        if (!empty($_SERVER['HTTP_FORWARDED'])) {
            if (preg_match_all('/for="?\[?([0-9a-fA-F:.]+)\]?"?/', $_SERVER['HTTP_FORWARDED'], $m)) {
                foreach ($m[1] as $part) {
                    $candidates[] = $part;
                }
            }
        }

        $candidates[] = $_SERVER['REMOTE_ADDR'] ?? null;

        $fallback = null;
        foreach ($candidates as $candidate) {
            $ip = is_valid_ip(trim((string) $candidate));
            if ($ip === null) {
                continue;
            }
            if (is_public_ip($ip)) {
                return $ip;
            }
            $fallback = $fallback ?? $ip;
        }

        return $fallback;
    }
}

if (!function_exists('is_valid_ip')) {
    function is_valid_ip(?string $ip): ?string
    {
        if ($ip === null || trim($ip) === '') {
            return null;
        }

        $ip = trim(trim($ip), '[]');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }
}

if (!function_exists('is_public_ip')) {
    function is_public_ip(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}

if (!function_exists('trust_proxy_headers')) {
    function trust_proxy_headers(): bool
    {
        $trusted = trim((string) env('TRUSTED_PROXIES', ''));

        if ($trusted === '') {
            return false;
        }

        if ($trusted === '*') {
            return true;
        }

        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        foreach (array_map('trim', explode(',', $trusted)) as $proxy) {
            if ($proxy !== '' && ip_matches_proxy($remote, $proxy)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('ip_matches_proxy')) {
    function ip_matches_proxy(string $ip, string $proxy): bool
    {
        $ip = is_valid_ip($ip);
        if ($ip === null) {
            return false;
        }

        if (!str_contains($proxy, '/')) {
            return $ip === $proxy;
        }

        [$subnet, $bits] = explode('/', $proxy, 2);
        $subnet = is_valid_ip($subnet);
        $bits = (int) $bits;

        if ($subnet === null) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = ~((1 << (8 - $remainder)) - 1) & 0xFF;

        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }
}

// Helpers de usuario
if (!function_exists('attempt')) {
    function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = User::where('email', $email)->first();

        if ($user && password_verify($password, $user->password)) {
            login_user($user, $remember);
            return true;
        }

        return false;
    }
}

if (!function_exists('login_user')) {
    function login_user(User $user, bool $remember = false): void
    {
        $issued = \App\Services\AuthTokenService::issue($user, $remember);

        auth()->set('user', $user);
        auth()->set('auth_token_id', $issued['token']->id);
        auth()->set('token', generate_jwt($user));

        if ($remember) {
            $days = max(1, (int) env('REMEMBER_DAYS', 7));
            setcookie(\App\Services\AuthTokenService::COOKIE, $issued['raw'], [
                'expires' => time() + ($days * 86400),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            $_COOKIE[\App\Services\AuthTokenService::COOKIE] = $issued['raw'];
        }
    }
}

if (!function_exists('logout')) {
    function logout(): void
    {
        $id = auth()->get('auth_token_id');
        if ($id) {
            $token = AuthToken::find($id);
            if ($token) {
                $token->delete();
            }
        }

        if (isset($_COOKIE[\App\Services\AuthTokenService::COOKIE])) {
            setcookie(\App\Services\AuthTokenService::COOKIE, '', [
                'expires' => time() - 3600,
                'path' => '/',
            ]);
            unset($_COOKIE[\App\Services\AuthTokenService::COOKIE]);
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
        if (!auth()->has('user') || !auth()->has('auth_token_id')) {
            return false;
        }

        $token = AuthToken::find(auth()->get('auth_token_id'));

        if (!$token || $token->isExpired()) {
            auth()->remove('user');
            auth()->remove('auth_token_id');
            auth()->remove('token');
            return false;
        }

        $user = User::find($token->user_id);

        if (!$user) {
            $token->delete();
            auth()->clear();
            return false;
        }

        auth()->set('user', $user);

        return true;
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
        if (auth()->has('user')) {
            return false;
        }

        $raw = $_COOKIE[\App\Services\AuthTokenService::COOKIE] ?? '';

        if ($raw === '') {
            return false;
        }

        $token = \App\Services\AuthTokenService::find($raw);

        if (!$token) {
            setcookie(\App\Services\AuthTokenService::COOKIE, '', [
                'expires' => time() - 3600,
                'path' => '/',
            ]);
            unset($_COOKIE[\App\Services\AuthTokenService::COOKIE]);
            return false;
        }

        $user = User::find($token->user_id);

        if (!$user) {
            $token->delete();
            return false;
        }

        auth()->set('user', $user);
        auth()->set('auth_token_id', $token->id);
        auth()->set('token', generate_jwt($user));
        \App\Services\AuthTokenService::touch($token);

        return true;
    }
}

// Revocacion de accesos (expulsar usuarios o dispositivos)
if (!function_exists('revoke_user_tokens')) {
    function revoke_user_tokens(int $userId): int
    {
        return \App\Services\AuthTokenService::revokeUser($userId);
    }
}

if (!function_exists('revoke_token')) {
    function revoke_token(int $id): bool
    {
        $token = AuthToken::find($id);
        if (!$token) {
            return false;
        }
        $token->delete();
        return true;
    }
}

if (!function_exists('auth_tokens')) {
    function auth_tokens(int $userId)
    {
        return \App\Services\AuthTokenService::forUser($userId);
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
