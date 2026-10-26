<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $productos = Producto::with('categoria')
            ->buscar($request->string('q')->toString())
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('productos.index', compact('productos'));
    }

    public function create(): View
    {
        return view('productos.form', ['producto' => new Producto, 'categorias' => Categoria::orderBy('nombre')->get()]);
    }

    public function store(ProductoRequest $request): RedirectResponse
    {
        Producto::create($request->validated());

        return redirect()->route('productos.index')->with('status', 'Producto creado.');
    }

    public function edit(Producto $producto): View
    {
        return view('productos.form', ['producto' => $producto, 'categorias' => Categoria::orderBy('nombre')->get()]);
    }

    public function update(ProductoRequest $request, Producto $producto): RedirectResponse
    {
        $producto->update($request->validated());

        return redirect()->route('productos.index')->with('status', 'Producto actualizado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        return redirect()->route('productos.index')->with('status', 'Producto eliminado.');
    }
}
