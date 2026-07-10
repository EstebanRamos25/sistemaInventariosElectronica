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

                $cantidad   = (int) $item['cantidad'];
                $tipoVenta  = $item['tipo_venta'] ?? 'juego';

                if ($cantidad <= 0) {
                    throw new InvalidArgumentException('La cantidad debe ser mayor a 0.');
                }

                // ── Descuento de stock según tipo de venta ──────────────────
                if ($tipoVenta === 'barra') {
                    // Venta de barras sueltas
                    $stockBarras = (int) $producto->stock_barras_sueltas;
                    $stockJuegos = (int) $producto->stock_actual;

                    // Si no hay suficientes barras sueltas, intentar abrir un juego
                    if ($stockBarras < $cantidad) {
                        $barrasPorJuego = max(1, (int) $producto->unidades_por_empaque);

                        // Calcular cuántas barras faltan
                        $barrasFaltantes = $cantidad - $stockBarras;
                        $juegosNecesarios = (int) ceil($barrasFaltantes / $barrasPorJuego);

                        if ($stockJuegos < $juegosNecesarios) {
                            $totalBarras = ($stockJuegos * $barrasPorJuego) + $stockBarras;
                            throw new StockInsuficienteException(
                                "Stock insuficiente de barras para {$producto->codigo}. " .
                                "Disponible: {$stockBarras} barras sueltas + {$stockJuegos} juegos ({$totalBarras} barras en total), requerido: {$cantidad}."
                            );
                        }

                        // Abrir juegos necesarios
                        $barrasGeneradas = $juegosNecesarios * $barrasPorJuego;
                        $producto->stock_actual = $stockJuegos - $juegosNecesarios;
                        $stockBarras += $barrasGeneradas;
                    }

                    $stockBarrasNuevo = $stockBarras - $cantidad;
                    $producto->stock_barras_sueltas = $stockBarrasNuevo;

                    $stockAnterior = (int) $producto->getRawOriginal('stock_barras_sueltas');
                    $stockNuevo    = $stockBarrasNuevo;
                    $motivoMovimiento = 'venta_barra';

                    // Precio: usar precio_venta_barra si está definido
                    $precioDefault = (float) $producto->precio_venta_barra > 0
                        ? $this->toDecimalString($producto->precio_venta_barra)
                        : $this->toDecimalString($producto->precio_venta);

                } else {
                    // Venta de juego completo (bolsa cerrada)
                    $stockAnterior = (int) $producto->stock_actual;

                    if ($stockAnterior < $cantidad) {
                        throw new StockInsuficienteException(
                            "Stock insuficiente de juegos para {$producto->codigo}. " .
                            "Disponible: {$stockAnterior}, requerido: {$cantidad}."
                        );
                    }

                    $stockNuevo = $stockAnterior - $cantidad;
                    $producto->stock_actual = $stockNuevo;
                    $motivoMovimiento = 'venta';
                    $precioDefault = $this->toDecimalString($producto->precio_venta);
                }

                $precioUnitario = array_key_exists('precio_unitario', $item)
                    ? $this->toDecimalString($item['precio_unitario'])
                    : $precioDefault;

                $lineSubtotal = $this->mul($precioUnitario, $cantidad);
                $subtotal = $this->add($subtotal, $lineSubtotal);

                $detalleRows[] = [
                    'producto_id'    => $producto->id,
                    'tipo_venta'     => $tipoVenta,
                    'cantidad'       => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal'       => $lineSubtotal,
                ];

                $movimientos[] = [
                    'producto_id'    => $producto->id,
                    'tipo'           => 'salida',
                    'motivo'         => $motivoMovimiento,
                    'cantidad'       => $cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo'    => $stockNuevo,
                    'observaciones'  => $tipoVenta === 'barra' ? "Venta de {$cantidad} barra(s) suelta(s)" : null,
                    'fecha_movimiento' => $fechaVenta,
                ];

                $producto->save();

                if ((int) $producto->stock_actual <= (int) $producto->stock_minimo) {
                    $alertasPendientes[] = [
                        'producto_id' => $producto->id,
                        'stock_actual' => (int) $producto->stock_actual,
                        'stock_minimo' => (int) $producto->stock_minimo,
                        'estado'      => 'pendiente',
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
            $cantidad   = (int) Arr::get($item, 'cantidad', 0);
            $tipoVenta  = Arr::get($item, 'tipo_venta', 'juego');

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            // Agrupar por producto_id + tipo_venta (juego y barra son stocks distintos)
            $key = $productoId . '|' . $tipoVenta;

            if (! isset($agrupados[$key])) {
                $agrupados[$key] = [
                    'producto_id' => $productoId,
                    'tipo_venta'  => $tipoVenta,
                    'cantidad'    => 0,
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
