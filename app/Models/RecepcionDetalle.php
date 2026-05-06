<?php

namespace App\Models;

use App\Models\Producto;
use App\Models\Recepcion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecepcionDetalle extends Model
{
    use HasFactory;

    protected $table = 'recepcion_detalles';

    protected $fillable = [
        'recepcion_id',
        'producto_id',
        'cantidad_recibida',
    ];

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(Recepcion::class, 'recepcion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
