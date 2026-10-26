<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductosWebTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_catalogo_lista_productos_y_permite_buscar(): void
    {
        Producto::factory()->create(['nombre' => 'Arroz 1 kg', 'sku' => 'ABR-001']);
        Producto::factory()->create(['nombre' => 'Jabón líquido', 'sku' => 'LIM-001']);

        $this->actingAs(User::factory()->create())
            ->get('/productos?q=Arroz')
            ->assertOk()
            ->assertSee('Arroz 1 kg')
            ->assertDontSee('Jabón líquido');
    }

    #[Test]
    public function el_administrador_puede_crear_un_producto(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/productos', [
                'sku' => 'BEB-100', 'nombre' => 'Agua 1 L', 'precio' => 12.50, 'stock' => 40, 'stock_minimo' => 10,
            ])
            ->assertRedirect('/productos');

        $this->assertDatabaseHas('productos', ['sku' => 'BEB-100', 'stock' => 40]);
    }

    #[Test]
    public function valida_los_datos_obligatorios_del_producto(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/productos', ['sku' => '', 'nombre' => '', 'precio' => -1])
            ->assertSessionHasErrors(['sku', 'nombre', 'precio', 'stock']);
    }

    #[Test]
    public function no_permite_sku_duplicado(): void
    {
        Producto::factory()->create(['sku' => 'DUP-1']);

        $this->actingAs(User::factory()->admin()->create())
            ->post('/productos', ['sku' => 'DUP-1', 'nombre' => 'Otro', 'precio' => 10, 'stock' => 1, 'stock_minimo' => 1])
            ->assertSessionHasErrors('sku');
    }

    #[Test]
    public function el_administrador_puede_editar_y_eliminar(): void
    {
        $admin = User::factory()->admin()->create();
        $producto = Producto::factory()->create();

        $this->actingAs($admin)
            ->put("/productos/{$producto->id}", [
                'sku' => $producto->sku, 'nombre' => 'Nombre nuevo', 'precio' => 99, 'stock' => 3, 'stock_minimo' => 1,
            ])->assertRedirect('/productos');
        $this->assertSame('Nombre nuevo', $producto->fresh()?->nombre);

        $this->actingAs($admin)->delete("/productos/{$producto->id}")->assertRedirect('/productos');
        $this->assertModelMissing($producto);
    }

    #[Test]
    public function un_vendedor_no_puede_crear_productos(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/productos', ['sku' => 'X-1', 'nombre' => 'X', 'precio' => 1, 'stock' => 1, 'stock_minimo' => 1])
            ->assertForbidden();

        $this->assertDatabaseCount('productos', 0);
    }

    #[Test]
    public function el_panel_muestra_alertas_de_stock_bajo(): void
    {
        Producto::factory()->conStock(2)->create(['nombre' => 'Leche entera']);
        Producto::factory()->conStock(50)->create(['nombre' => 'Galletas']);

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Leche entera')
            ->assertDontSee('Galletas');
    }
}
