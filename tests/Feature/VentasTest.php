<?php

namespace Tests\Feature;

use App\Exceptions\StockInsuficienteException;
use App\Models\Producto;
use App\Models\User;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VentasTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registrar_una_venta_guarda_totales_y_descuenta_inventario(): void
    {
        $vendedor = User::factory()->create();
        $producto = Producto::factory()->create(['precio' => 100, 'stock' => 10]);

        $venta = app(VentaService::class)->registrar($vendedor, [['producto_id' => $producto->id, 'cantidad' => 3]]);

        $this->assertEquals(300, $venta->subtotal);
        $this->assertEquals(48, $venta->iva);
        $this->assertEquals(348, $venta->total);
        $this->assertSame(7, $producto->fresh()?->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $producto->id, 'tipo' => 'salida', 'cantidad' => 3]);
    }

    #[Test]
    public function no_vende_mas_de_lo_que_hay_en_existencia_y_no_deja_datos_a_medias(): void
    {
        $vendedor = User::factory()->create();
        $conStock = Producto::factory()->create(['stock' => 10]);
        $sinStock = Producto::factory()->create(['stock' => 1]);

        try {
            app(VentaService::class)->registrar($vendedor, [
                ['producto_id' => $conStock->id, 'cantidad' => 2],
                ['producto_id' => $sinStock->id, 'cantidad' => 5],
            ]);
            $this->fail('Se esperaba StockInsuficienteException');
        } catch (StockInsuficienteException) {
            // La transacción debe revertir todo.
        }

        $this->assertSame(10, $conStock->fresh()?->stock);
        $this->assertDatabaseCount('ventas', 0);
    }

    #[Test]
    public function el_punto_de_venta_registra_una_venta_desde_el_formulario(): void
    {
        $producto = Producto::factory()->create(['precio' => 20, 'stock' => 5]);

        $this->actingAs(User::factory()->create())
            ->post('/ventas', ['items' => [['producto_id' => $producto->id, 'cantidad' => 2]]])
            ->assertRedirect('/ventas/nueva')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('ventas', ['total' => 46.40]);
    }

    #[Test]
    public function el_punto_de_venta_muestra_error_si_no_hay_stock(): void
    {
        $producto = Producto::factory()->create(['stock' => 1]);

        $this->actingAs(User::factory()->create())
            ->from('/ventas/nueva')
            ->post('/ventas', ['items' => [['producto_id' => $producto->id, 'cantidad' => 9]]])
            ->assertRedirect('/ventas/nueva')
            ->assertSessionHasErrors('items');
    }
}
