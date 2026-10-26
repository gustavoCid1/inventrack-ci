<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    public const ENTRADA = 'entrada';

    public const SALIDA = 'salida';

    protected $table = 'movimientos_inventario';

    /** @var list<string> */
    protected $fillable = ['producto_id', 'user_id', 'tipo', 'cantidad', 'motivo'];

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
