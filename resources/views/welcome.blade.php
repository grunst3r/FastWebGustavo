@extends('layouts.app')

@section('content')
    <div class="text-center">
        <h1 class="mb-3">Bienvenido a {{ config('app.name') }}</h1>
        <p class="lead">Starter listo para arrancar cualquier proyecto con autenticación incluida.</p>

        @if (!user())
            <a href="{{ route('login') }}" class="btn btn-primary me-2">Iniciar sesión</a>
            <a href="{{ route('register') }}" class="btn btn-outline-success">Registrarse</a>
        @else
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Ir al dashboard</a>
        @endif
    </div>
@endsection
