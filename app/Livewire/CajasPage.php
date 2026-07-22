<?php

namespace App\Livewire;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
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

    public ?string $reporte_fecha = null;
    public bool $reporte_cargado = false;

    /** @var array{resumen:array<string, mixed>, totales_por_pago:array<int, array<string, mixed>>, ventas:array<int, array<string, mixed>>, movimientos:array<int, array<string, mixed>>, productos:array<int, array<string, mixed>>} */
    public array $reporte = [
        'resumen' => [],
        'totales_por_pago' => [],
        'ventas' => [],
        'movimientos' => [],
        'productos' => [],
    ];

    public function mount(): void
    {
        $this->reporte_fecha = now()->toDateString();
    }

    public function render()
    {
        $cajaAbierta = Caja::query()->where('estado', 'abierta')->orderByDesc('fecha_apertura')->first();
        $cajas = Caja::query()->orderByDesc('fecha_apertura')->limit(20)->get();

        $cajaIds = $cajas->pluck('id')->all();

        $resumenMovimientos = collect();
        $resumenVentas = collect();
        $productosVendidosPorCaja = [];
        $montoEsperadoCajaAbierta = null;

        if ($cajaIds !== []) {
            try {
                $resumenMovimientos = MovimientoCaja::query()
                    ->whereIn('caja_id', $cajaIds)
                    ->select('caja_id')
                    ->selectRaw("SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END) as neto")
                    ->selectRaw("SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos")
                    ->selectRaw("SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as egresos")
                    ->groupBy('caja_id')
                    ->get()
                    ->keyBy('caja_id');
            } catch (\Throwable $e) {
                // No bloquea el módulo si hay problemas de BD en entornos de prueba.
            }

            try {
                $resumenVentas = Venta::query()
                    ->whereIn('caja_id', $cajaIds)
                    ->select('caja_id')
                    ->selectRaw('COUNT(*) as cantidad')
                    ->selectRaw('COALESCE(SUM(total), 0) as total')
                    ->groupBy('caja_id')
                    ->get()
                    ->keyBy('caja_id');
            } catch (\Throwable $e) {
                // No bloquea el módulo.
            }

            try {
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
            } catch (\Throwable $e) {
                // No bloquea el módulo.
            }
        }

        try {
            if ($cajaAbierta) {
                $mov = $resumenMovimientos->get($cajaAbierta->id);
                $neto = (float) ($mov?->neto ?? 0);
                $montoEsperadoCajaAbierta = (float) $cajaAbierta->monto_inicial + $neto;
            }
        } catch (\Throwable $e) {
            // No bloquea el módulo.
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
        $mensaje = 'Caja cerrada.';

        DB::transaction(function () use ($cajaId, &$mensaje) {
            $caja = Caja::query()->lockForUpdate()->findOrFail($cajaId);
            if ($caja->estado !== 'abierta') {
                return;
            }

            $neto = (float) (MovimientoCaja::query()
                ->where('caja_id', $caja->id)
                ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END), 0) as neto")
                ->value('neto') ?? 0);

            $tieneVentas = Venta::query()->where('caja_id', $caja->id)->exists();

            // Si no hubo ventas ni movimientos → eliminar el registro (sesión vacía)
            if (! $tieneVentas && $neto == 0.0) {
                $caja->delete();
                $mensaje = 'Caja cerrada y sesión vacía eliminada del historial.';
                return;
            }

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

        session()->flash('status', $mensaje);

        $this->monto_final = '0.00';
    }

    public function generarReporte(): void
    {
        $data = $this->validate([
            'reporte_fecha' => ['required', 'date'],
        ]);

        $fecha = Carbon::parse($data['reporte_fecha']);
        $inicio = $fecha->copy()->startOfDay();
        $fin = $fecha->copy()->endOfDay();

        $this->reporte = [
            'resumen' => [],
            'totales_por_pago' => [],
            'ventas' => [],
            'movimientos' => [],
            'productos' => [],
        ];

        try {
            $ventas = Venta::query()
                ->whereBetween('fecha_venta', [$inicio, $fin])
                ->orderBy('fecha_venta')
                ->get(['id', 'caja_id', 'numero_venta', 'fecha_venta', 'tipo_pago', 'total']);

            $movResumen = MovimientoCaja::query()
                ->whereBetween('fecha_movimiento', [$inicio, $fin])
                ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END), 0) as neto")
                ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) as ingresos")
                ->selectRaw("COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0) as egresos")
                ->first();

            $movimientos = MovimientoCaja::query()
                ->whereBetween('fecha_movimiento', [$inicio, $fin])
                ->orderBy('fecha_movimiento')
                ->get(['id', 'caja_id', 'tipo', 'categoria', 'monto', 'descripcion', 'fecha_movimiento']);

            $productos = VentaDetalle::query()
                ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
                ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                ->whereBetween('ventas.fecha_venta', [$inicio, $fin])
                ->selectRaw('productos.codigo as codigo, productos.nombre as nombre')
                ->selectRaw('SUM(venta_detalles.cantidad) as cantidad')
                ->selectRaw('SUM(venta_detalles.subtotal) as total_vendido')
                ->groupBy('productos.codigo', 'productos.nombre')
                ->orderByRaw('SUM(venta_detalles.cantidad) DESC')
                ->get();

            $ventasTotal = (float) $ventas->sum('total');
            $ventasCantidad = (int) $ventas->count();

            $totalesPorPago = $ventas
                ->groupBy('tipo_pago')
                ->map(fn ($group) => [
                    'tipo_pago' => (string) ($group->first()->tipo_pago ?? ''),
                    'cantidad' => (int) $group->count(),
                    'total' => (float) $group->sum('total'),
                ])
                ->values()
                ->all();

            $this->reporte['resumen'] = [
                'fecha' => $fecha->toDateString(),
                'ventas_cantidad' => $ventasCantidad,
                'ventas_total' => $ventasTotal,
                'mov_neto' => (float) ($movResumen?->neto ?? 0),
                'mov_ingresos' => (float) ($movResumen?->ingresos ?? 0),
                'mov_egresos' => (float) ($movResumen?->egresos ?? 0),
                'mov_cantidad' => (int) $movimientos->count(),
                'productos_distintos' => (int) $productos->count(),
            ];

            $this->reporte['totales_por_pago'] = $totalesPorPago;

            $this->reporte['ventas'] = $ventas
                ->map(fn ($v) => [
                    'fecha_venta' => (string) $v->fecha_venta,
                    'numero_venta' => (string) $v->numero_venta,
                    'tipo_pago' => (string) $v->tipo_pago,
                    'total' => (float) $v->total,
                    'caja_id' => (int) $v->caja_id,
                ])
                ->all();

            $this->reporte['movimientos'] = $movimientos
                ->map(fn ($m) => [
                    'fecha_movimiento' => (string) $m->fecha_movimiento,
                    'tipo' => (string) $m->tipo,
                    'categoria' => (string) $m->categoria,
                    'monto' => (float) $m->monto,
                    'descripcion' => (string) ($m->descripcion ?? ''),
                    'caja_id' => (int) $m->caja_id,
                ])
                ->all();

            $this->reporte['productos'] = $productos
                ->map(fn ($p) => [
                    'codigo' => (string) $p->codigo,
                    'nombre' => (string) $p->nombre,
                    'cantidad' => (int) $p->cantidad,
                    'total_vendido' => (float) $p->total_vendido,
                ])
                ->all();

            $this->reporte_cargado = true;
        } catch (\Throwable $e) {
            $this->reporte_cargado = false;
            report($e);
            $this->addError('reporte_fecha', 'No se pudo generar el reporte.');
        }
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
