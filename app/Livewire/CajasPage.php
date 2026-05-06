<?php

namespace App\Livewire;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Caja')]
class CajasPage extends Component
{
    public string|int|float $monto_inicial = '0.00';
    public string|int|float $monto_final = '0.00';

    public function render()
    {
        $cajaAbierta = Caja::query()->where('estado', 'abierta')->orderByDesc('fecha_apertura')->first();
        $cajas = Caja::query()->orderByDesc('fecha_apertura')->limit(20)->get();

        $cajaIds = $cajas->pluck('id')->all();

        $resumenMovimientos = collect();
        $resumenVentas = collect();
        $productosVendidosPorCaja = [];
        $montoEsperadoCajaAbierta = null;

        try {
            if ($cajaIds !== []) {
                $resumenMovimientos = MovimientoCaja::query()
                    ->whereIn('caja_id', $cajaIds)
                    ->selectRaw("caja_id,")
                    ->selectRaw("SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END) as neto,")
                    ->selectRaw("SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,")
                    ->selectRaw("SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as egresos")
                    ->groupBy('caja_id')
                    ->get()
                    ->keyBy('caja_id');

                $resumenVentas = Venta::query()
                    ->whereIn('caja_id', $cajaIds)
                    ->selectRaw('caja_id, COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total')
                    ->groupBy('caja_id')
                    ->get()
                    ->keyBy('caja_id');

                $rows = VentaDetalle::query()
                    ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
                    ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                    ->whereIn('ventas.caja_id', $cajaIds)
                    ->selectRaw('ventas.caja_id as caja_id, productos.codigo as codigo, productos.nombre as nombre')
                    ->selectRaw('SUM(venta_detalles.cantidad) as cantidad')
                    ->selectRaw('SUM(venta_detalles.subtotal) as total_vendido')
                    ->groupBy('ventas.caja_id', 'productos.codigo', 'productos.nombre')
                    ->orderBy('ventas.caja_id')
                    ->orderByRaw('SUM(venta_detalles.cantidad) DESC')
                    ->get();

                foreach ($rows as $row) {
                    $productosVendidosPorCaja[(int) $row->caja_id] ??= [];
                    if (count($productosVendidosPorCaja[(int) $row->caja_id]) >= 10) {
                        continue;
                    }
                    $productosVendidosPorCaja[(int) $row->caja_id][] = $row;
                }
            }

            if ($cajaAbierta) {
                $mov = $resumenMovimientos->get($cajaAbierta->id);
                $neto = (float) ($mov?->neto ?? 0);
                $montoEsperadoCajaAbierta = (float) $cajaAbierta->monto_inicial + $neto;
            }
        } catch (\Throwable $e) {
            // No bloquea el módulo si hay problemas de BD en entornos de prueba.
        }

        return view('livewire.cajas-page', [
            'cajaAbierta' => $cajaAbierta,
            'cajas' => $cajas,
            'resumenMovimientos' => $resumenMovimientos,
            'resumenVentas' => $resumenVentas,
            'productosVendidosPorCaja' => $productosVendidosPorCaja,
            'montoEsperadoCajaAbierta' => $montoEsperadoCajaAbierta,
        ]);
    }

    public function abrir(): void
    {
        DB::transaction(function () {
            $existe = Caja::query()->lockForUpdate()->where('estado', 'abierta')->exists();
            if ($existe) {
                return;
            }

            Caja::query()->create([
                'fecha_apertura' => now(),
                'monto_inicial' => $this->monto_inicial,
                'estado' => 'abierta',
            ]);
        });

        session()->flash('status', 'Caja abierta.');
    }

    public function cerrar(int $cajaId): void
    {
        DB::transaction(function () use ($cajaId) {
            $caja = Caja::query()->lockForUpdate()->findOrFail($cajaId);
            if ($caja->estado !== 'abierta') {
                return;
            }

            $neto = (float) (MovimientoCaja::query()
                ->where('caja_id', $caja->id)
                ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END), 0) as neto")
                ->value('neto') ?? 0);

            $montoEsperado = (float) $caja->monto_inicial + $neto;
            $montoFinalIngresado = (float) $this->monto_final;

            if ($montoFinalIngresado <= 0 && $montoEsperado > 0) {
                $this->monto_final = number_format($montoEsperado, 2, '.', '');
            }

            $caja->fecha_cierre = now();
            $caja->monto_final = number_format((float) $this->monto_final, 2, '.', '');
            $caja->estado = 'cerrada';
            $caja->save();
        });

        session()->flash('status', 'Caja cerrada.');

        $this->monto_final = '0.00';
    }

    public function usarMontoFinalEsperado(): void
    {
        $caja = Caja::query()->where('estado', 'abierta')->orderByDesc('fecha_apertura')->first();
        if (! $caja) {
            return;
        }

        $neto = (float) (MovimientoCaja::query()
            ->where('caja_id', $caja->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END), 0) as neto")
            ->value('neto') ?? 0);

        $montoEsperado = (float) $caja->monto_inicial + $neto;
        $this->monto_final = number_format($montoEsperado, 2, '.', '');
    }
}
