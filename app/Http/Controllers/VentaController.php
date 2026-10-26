<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\VentaRequest;
use App\Models\Producto;
use App\Models\User;
use App\Services\VentaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function create(): View
    {
        return view('ventas.create', ['productos' => Producto::where('stock', '>', 0)->orderBy('nombre')->get()]);
    }

    public function store(VentaRequest $request, VentaService $ventas): RedirectResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        try {
            $venta = $ventas->registrar($usuario, $request->validated('items'));
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('ventas.create')->with('status', "Venta #{$venta->id} registrada. Total: \${$venta->total}");
    }
}
