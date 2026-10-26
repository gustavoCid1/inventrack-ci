<?php

namespace Tests\Feature\Api;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiProductosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lista_productos_paginados(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Producto::factory()->count(3)->create();

        $this->getJson('/api/productos')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'sku', 'nombre', 'precio', 'stock']], 'total']);
    }

    #[Test]
    public function filtra_productos_con_stock_bajo(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Producto::factory()->conStock(1)->create();
        Producto::factory()->conStock(80)->create();

        $this->getJson('/api/productos?bajo_stock=1')->assertOk()->assertJsonCount(1, 'data');
    }

    #[Test]
    public function muestra_el_detalle_de_un_producto_y_404_si_no_existe(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $producto = Producto::factory()->create();

        $this->getJson("/api/productos/{$producto->id}")->assertOk()->assertJsonPath('sku', $producto->sku);
        $this->getJson('/api/productos/9999')->assertNotFound();
    }

    #[Test]
    public function solo_el_administrador_crea_productos_por_api(): void
    {
        $datos = ['sku' => 'API-1', 'nombre' => 'Café 250 g', 'precio' => 89.9, 'stock' => 15, 'stock_minimo' => 3];

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/productos', $datos)->assertForbidden();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->postJson('/api/productos', $datos)->assertCreated()->assertJsonPath('sku', 'API-1');
    }

    #[Test]
    public function la_api_valida_el_producto(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/productos', ['precio' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'nombre', 'precio', 'stock']);
    }
}
