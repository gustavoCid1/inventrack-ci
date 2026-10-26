<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoRequest;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductoApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Producto::query()->buscar($request->string('q')->toString())->orderBy('nombre');

        if ($request->boolean('bajo_stock')) {
            $query->bajoStock();
        }

        return response()->json($query->paginate(20));
    }

    public function show(Producto $producto): JsonResponse
    {
        return response()->json($producto->load('categoria'));
    }

    public function store(ProductoRequest $request): JsonResponse
    {
        return response()->json(Producto::create($request->validated()), 201);
    }
}
