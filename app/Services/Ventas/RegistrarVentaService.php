<?php

namespace App\Services\Ventas;

use App\Models\AlertaReposicion;
use App\Models\Caja;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\Ventas\Exceptions\CajaNoAbiertaException;
use App\Services\Ventas\Exceptions\StockInsuficienteException;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RegistrarVentaService
{
    /**
     * @param  array<int, array{producto_id:int, cantidad:int, precio_unitario?:string|int|float}>  $items
     */
    public function registrar(
        int $cajaId,
        array $items,
        string $tipoPago,
        string|int|float $descuento = '0',
        ?string $observaciones = null,
        ?CarbonInterface $fechaVenta = null,
    ): Venta {
        $itemsNormalizados = $this->normalizarItems($items);
        if ($itemsNormalizados === []) {
            throw new InvalidArgumentException('La venta debe tener al menos 1 item.');
        }

        $fechaVenta = $fechaVenta ?? now();
        $descuento = $this->toDecimalString($descuento);

        return DB::transaction(function () use ($cajaId, $itemsNormalizados, $tipoPago, $descuento, $observaciones, $fechaVenta) {
            $caja = Caja::query()->lockForUpdate()->findOrFail($cajaId);

            if ($caja->estado !== 'abierta' || $caja->fecha_cierre !== null) {
                throw new CajaNoAbiertaException('No se puede vender: la caja no está abierta.');
            }

            $venta = new Venta();
            $venta->caja_id = $caja->id;
            $venta->numero_venta = $this->generarNumeroVenta($caja->id, $fechaVenta);
            $venta->fecha_venta = $fechaVenta;
            $venta->tipo_pago = $tipoPago;
            $venta->estado = 'completada';
            $venta->observaciones = $observaciones;

            $subtotal = '0.00';
            $detalleRows = [];
            $movimientos = [];
            $alertasPendientes = [];

            foreach ($itemsNormalizados as $item) {
                $producto = Producto::query()->lockForUpdate()->findOrFail($item['producto_id']);

                if (! $producto->activo) {
                    throw new InvalidArgumentException("Producto inactivo: {$producto->codigo}");
                }

                $cantidad = (int) $item['cantidad'];
                if ($cantidad <= 0) {
                    throw new InvalidArgumentException('La cantidad debe ser mayor a 0.');
                }

                $stockAnterior = (int) $producto->stock_actual;
                if ($stockAnterior < $cantidad) {
                    throw new StockInsuficienteException("Stock insuficiente para {$producto->codigo}. Disponible: {$stockAnterior}, requerido: {$cantidad}.");
                }

                $stockNuevo = $stockAnterior - $cantidad;

                $precioUnitario = array_key_exists('precio_unitario', $item)
                    ? $this->toDecimalString($item['precio_unitario'])
                    : $this->toDecimalString($producto->precio_venta);

                $lineSubtotal = $this->mul($precioUnitario, $cantidad);
                $subtotal = $this->add($subtotal, $lineSubtotal);

                $detalleRows[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $lineSubtotal,
                ];

                $movimientos[] = [
                    'producto_id' => $producto->id,
                    'tipo' => 'salida',
                    'motivo' => 'venta',
                    'cantidad' => $cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                    'observaciones' => null,
                    'fecha_movimiento' => $fechaVenta,
                ];

                $producto->stock_actual = $stockNuevo;
                $producto->save();

                if ($stockNuevo <= (int) $producto->stock_minimo) {
                    $alertasPendientes[] = [
                        'producto_id' => $producto->id,
                        'stock_actual' => $stockNuevo,
                        'stock_minimo' => (int) $producto->stock_minimo,
                        'estado' => 'pendiente',
                        'fecha_alerta' => $fechaVenta,
                    ];
                }
            }

            $total = $this->sub($subtotal, $descuento);
            if ($this->cmp($total, '0.00') < 0) {
                throw new InvalidArgumentException('El descuento no puede ser mayor al subtotal.');
            }

            $venta->subtotal = $subtotal;
            $venta->descuento = $descuento;
            $venta->total = $total;
            $venta->save();

            foreach ($detalleRows as $row) {
                $venta->detalles()->create($row);
            }

            foreach ($movimientos as $movimiento) {
                $venta->movimientosInventario()->create($movimiento);
            }

            $venta->movimientosCaja()->create([
                'caja_id' => $caja->id,
                'tipo' => 'ingreso',
                'categoria' => 'venta',
                'monto' => $venta->total,
                'descripcion' => "Venta {$venta->numero_venta}",
                'fecha_movimiento' => $fechaVenta,
            ]);

            foreach ($alertasPendientes as $alertaData) {
                $alerta = AlertaReposicion::query()->firstOrNew([
                    'producto_id' => $alertaData['producto_id'],
                    'estado' => 'pendiente',
                ]);

                $alerta->stock_actual = $alertaData['stock_actual'];
                $alerta->stock_minimo = $alertaData['stock_minimo'];
                $alerta->fecha_alerta = $alertaData['fecha_alerta'];
                $alerta->save();
            }

            return $venta;
        });
    }

    /**
     * @param  array<int, array{producto_id:int, cantidad:int, precio_unitario?:string|int|float}>  $items
     * @return array<int, array{producto_id:int, cantidad:int, precio_unitario?:string}>
     */
    private function normalizarItems(array $items): array
    {
        $agrupados = [];

        foreach ($items as $item) {
            $productoId = (int) Arr::get($item, 'producto_id', 0);
            $cantidad = (int) Arr::get($item, 'cantidad', 0);

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            $key = (string) $productoId;
            if (! isset($agrupados[$key])) {
                $agrupados[$key] = [
                    'producto_id' => $productoId,
                    'cantidad' => 0,
                ];

                if (array_key_exists('precio_unitario', $item)) {
                    $agrupados[$key]['precio_unitario'] = $this->toDecimalString($item['precio_unitario']);
                }
            }

            $agrupados[$key]['cantidad'] += $cantidad;
        }

        return array_values($agrupados);
    }

    private function generarNumeroVenta(int $cajaId, CarbonInterface $fechaVenta): string
    {
        $base = 'V-'.$fechaVenta->format('Ymd-His').'-C'.$cajaId;

        for ($i = 0; $i < 5; $i++) {
            $numero = $base.'-'.Str::upper(Str::random(4));
            if (! Venta::query()->where('numero_venta', $numero)->exists()) {
                return $numero;
            }
        }

        return $base.'-'.Str::uuid()->toString();
    }

    private function toDecimalString(string|int|float $value): string
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
            if ($value === '') {
                return '0.00';
            }
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function add(string $a, string $b): string
    {
        if (function_exists('bcadd')) {
            return bcadd($a, $b, 2);
        }

        return number_format(((float) $a) + ((float) $b), 2, '.', '');
    }

    private function sub(string $a, string $b): string
    {
        if (function_exists('bcsub')) {
            return bcsub($a, $b, 2);
        }

        return number_format(((float) $a) - ((float) $b), 2, '.', '');
    }

    private function mul(string $a, int $b): string
    {
        if (function_exists('bcmul')) {
            return bcmul($a, (string) $b, 2);
        }

        return number_format(((float) $a) * $b, 2, '.', '');
    }

    private function cmp(string $a, string $b): int
    {
        if (function_exists('bccomp')) {
            return bccomp($a, $b, 2);
        }

        return ((float) $a) <=> ((float) $b);
    }
}
