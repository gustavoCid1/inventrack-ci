<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Venta;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'totalProductos' => Producto::count(),
            'bajoStock' => Producto::bajoStock()->orderBy('stock')->get(),
            'ventasHoy' => Venta::whereDate('created_at', today())->sum('total'),
        ]);
    }
}
