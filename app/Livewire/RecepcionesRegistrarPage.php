<?php

namespace App\Livewire;

use App\Models\OrdenCompra;
use App\Services\Compras\RegistrarRecepcionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Registrar recepción')]
class RecepcionesRegistrarPage extends Component
{
    public int|string $orden_compra_id = '';
    public ?string $observaciones = null;

    /** @var array<int, array{producto_id:int, codigo:string, nombre:string, cantidad_ordenada:int, cantidad_recibida:int}> */
    public array $items = [];

    public ?int $ultima_recepcion_id = null;

    public function render()
    {
        return view('livewire.recepciones-registrar-page', [
            'ordenes' => OrdenCompra::query()->orderByDesc('fecha_orden')->limit(50)->get(),
        ]);
    }

    public function updatedOrdenCompraId(): void
    {
        $this->ultima_recepcion_id = null;
        $this->items = [];

        if (! $this->orden_compra_id) {
            return;
        }

        $orden = OrdenCompra::query()
            ->with(['detalles.producto'])
            ->find($this->orden_compra_id);

        if (! $orden) {
            return;
        }

        $this->items = $orden->detalles
            ->map(function ($d) {
                return [
                    'producto_id' => (int) $d->producto_id,
                    'codigo' => (string) $d->producto?->codigo,
                    'nombre' => (string) $d->producto?->nombre,
                    'cantidad_ordenada' => (int) $d->cantidad,
                    'cantidad_recibida' => 0,
                ];
            })
            ->values()
            ->all();
    }

    public function registrar(RegistrarRecepcionService $service): void
    {
        if (! $this->orden_compra_id) {
            session()->flash('error', 'Selecciona una orden.');
            return;
        }

        $items = collect($this->items)
            ->filter(fn ($i) => (int) ($i['cantidad_recibida'] ?? 0) > 0)
            ->map(fn ($i) => [
                'producto_id' => (int) $i['producto_id'],
                'cantidad_recibida' => (int) $i['cantidad_recibida'],
            ])
            ->values()
            ->all();

        if ($items === []) {
            session()->flash('error', 'Ingresa al menos una cantidad recibida.');
            return;
        }

        try {
            $recepcion = $service->registrar(
                (int) $this->orden_compra_id,
                $items,
                $this->observaciones,
            );

            $this->ultima_recepcion_id = $recepcion->id;
            session()->flash('status', 'Recepción registrada.');

            $this->observaciones = null;
            $this->updatedOrdenCompraId();
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage() ?: 'Error registrando recepción.');
            report($e);
        }
    }
}
