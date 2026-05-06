<?php

namespace App\Models;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\MovimientoInventario;
use App\Models\VentaDetalle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'caja_id',
        'numero_venta',
        'fecha_venta',
        'subtotal',
        'descuento',
        'total',
        'tipo_pago',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'venta_id');
    }

    public function movimientosInventario(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }

    public function movimientosCaja(): MorphMany
    {
        return $this->morphMany(MovimientoCaja::class, 'referencia');
    }
}
