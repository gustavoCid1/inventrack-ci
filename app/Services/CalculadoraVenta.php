<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Cálculo de importes de una venta. No usa base de datos,
 * por eso se prueba con pruebas unitarias puras.
 */
class CalculadoraVenta
{
    public const TASA_IVA = 0.16;

    /**
     * @param  array<int, array{precio: float|int|string, cantidad: int}>  $partidas
     * @return array{subtotal: float, iva: float, total: float}
     */
    public function calcularTotales(array $partidas): array
    {
        if ($partidas === []) {
            throw new InvalidArgumentException('La venta debe tener al menos un producto.');
        }

        $subtotal = 0.0;

        foreach ($partidas as $partida) {
            $subtotal += $this->importe((float) $partida['precio'], (int) $partida['cantidad']);
        }

        $subtotal = round($subtotal, 2);
        $iva = round($subtotal * self::TASA_IVA, 2);

        return [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => round($subtotal + $iva, 2),
        ];
    }

    public function importe(float $precio, int $cantidad): float
    {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }

        if ($precio < 0) {
            throw new InvalidArgumentException('El precio no puede ser negativo.');
        }

        return round($precio * $cantidad, 2);
    }
}
