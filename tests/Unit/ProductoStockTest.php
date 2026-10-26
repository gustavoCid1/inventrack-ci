<?php

namespace Tests\Unit;

use App\Models\Producto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProductoStockTest extends TestCase
{
    /** @return array<string, array{int, int, bool}> */
    public static function casosDeStock(): array
    {
        return [
            'stock por encima del mínimo' => [20, 5, false],
            'stock igual al mínimo' => [5, 5, true],
            'stock por debajo del mínimo' => [2, 5, true],
            'producto agotado' => [0, 5, true],
        ];
    }

    #[Test]
    #[DataProvider('casosDeStock')]
    public function detecta_cuando_el_stock_esta_bajo(int $stock, int $minimo, bool $esperado): void
    {
        $producto = new Producto(['stock' => $stock, 'stock_minimo' => $minimo]);

        $this->assertSame($esperado, $producto->tieneStockBajo());
    }
}
