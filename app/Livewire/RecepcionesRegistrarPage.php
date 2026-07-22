<?php

namespace App\Livewire;

use App\Models\OrdenCompra;
use App\Models\Recepcion;
use App\Services\Compras\RegistrarRecepcionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Recepciones')]
class RecepcionesRegistrarPage extends Component
{
    // ── Formulario ───────────────────────────────────────────────────────────

    public int|string $orden_compra_id = '';
    public string     $fecha_recepcion;
    public ?string    $observaciones   = null;

    /**
     * Ítems cargados de la orden. Cada uno:
     * [
     *   'producto_id'      => int,
     *   'codigo'           => string,
     *   'nombre'           => string,
     *   'piezas_por_juego' => int|null,   // unidades_por_empaque del producto
     *   'cantidad_ordenada_juegos' => int, // juegos/paquetes en la orden
     *   'juegos_recibidos' => string,     // cuántos juegos llegan ahora
     * ]
     */
    public array $items = [];

    public ?int $ultima_recepcion_id = null;

    // ────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->fecha_recepcion = now()->toDateString();
    }

    public function render()
    {
        return view('livewire.recepciones-registrar-page', [
            // Solo órdenes pendientes o parcialmente recibidas
            'ordenes'    => OrdenCompra::query()
                ->with('proveedor')
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->orderByDesc('fecha_orden')
                ->get(),
            'historial'  => Recepcion::query()
                ->with(['ordenCompra.proveedor', 'detalles.producto'])
                ->orderByDesc('fecha_recepcion')
                ->orderByDesc('id')
                ->limit(30)
                ->get(),
        ]);
    }

    // ── Cargar detalle de la orden seleccionada ──────────────────────────────

    public function updatedOrdenCompraId(): void
    {
        $this->loadOrden();
    }

    public function loadOrden(): void
    {
        $this->ultima_recepcion_id = null;
        $this->items               = [];

        if (! $this->orden_compra_id) {
            return;
        }

        $orden = OrdenCompra::query()
            ->with(['detalles.producto'])
            ->find($this->orden_compra_id);

        if (! $orden) {
            return;
        }

        $this->items = $orden->detalles->map(function ($d) {
            // La cantidad guardada en orden_compra_detalles es en UNIDADES
            // Recalculamos a juegos usando piezas_por_juego del producto
            $piezasPorJuego    = (int) ($d->producto?->unidades_por_empaque ?? 0);
            $cantidadUnidades  = (int) $d->cantidad;

            // Si tenemos piezas_por_juego, convertimos. Si no, asumimos 1 juego = 1 unidad
            $cantidadJuegosOrdenada = $piezasPorJuego > 0
                ? (int) ceil($cantidadUnidades / $piezasPorJuego)
                : $cantidadUnidades;

            return [
                'producto_id'               => (int) $d->producto_id,
                'codigo'                    => (string) $d->producto?->codigo,
                'nombre'                    => (string) $d->producto?->nombre,
                'piezas_por_juego'          => $piezasPorJuego ?: null,
                'cantidad_ordenada_juegos'  => $cantidadJuegosOrdenada,
                'juegos_recibidos'          => '',
            ];
        })->values()->all();
    }

    // ── Registrar la recepción ────────────────────────────────────────────────

    public function registrar(): void
    {
        if (! $this->orden_compra_id) {
            Session::flash('error', 'Selecciona una orden de cotización.');
            return;
        }

        // Construir ítems en UNIDADES para el service (que espera unidades)
        $itemsParaService = [];

        foreach ($this->items as $item) {
            $juegosRecibidos = (int) ($item['juegos_recibidos'] ?? 0);
            if ($juegosRecibidos <= 0) {
                continue;
            }

            // stock_actual = juegos (bolsas cerradas)
            // → pasamos juegos directamente, NO convertimos a unidades
            $itemsParaService[] = [
                'producto_id'       => (int) $item['producto_id'],
                'cantidad_recibida' => $juegosRecibidos,
            ];
        }

        if ($itemsParaService === []) {
            Session::flash('error', 'Ingresa la cantidad de juegos recibidos en al menos un producto.');
            return;
        }

        try {
            $service    = app(RegistrarRecepcionService::class);
            $recepcion  = $service->registrar(
                (int) $this->orden_compra_id,
                $itemsParaService,
                $this->observaciones,
                Carbon::parse($this->fecha_recepcion),
            );

            // Marcar la orden como recibida/parcial
            $this->actualizarEstadoOrden((int) $this->orden_compra_id);

            $this->ultima_recepcion_id = $recepcion->id;
            Session::flash('status', "Recepción #{$recepcion->id} registrada. Stock de juegos actualizado.");

            // Reset formulario
            $this->reset(['orden_compra_id', 'observaciones', 'items']);
            $this->fecha_recepcion = now()->toDateString();

        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage() ?: 'Error al registrar la recepción.');
            report($e);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function actualizarEstadoOrden(int $ordenId): void
    {
        $orden = OrdenCompra::query()->find($ordenId);
        if (! $orden) {
            return;
        }

        // Contamos lo recibido vs lo ordenado por producto
        $totalOrdenado = \App\Models\OrdenCompraDetalle::query()
            ->where('orden_compra_id', $ordenId)
            ->selectRaw('SUM(cantidad) as total')
            ->value('total') ?? 0;

        $totalRecibido = \DB::table('recepcion_detalles')
            ->join('recepciones', 'recepciones.id', '=', 'recepcion_detalles.recepcion_id')
            ->where('recepciones.orden_compra_id', $ordenId)
            ->sum('recepcion_detalles.cantidad_recibida');

        if ($totalRecibido >= $totalOrdenado) {
            $orden->estado = 'recibida';
        } else {
            $orden->estado = 'parcial';
        }

        $orden->save();
    }
}
