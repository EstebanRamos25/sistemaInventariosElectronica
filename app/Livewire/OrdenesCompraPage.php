<?php

namespace App\Livewire;

use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Compras / Órdenes')]
class OrdenesCompraPage extends Component
{
    public int|string $proveedor_id = '';
    public string $numero_orden = '';
    public string $fecha_orden;
    public ?string $fecha_estimada_llegada = null;
    public ?string $observaciones = null;

    /** @var array<int, array{producto_id:int|string, cantidad:int|string, precio_unitario:string|int|float}> */
    public array $items = [];

    public function mount(): void
    {
        $this->fecha_orden = now()->toDateString();
        $this->items = [
            ['producto_id' => '', 'cantidad' => 1, 'precio_unitario' => '0.00'],
        ];
    }

    public function render()
    {
        return view('livewire.ordenes-compra-page', [
            'proveedores' => Proveedor::query()->orderBy('nombre')->get(),
            'productos' => Producto::query()->where('activo', true)->orderBy('nombre')->get(),
            'ordenes' => OrdenCompra::query()->with('proveedor')->orderByDesc('fecha_orden')->limit(20)->get(),
        ]);
    }

    public function addItem(): void
    {
        $this->items[] = ['producto_id' => '', 'cantidad' => 1, 'precio_unitario' => '0.00'];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->addItem();
        }
    }

    public function save(): void
    {
        $this->validate([
            'proveedor_id' => ['required', 'integer', Rule::exists('proveedores', 'id')],
            'numero_orden' => ['required', 'string', 'max:255', Rule::unique('ordenes_compra', 'numero_orden')],
            'fecha_orden' => ['required', 'date'],
            'fecha_estimada_llegada' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () {
            $orden = OrdenCompra::query()->create([
                'proveedor_id' => (int) $this->proveedor_id,
                'numero_orden' => $this->numero_orden,
                'fecha_orden' => $this->fecha_orden,
                'fecha_estimada_llegada' => $this->fecha_estimada_llegada,
                'estado' => 'pendiente',
                'total' => 0,
                'observaciones' => $this->observaciones,
            ]);

            $total = 0.0;
            foreach ($this->items as $item) {
                $cantidad = (int) $item['cantidad'];
                $precio = (float) $item['precio_unitario'];
                $subtotal = $cantidad * $precio;
                $total += $subtotal;

                $orden->detalles()->create([
                    'producto_id' => (int) $item['producto_id'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => number_format($precio, 2, '.', ''),
                    'subtotal' => number_format($subtotal, 2, '.', ''),
                ]);
            }

            $orden->total = number_format($total, 2, '.', '');
            $orden->save();
        });

        session()->flash('status', 'Orden de compra creada.');

        $this->reset(['proveedor_id', 'numero_orden', 'fecha_estimada_llegada', 'observaciones']);
        $this->fecha_orden = now()->toDateString();
        $this->items = [['producto_id' => '', 'cantidad' => 1, 'precio_unitario' => '0.00']];
    }
}
