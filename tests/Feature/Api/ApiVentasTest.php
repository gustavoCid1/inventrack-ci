<?php

namespace Tests\Feature\Api;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiVentasTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function crea_una_venta_y_actualiza_el_inventario(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $producto = Producto::factory()->create(['precio' => 50, 'stock' => 10]);

        $this->postJson('/api/ventas', ['items' => [['producto_id' => $producto->id, 'cantidad' => 4]]])
            ->assertCreated()
            ->assertJsonPath('total', '232.00')
            ->assertJsonCount(1, 'detalles');

        $this->assertSame(6, $producto->fresh()?->stock);
    }

    #[Test]
    public function responde_422_cuando_no_hay_stock_suficiente(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $producto = Producto::factory()->create(['stock' => 2]);

        $this->postJson('/api/ventas', ['items' => [['producto_id' => $producto->id, 'cantidad' => 3]]])
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => "Stock insuficiente para {$producto->nombre}: disponible 2, solicitado 3."]);
    }

    #[Test]
    public function valida_la_estructura_de_la_venta(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ventas', ['items' => [['producto_id' => 999, 'cantidad' => 0]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.producto_id', 'items.0.cantidad']);
    }
}
