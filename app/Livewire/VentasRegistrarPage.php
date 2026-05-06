<?php

namespace App\Livewire;

use App\Models\Caja;
use App\Models\Producto;
use App\Services\Ventas\Exceptions\CajaNoAbiertaException;
use App\Services\Ventas\Exceptions\StockInsuficienteException;
use App\Services\Ventas\RegistrarVentaService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Registrar venta')]
class VentasRegistrarPage extends Component
{
    public string $tipo_pago = 'efectivo';
    public string|int|float $descuento = '0.00';
    public ?string $observaciones = null;

    /** @var array<int, array{producto_id:int|string, cantidad:int|string, precio_unitario?:string|int|float}> */
    public array $items = [];

    public ?string $ultima_venta_numero = null;
    public ?string $ultima_venta_total = null;

    public function mount(): void
    {
        $this->items = [
            ['producto_id' => '', 'cantidad' => 1],
        ];
    }

    public function render()
    {
        return view('livewire.ventas-registrar-page', [
            'cajaAbierta' => Caja::query()->where('estado', 'abierta')->orderByDesc('fecha_apertura')->first(),
            'productos' => Producto::query()->where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function addItem(): void
    {
        $this->items[] = ['producto_id' => '', 'cantidad' => 1];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->addItem();
        }
    }

    public function registrar(RegistrarVentaService $service): void
    {
        $caja = Caja::query()->where('estado', 'abierta')->orderByDesc('fecha_apertura')->first();
        if (! $caja) {
            session()->flash('error', 'No hay caja abierta.');
            return;
        }

        $data = $this->validate([
            'tipo_pago' => ['required', 'string', Rule::in(['efectivo', 'qr', 'transferencia'])],
            'descuento' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $venta = $service->registrar(
                (int) $caja->id,
                $data['items'],
                $data['tipo_pago'],
                $data['descuento'],
                $data['observaciones'] ?? null,
            );

            $this->ultima_venta_numero = $venta->numero_venta;
            $this->ultima_venta_total = (string) $venta->total;

            session()->flash('status', 'Venta registrada.');

            $this->descuento = '0.00';
            $this->observaciones = null;
            $this->items = [['producto_id' => '', 'cantidad' => 1]];
        } catch (CajaNoAbiertaException $e) {
            session()->flash('error', $e->getMessage());
        } catch (StockInsuficienteException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Error registrando venta.');
            report($e);
        }
    }
}
