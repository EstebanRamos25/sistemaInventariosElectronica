<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Modelo para el historial de tasas de cambio USD → Bs.
 *
 * @property int         $id
 * @property float       $tasa     Bs por 1 USD
 * @property string|null $fuente
 * @property string|null $notas
 * @property Carbon      $fecha
 */
class TasaCambio extends Model
{
    protected $table = 'tasas_cambio';

    protected $fillable = [
        'tasa',
        'fuente',
        'notas',
        'fecha',
    ];

    protected $casts = [
        'tasa'  => 'decimal:4',
        'fecha' => 'date',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Tasa vigente = la de mayor fecha que no supere hoy.
     */
    public static function vigente(): ?self
    {
        return self::query()
            ->where('fecha', '<=', now()->toDateString())
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Convierte un valor en USD a Bs usando la tasa dada (o la vigente).
     */
    public static function convertirUsdABs(float $usd, ?float $tasa = null): float
    {
        $tasa ??= (float) (self::vigente()?->tasa ?? 1);
        return round($usd * $tasa, 2);
    }

    /**
     * Convierte un valor en Bs a USD usando la tasa dada (o la vigente).
     */
    public static function convertirBsAUsd(float $bs, ?float $tasa = null): float
    {
        $tasa ??= (float) (self::vigente()?->tasa ?? 1);
        return $tasa > 0 ? round($bs / $tasa, 2) : 0.0;
    }
}
