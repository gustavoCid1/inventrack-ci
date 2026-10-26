<?php

namespace Tests\Unit;

use App\Services\CalculadoraVenta;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CalculadoraVentaTest extends TestCase
{
    private CalculadoraVenta $calculadora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculadora = new CalculadoraVenta;
    }

    #[Test]
    public function calcula_subtotal_iva_y_total_de_una_venta(): void
    {
        $totales = $this->calculadora->calcularTotales([
            ['precio' => 100, 'cantidad' => 2],
            ['precio' => 50.5, 'cantidad' => 1],
        ]);

        $this->assertSame(250.5, $totales['subtotal']);
        $this->assertSame(40.08, $totales['iva']);
        $this->assertSame(290.58, $totales['total']);
    }

    #[Test]
    public function el_iva_aplicado_es_del_16_por_ciento(): void
    {
        $totales = $this->calculadora->calcularTotales([['precio' => 1000, 'cantidad' => 1]]);

        $this->assertSame(160.0, $totales['iva']);
        $this->assertSame(1160.0, $totales['total']);
    }

    #[Test]
    public function redondea_los_importes_a_dos_decimales(): void
    {
        $totales = $this->calculadora->calcularTotales([['precio' => 0.333, 'cantidad' => 3]]);

        $this->assertSame(1.0, $totales['subtotal']);
        $this->assertSame(0.16, $totales['iva']);
    }

    #[Test]
    public function rechaza_una_venta_sin_productos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculadora->calcularTotales([]);
    }

    #[Test]
    public function rechaza_cantidades_cero_o_negativas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculadora->importe(10, -3);
    }

    #[Test]
    public function rechaza_precios_negativos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculadora->importe(-5, 1);
    }
}
