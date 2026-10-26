<?php

namespace App\Models;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $sku
 * @property string $nombre
 * @property string $precio
 * @property int $stock
 * @property int $stock_minimo
 */
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    protected $table = 'productos';

    /** @var list<string> */
    protected $fillable = ['categoria_id', 'sku', 'nombre', 'precio', 'stock', 'stock_minimo'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'stock' => 'integer',
            'stock_minimo' => 'integer',
        ];
    }

    /** @return BelongsTo<Categoria, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** @return HasMany<MovimientoInventario, $this> */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function tieneStockBajo(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    /**
     * Productos cuyo stock llegó al mínimo o por debajo.
     *
     * @param  Builder<Producto>  $query
     * @return Builder<Producto>
     */
    public function scopeBajoStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    /**
     * Búsqueda por nombre o SKU.
     *
     * @param  Builder<Producto>  $query
     * @return Builder<Producto>
     */
    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if ($termino === null || trim($termino) === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
                ->orWhere('sku', 'like', "%{$termino}%");
        });
    }
}
