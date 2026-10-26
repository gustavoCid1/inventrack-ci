<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;

class AlertasStockCommand extends Command
{
    protected $signature = 'inventario:alertas';

    protected $description = 'Lista los productos con stock igual o menor a su mínimo';

    public function handle(): int
    {
        $productos = Producto::bajoStock()->orderBy('stock')->get(['sku', 'nombre', 'stock', 'stock_minimo']);

        if ($productos->isEmpty()) {
            $this->info('Sin alertas de stock.');

            return self::SUCCESS;
        }

        $this->table(['SKU', 'Producto', 'Stock', 'Mínimo'], $productos->toArray());

        return self::SUCCESS;
    }
}
