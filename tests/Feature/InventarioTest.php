<?php

namespace Tests\Feature;

use App\Exceptions\StockInsuficienteException;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function una_entrada_aumenta_el_stock_y_queda_registrada(): void
    {
        $producto = Producto::factory()->conStock(4)->create();

        $actualizado = app(InventarioService::class)->registrarMovimiento($producto, 'entrada', 20, null, 'Compra a proveedor');

        $this->assertSame(24, $actualizado->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $producto->id, 'tipo' => 'entrada', 'cantidad' => 20]);
    }

    #[Test]
    public function una_salida_mayor_al_stock_es_rechazada(): void
    {
        $producto = Producto::factory()->conStock(3)->create();

        $this->expectException(StockInsuficienteException::class);

        app(InventarioService::class)->registrarMovimiento($producto, 'salida', 10);
    }

    #[Test]
    public function rechaza_tipos_de_movimiento_desconocidos(): void
    {
        $producto = Producto::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(InventarioService::class)->registrarMovimiento($producto, 'ajuste', 1);
    }

    #[Test]
    public function el_scope_bajo_stock_filtra_correctamente(): void
    {
        Producto::factory()->conStock(1)->create();
        Producto::factory()->conStock(5, 5)->create();
        Producto::factory()->conStock(30)->create();

        $this->assertSame(2, Producto::bajoStock()->count());
    }

    #[Test]
    public function el_comando_de_alertas_lista_productos_con_stock_bajo(): void
    {
        Producto::factory()->conStock(1)->create(['sku' => 'ALR-1']);

        $this->artisan('inventario:alertas')
            ->expectsOutputToContain('ALR-1')
            ->assertSuccessful();
    }
}
