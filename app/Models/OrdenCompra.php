<?php

namespace App\Models;

use App\Models\MovimientoInventario;
use App\Models\OrdenCompraDetalle;
use App\Models\Proveedor;
use App\Models\Recepcion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'proveedor_id',
        'numero_orden',
        'fecha_orden',
        'fecha_estimada_llegada',
        'estado',
        'total',
        'observaciones',
    ];

    protected $casts = [
        'fecha_orden' => 'date',
        'fecha_estimada_llegada' => 'date',
        'total' => 'decimal:2',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'orden_compra_id');
    }

    public function recepciones(): HasMany
    {
        return $this->hasMany(Recepcion::class, 'orden_compra_id');
    }

    public function movimientosInventario(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }
}
