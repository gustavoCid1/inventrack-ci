@extends('layouts.app')
@section('title', 'Iniciar sesión - InvenTrack')
@section('content')
<div class="max-w-sm mx-auto bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-4">Iniciar sesión</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <label class="block">Correo<input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full border rounded px-3 py-2" required></label>
        <label class="block">Contraseña<input type="password" name="password" class="mt-1 w-full border rounded px-3 py-2" required></label>
        <button class="w-full bg-slate-900 text-white rounded py-2">Entrar</button>
    </form>
</div>
@endsection
