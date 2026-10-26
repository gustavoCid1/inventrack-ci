@extends('layouts.app')
@section('title', 'Punto de venta - InvenTrack')
@section('content')
<h1 class="text-2xl font-bold mb-4">Punto de venta</h1>
<form method="POST" action="{{ route('ventas.store') }}" class="bg-white rounded-lg shadow p-6 space-y-3">
    @csrf
    @for ($i = 0; $i < 3; $i++)
        <div class="flex gap-3">
            <select name="items[{{ $i }}][producto_id]" class="border rounded px-3 py-2 flex-1" @if($i === 0) required @endif>
                <option value="">Producto…</option>
                @foreach ($productos as $p)<option value="{{ $p->id }}">{{ $p->nombre }} — ${{ $p->precio }} ({{ $p->stock }} disp.)</option>@endforeach
            </select>
            <input type="number" min="1" name="items[{{ $i }}][cantidad]" placeholder="Cant." class="border rounded px-3 py-2 w-28" @if($i === 0) required @endif>
        </div>
    @endfor
    <p class="text-sm text-slate-500">El total incluye IVA del 16 %.</p>
    <button class="bg-emerald-600 text-white rounded px-4 py-2">Registrar venta</button>
</form>
@endsection
