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

    /** @var array<int, array{producto_id:int|string, tipo_cantidad:'unidad'|'empaque', cantidad:int|string, precio_unitario:string|int|float}> */
    public array $items = [];

    public function mount(): void
    {
        $this->fecha_orden = now()->toDateString();
        $this->items = [
            ['producto_id' => '', 'tipo_cantidad' => 'unidad', 'cantidad' => 1, 'precio_unitario' => '0.00'],
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
        $this->items[] = ['producto_id' => '', 'tipo_cantidad' => 'unidad', 'cantidad' => 1, 'precio_unitario' => '0.00'];
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
            'items.*.tipo_cantidad' => ['required', 'string', Rule::in(['unidad', 'empaque'])],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        $productoIds = collect($this->items)
            ->pluck('producto_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $productosPorId = Producto::query()
            ->whereIn('id', $productoIds)
            ->get(['id', 'unidades_por_empaque'])
            ->keyBy('id');

        $itemsNormalizados = [];
        $total = 0.0;

        foreach ($this->items as $item) {
            $productoId = (int) $item['producto_id'];
            $tipoCantidad = (string) ($item['tipo_cantidad'] ?? 'unidad');

            $cantidadIngresada = (int) $item['cantidad'];
            $precioIngresado = (float) $item['precio_unitario'];

            $cantidadUnidades = $cantidadIngresada;
            $precioUnitario = $precioIngresado;

            if ($tipoCantidad === 'empaque') {
                /** @var \App\Models\Producto|null $producto */
                $producto = $productosPorId[$productoId] ?? null;
                $unidadesPorEmpaque = (int) ($producto?->unidades_por_empaque ?? 0);
                if ($unidadesPorEmpaque <= 0) {
                    throw new \InvalidArgumentException('Para comprar por empaque, el producto debe tener “Unidades por empaque”.');
                }

                $cantidadUnidades = $cantidadIngresada * $unidadesPorEmpaque;
                $precioUnitario = $precioIngresado / $unidadesPorEmpaque;
            }

            $subtotal = $cantidadUnidades * $precioUnitario;
            $total += $subtotal;

            $itemsNormalizados[] = [
                'producto_id' => $productoId,
                'cantidad' => $cantidadUnidades,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal,
            ];
        }

        DB::transaction(function () use ($itemsNormalizados, $total) {
            $orden = OrdenCompra::query()->create([
                'proveedor_id' => (int) $this->proveedor_id,
                'numero_orden' => $this->numero_orden,
                'fecha_orden' => $this->fecha_orden,
                'fecha_estimada_llegada' => $this->fecha_estimada_llegada,
                'estado' => 'pendiente',
                'total' => 0,
                'observaciones' => $this->observaciones,
            ]);

            foreach ($itemsNormalizados as $row) {
                $orden->detalles()->create([
                    'producto_id' => (int) $row['producto_id'],
                    'cantidad' => (int) $row['cantidad'],
                    'precio_unitario' => number_format((float) $row['precio_unitario'], 2, '.', ''),
                    'subtotal' => number_format((float) $row['subtotal'], 2, '.', ''),
                ]);
            }

            $orden->total = number_format($total, 2, '.', '');
            $orden->save();
        });

        session()->flash('status', 'Orden de compra creada.');

        $this->reset(['proveedor_id', 'numero_orden', 'fecha_estimada_llegada', 'observaciones']);
        $this->fecha_orden = now()->toDateString();
        $this->items = [['producto_id' => '', 'tipo_cantidad' => 'unidad', 'cantidad' => 1, 'precio_unitario' => '0.00']];
    }
}
