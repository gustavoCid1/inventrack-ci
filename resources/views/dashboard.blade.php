@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-6">Panel</h1>
<div class="grid md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4"><p class="text-sm text-slate-500">Productos</p><p class="text-3xl font-bold">{{ $totalProductos }}</p></div>
    <div class="bg-white rounded-lg shadow p-4"><p class="text-sm text-slate-500">Ventas de hoy</p><p class="text-3xl font-bold">${{ number_format((float) $ventasHoy, 2) }}</p></div>
    <div class="bg-white rounded-lg shadow p-4"><p class="text-sm text-slate-500">Alertas de stock</p><p class="text-3xl font-bold text-red-600">{{ $bajoStock->count() }}</p></div>
</div>
<h2 class="text-xl font-semibold mb-2">Productos con stock bajo</h2>
<ul class="bg-white rounded-lg shadow divide-y">
    @forelse ($bajoStock as $p)
        <li class="px-4 py-2 flex justify-between"><span>{{ $p->nombre }} ({{ $p->sku }})</span><span class="text-red-600">{{ $p->stock }} / mín. {{ $p->stock_minimo }}</span></li>
    @empty
        <li class="px-4 py-2 text-slate-500">Sin alertas.</li>
    @endforelse
</ul>
@endsection
