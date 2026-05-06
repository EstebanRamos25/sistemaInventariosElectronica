<?php

namespace App\Models;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaReposicion extends Model
{
    use HasFactory;

    protected $table = 'alertas_reposicion';

    protected $fillable = [
        'producto_id',
        'stock_actual',
        'stock_minimo',
        'estado',
        'fecha_alerta',
    ];

    protected $casts = [
        'fecha_alerta' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
