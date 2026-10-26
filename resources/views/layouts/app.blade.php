<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'InvenTrack')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
    @auth
        <nav class="bg-slate-900 text-white">
            <div class="max-w-6xl mx-auto px-4 py-3 flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="font-bold text-lg">InvenTrack</a>
                <a href="{{ route('productos.index') }}" class="hover:underline">Productos</a>
                <a href="{{ route('ventas.create') }}" class="hover:underline">Punto de venta</a>
                <span class="ml-auto text-sm text-slate-300">{{ auth()->user()->name }} ({{ auth()->user()->rol }})</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm underline">Salir</button></form>
            </div>
        </nav>
    @endauth
    <main class="max-w-6xl mx-auto px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded bg-emerald-100 text-emerald-900 px-4 py-2">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 text-red-900 px-4 py-2">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
