@extends('layouts.app')

@section('content')
    <h1 class="mb-4">Dashboard</h1>
    <div class="card">
        <div class="card-body">
            <p class="mb-1">Sesión iniciada como <strong>{{ user()->email }}</strong>.</p>
            <p class="text-muted mb-0">Este es un starter básico: agregá aquí tus modelos, controladores y rutas.</p>
        </div>
    </div>
@endsection
