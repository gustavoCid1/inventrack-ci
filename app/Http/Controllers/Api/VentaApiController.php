<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VentaRequest;
use App\Models\User;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;

class VentaApiController extends Controller
{
    public function store(VentaRequest $request, VentaService $ventas): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        try {
            $venta = $ventas->registrar($usuario, $request->validated('items'));
        } catch (StockInsuficienteException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($venta, 201);
    }
}
