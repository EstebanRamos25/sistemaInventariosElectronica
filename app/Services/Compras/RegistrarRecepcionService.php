<?php

namespace App\Services\Compras;

use App\Models\AlertaReposicion;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Producto;
use App\Models\Recepcion;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarRecepcionService
{
    /**
     * @param  array<int, array{producto_id:int, cantidad_recibida:int}>  $items
     */
    public function registrar(
        int $ordenCompraId,
        array $items,
        ?string $observaciones = null,
        ?CarbonInterface $fechaRecepcion = null,
    ): Recepcion {
        $itemsNormalizados = $this->normalizarItems($items);
        if ($itemsNormalizados === []) {
            throw new InvalidArgumentException('La recepción debe tener al menos 1 item.');
        }

        $fechaRecepcion = $fechaRecepcion ?? now();

        return DB::transaction(function () use ($ordenCompraId, $itemsNormalizados, $observaciones, $fechaRecepcion) {
            $orden = OrdenCompra::query()->lockForUpdate()->findOrFail($ordenCompraId);

            if ($orden->estado === 'cancelado') {
                throw new \App\Services\Compras\Exceptions\OrdenNoRecibibleException('No se puede recepcionar: la orden está cancelada.');
            }

            $ordenDetalles = OrdenCompraDetalle::query()
                ->where('orden_compra_id', $orden->id)
                ->get(['producto_id', 'cantidad']);

            $ordenadoPorProducto = [];
            foreach ($ordenDetalles as $detalle) {
                $ordenadoPorProducto[(int) $detalle->producto_id] = (int) $detalle->cantidad;
            }

            foreach ($itemsNormalizados as $item) {
                if (! array_key_exists($item['producto_id'], $ordenadoPorProducto)) {
                    throw new InvalidArgumentException('La recepción incluye un producto que no está en la orden de compra.');
                }
            }

            $recepcion = new Recepcion();
            $recepcion->orden_compra_id = $orden->id;
            $recepcion->fecha_recepcion = $fechaRecepcion;
            $recepcion->observaciones = $observaciones;
            $recepcion->save();

            $movimientos = [];

            foreach ($itemsNormalizados as $item) {
                $producto = Producto::query()->lockForUpdate()->findOrFail($item['producto_id']);

                if (! $producto->activo) {
                    throw new InvalidArgumentException("Producto inactivo: {$producto->codigo}");
                }

                $cantidadRecibida = (int) $item['cantidad_recibida'];
                if ($cantidadRecibida <= 0) {
                    throw new InvalidArgumentException('La cantidad recibida debe ser mayor a 0.');
                }

                $stockAnterior = (int) $producto->stock_actual;
                $stockNuevo = $stockAnterior + $cantidadRecibida;

                $recepcion->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad_recibida' => $cantidadRecibida,
                ]);

                $movimientos[] = [
                    'producto_id' => $producto->id,
                    'tipo' => 'entrada',
                    'motivo' => 'compra',
                    'cantidad' => $cantidadRecibida,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                    'observaciones' => null,
                    'fecha_movimiento' => $fechaRecepcion,
                ];

                $producto->stock_actual = $stockNuevo;
                $producto->save();

                if ($stockNuevo > (int) $producto->stock_minimo) {
                    AlertaReposicion::query()
                        ->where('producto_id', $producto->id)
                        ->where('estado', 'pendiente')
                        ->update([
                            'estado' => 'resuelto',
                            'stock_actual' => $stockNuevo,
                            'stock_minimo' => (int) $producto->stock_minimo,
                        ]);
                }
            }

            foreach ($movimientos as $movimiento) {
                $recepcion->movimientosInventario()->create($movimiento);
            }

            if ($this->ordenEstaCompletamenteRecibida($orden->id)) {
                $orden->estado = 'recibido';
                $orden->save();
            }

            return $recepcion;
        });
    }

    /**
     * @param  array<int, array{producto_id:int, cantidad_recibida:int}>  $items
     * @return array<int, array{producto_id:int, cantidad_recibida:int}>
     */
    private function normalizarItems(array $items): array
    {
        $agrupados = [];

        foreach ($items as $item) {
            $productoId = (int) Arr::get($item, 'producto_id', 0);
            $cantidad = (int) Arr::get($item, 'cantidad_recibida', 0);

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            if (! isset($agrupados[$productoId])) {
                $agrupados[$productoId] = [
                    'producto_id' => $productoId,
                    'cantidad_recibida' => 0,
                ];
            }

            $agrupados[$productoId]['cantidad_recibida'] += $cantidad;
        }

        return array_values($agrupados);
    }

    /**
     * Compara lo ordenado (en unidades, como está guardado en orden_compra_detalles)
     * contra lo recibido (en juegos, como está guardado en recepcion_detalles).
     *
     * La conversión unidades → juegos se hace dividiendo por unidades_por_empaque
     * del producto. Si el producto no tiene unidades_por_empaque, se asume 1 unidad = 1 juego.
     */
    private function ordenEstaCompletamenteRecibida(int $ordenCompraId): bool
    {
        // Cargamos lo ordenado con piezas_por_juego del producto
        $detallesOrden = OrdenCompraDetalle::query()
            ->where('orden_compra_id', $ordenCompraId)
            ->join('productos', 'productos.id', '=', 'orden_compra_detalles.producto_id')
            ->selectRaw('
                orden_compra_detalles.producto_id,
                SUM(orden_compra_detalles.cantidad)      AS total_unidades_ordenadas,
                MAX(productos.unidades_por_empaque)      AS piezas_por_juego
            ')
            ->groupBy('orden_compra_detalles.producto_id')
            ->get();

        if ($detallesOrden->isEmpty()) {
            return false;
        }

        // Lo recibido está en JUEGOS (desde la corrección del Livewire component)
        $recibidoJuegosPorProducto = DB::table('recepcion_detalles')
            ->join('recepciones', 'recepciones.id', '=', 'recepcion_detalles.recepcion_id')
            ->where('recepciones.orden_compra_id', $ordenCompraId)
            ->selectRaw('recepcion_detalles.producto_id, SUM(recepcion_detalles.cantidad_recibida) as total_recibido_juegos')
            ->groupBy('recepcion_detalles.producto_id')
            ->pluck('total_recibido_juegos', 'recepcion_detalles.producto_id');

        foreach ($detallesOrden as $detalle) {
            $piezasPorJuego         = (int) ($detalle->piezas_por_juego ?? 0);
            $totalUnidadesOrdenadas = (int) $detalle->total_unidades_ordenadas;

            // Convertimos unidades ordenadas → juegos ordenados
            $totalJuegosOrdenados = $piezasPorJuego > 0
                ? (int) ceil($totalUnidadesOrdenadas / $piezasPorJuego)
                : $totalUnidadesOrdenadas;

            $totalJuegosRecibidos = (int) ($recibidoJuegosPorProducto[$detalle->producto_id] ?? 0);

            if ($totalJuegosRecibidos < $totalJuegosOrdenados) {
                return false;
            }
        }

        return true;
    }
}

