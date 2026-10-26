<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Administrador', 'email' => 'admin@inventrack.test']);
        User::factory()->create(['name' => 'Vendedor', 'email' => 'vendedor@inventrack.test']);

        foreach (['Abarrotes', 'Bebidas', 'Limpieza', 'Papelería'] as $nombre) {
            $categoria = Categoria::create(['nombre' => $nombre]);
            Producto::factory()->count(5)->create(['categoria_id' => $categoria->id]);
        }

        Producto::factory()->conStock(2)->create(['nombre' => 'Refresco 600 ml', 'sku' => 'BEB-0001']);
    }
}
