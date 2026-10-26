@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $producto->exists ? 'Editar producto' : 'Nuevo producto' }}</h1>
<form method="POST" action="{{ $producto->exists ? route('productos.update', $producto) : route('productos.store') }}" class="bg-white rounded-lg shadow p-6 grid md:grid-cols-2 gap-4">
    @csrf
    @if ($producto->exists) @method('PUT') @endif
    <label>SKU<input name="sku" value="{{ old('sku', $producto->sku) }}" class="mt-1 w-full border rounded px-3 py-2"></label>
    <label>Nombre<input name="nombre" value="{{ old('nombre', $producto->nombre) }}" class="mt-1 w-full border rounded px-3 py-2"></label>
    <label>Categoría
        <select name="categoria_id" class="mt-1 w-full border rounded px-3 py-2">
            <option value="">Sin categoría</option>
            @foreach ($categorias as $c)<option value="{{ $c->id }}" @selected(old('categoria_id', $producto->categoria_id) == $c->id)>{{ $c->nombre }}</option>@endforeach
        </select>
    </label>
    <label>Precio<input name="precio" type="number" step="0.01" value="{{ old('precio', $producto->precio) }}" class="mt-1 w-full border rounded px-3 py-2"></label>
    <label>Stock<input name="stock" type="number" value="{{ old('stock', $producto->stock ?? 0) }}" class="mt-1 w-full border rounded px-3 py-2"></label>
    <label>Stock mínimo<input name="stock_minimo" type="number" value="{{ old('stock_minimo', $producto->stock_minimo ?? 5) }}" class="mt-1 w-full border rounded px-3 py-2"></label>
    <div class="md:col-span-2"><button class="bg-slate-900 text-white rounded px-4 py-2">Guardar</button></div>
</form>
@endsection
