@extends('layouts.app')
@section('title', 'Productos - InvenTrack')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Productos</h1>
    @if (auth()->user()->esAdmin())
        <a href="{{ route('productos.create') }}" class="bg-slate-900 text-white rounded px-4 py-2">Nuevo producto</a>
    @endif
</div>
<form class="mb-4"><input name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o SKU" class="border rounded px-3 py-2 w-72"></form>
<table class="w-full bg-white rounded-lg shadow text-left">
    <thead class="bg-slate-50"><tr><th class="p-2">SKU</th><th class="p-2">Nombre</th><th class="p-2">Categoría</th><th class="p-2">Precio</th><th class="p-2">Stock</th><th></th></tr></thead>
    <tbody>
    @foreach ($productos as $producto)
        <tr class="border-t">
            <td class="p-2">{{ $producto->sku }}</td>
            <td class="p-2">{{ $producto->nombre }}</td>
            <td class="p-2">{{ $producto->categoria?->nombre }}</td>
            <td class="p-2">${{ $producto->precio }}</td>
            <td class="p-2 {{ $producto->tieneStockBajo() ? 'text-red-600 font-semibold' : '' }}">{{ $producto->stock }}</td>
            <td class="p-2">@if (auth()->user()->esAdmin())<a class="underline" href="{{ route('productos.edit', $producto) }}">Editar</a>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $productos->links() }}</div>
@endsection
