<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">{{ config('app.name') }}</a>
            <div class="ms-auto">
                @if (user())
                    <span class="text-white me-3">{{ user()->name ?? user()->email }}</span>
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-light me-2">Dashboard</a>
                    <a href="{{ route('logout') }}" class="btn btn-sm btn-outline-danger">Salir</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light me-2">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-primary">Registro</a>
                @endif
            </div>
        </div>
    </nav>

    <main class="container py-5">
        @if ($flash = flash())
            <div class="alert alert-{{ $flash['type'] }}">{{ $flash['message'] }}</div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
