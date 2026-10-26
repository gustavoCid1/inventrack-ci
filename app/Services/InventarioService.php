<?php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventarioService
{
    public function registrarMovimiento(Producto $producto, string $tipo, int $cantidad, ?User $usuario = null, ?string $motivo = null): Producto
    {
        if (! in_array($tipo, [MovimientoInventario::ENTRADA, MovimientoInventario::SALIDA], true)) {
            throw new InvalidArgumentException("Tipo de movimiento no válido: {$tipo}");
        }

        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($producto, $tipo, $cantidad, $usuario, $motivo) {
            if ($tipo === MovimientoInventario::SALIDA && $producto->stock < $cantidad) {
                throw StockInsuficienteException::para($producto->nombre, $producto->stock, $cantidad);
            }

            $tipo === MovimientoInventario::ENTRADA
                ? $producto->increment('stock', $cantidad)
                : $producto->decrement('stock', $cantidad);

            $producto->movimientos()->create([
                'user_id' => $usuario?->id,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'motivo' => $motivo,
            ]);

            return $producto->refresh();
        });
    }
}
