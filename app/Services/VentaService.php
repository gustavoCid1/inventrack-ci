<?php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class VentaService
{
    public function __construct(private readonly CalculadoraVenta $calculadora) {}

    /**
     * Registra la venta, sus detalles y descuenta el inventario en una sola transacción.
     *
     * @param  array<int, array{producto_id: int, cantidad: int}>  $items
     *
     * @throws StockInsuficienteException
     */
    public function registrar(User $vendedor, array $items): Venta
    {
        return DB::transaction(function () use ($vendedor, $items) {
            $partidas = [];

            foreach ($items as $item) {
                /** @var Producto $producto */
                $producto = Producto::query()->lockForUpdate()->findOrFail($item['producto_id']);
                $cantidad = (int) $item['cantidad'];

                if ($producto->stock < $cantidad) {
                    throw StockInsuficienteException::para($producto->nombre, $producto->stock, $cantidad);
                }

                $partidas[] = ['producto' => $producto, 'precio' => (float) $producto->precio, 'cantidad' => $cantidad];
            }

            $totales = $this->calculadora->calcularTotales($partidas);

            $venta = Venta::create(['user_id' => $vendedor->id] + $totales);

            foreach ($partidas as $partida) {
                $producto = $partida['producto'];

                $venta->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $partida['cantidad'],
                    'precio_unitario' => $partida['precio'],
                    'importe' => $this->calculadora->importe($partida['precio'], $partida['cantidad']),
                ]);

                $producto->decrement('stock', $partida['cantidad']);

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'user_id' => $vendedor->id,
                    'tipo' => MovimientoInventario::SALIDA,
                    'cantidad' => $partida['cantidad'],
                    'motivo' => "Venta #{$venta->id}",
                ]);
            }

            return $venta->load('detalles');
        });
    }
}
