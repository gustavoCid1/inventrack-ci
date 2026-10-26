<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $table = 'ventas';

    /** @var list<string> */
    protected $fillable = ['user_id', 'subtotal', 'iva', 'total'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<DetalleVenta, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
