<?php

namespace App\Models;

use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Models\RecepcionDetalle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Recepcion extends Model
{
    use HasFactory;

    protected $table = 'recepciones';

    protected $fillable = [
        'orden_compra_id',
        'fecha_recepcion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_recepcion' => 'date',
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RecepcionDetalle::class, 'recepcion_id');
    }

    public function movimientosInventario(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }
}
