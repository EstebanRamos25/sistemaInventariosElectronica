<?php

namespace App\Models;

use App\Models\AlertaReposicion;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompraDetalle;
use App\Models\RecepcionDetalle;
use App\Models\VentaDetalle;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'marca_id',
        'codigo',
        'nombre',
        'descripcion',
        'modelo_tv',
        'pulgadas_tv',
        'voltaje_led',
        'leds_por_barra',
        'caracteristicas_barra',
        'unidad',
        'empaque',
        'unidades_por_empaque',
        'precio_compra',
        'precio_venta',
        'precio_compra_barra',
        'precio_venta_barra',
        'moneda',
        'stock_actual',
        'stock_barras_sueltas',
        'stock_minimo',
        'stock_ideal',
        'tiempo_reposicion_dias',
        'ubicacion',
        'activo',
    ];

    protected $casts = [
        'precio_compra'       => 'decimal:2',
        'precio_venta'        => 'decimal:2',
        'precio_compra_barra' => 'decimal:2',
        'precio_venta_barra'  => 'decimal:2',
        'voltaje_led'         => 'decimal:2',
        'activo'              => 'boolean',
    ];

    /**
     * Total de barras individuales disponibles:
     * (stock_actual × barras_por_juego) + stock_barras_sueltas
     */
    protected function totalBarrasDisponibles(): Attribute
    {
        return Attribute::make(
            get: function () {
                $barrasPorJuego = max(1, (int) $this->unidades_por_empaque);
                return ((int) $this->stock_actual * $barrasPorJuego)
                    + (int) $this->stock_barras_sueltas;
            }
        );
    }

    /**
     * Precio de venta del juego expresado en Bs.
     * Si el producto está en USD, convierte con la tasa vigente.
     * Si ya está en Bs, lo devuelve tal cual.
     */
    protected function precioVentaEnBs(): Attribute
    {
        return Attribute::make(
            get: function () {
                $precio = (float) $this->precio_venta;
                if ($this->moneda === 'USD') {
                    return TasaCambio::convertirUsdABs($precio);
                }
                return round($precio, 2);
            }
        );
    }

    /**
     * Precio de venta del juego expresado en USD.
     * Si el producto está en Bs, convierte con la tasa vigente.
     * Si ya está en USD, lo devuelve tal cual.
     */
    protected function precioVentaEnUsd(): Attribute
    {
        return Attribute::make(
            get: function () {
                $precio = (float) $this->precio_venta;
                if ($this->moneda === 'Bs') {
                    return TasaCambio::convertirBsAUsd($precio);
                }
                return round($precio, 2);
            }
        );
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    public function ventaDetalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'producto_id');
    }

    public function ordenCompraDetalles(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'producto_id');
    }

    public function recepcionDetalles(): HasMany
    {
        return $this->hasMany(RecepcionDetalle::class, 'producto_id');
    }

    public function alertasReposicion(): HasMany
    {
        return $this->hasMany(AlertaReposicion::class, 'producto_id');
    }

    protected function requiereReposicion(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->stock_actual <= (int) $this->stock_minimo,
        );
    }
}
