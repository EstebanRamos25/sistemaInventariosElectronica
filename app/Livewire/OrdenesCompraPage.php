<?php

namespace App\Livewire;

use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Órdenes de Cotización')]
class OrdenesCompraPage extends Component
{
    // ── Cabecera de orden ────────────────────────────────────────────────────

    public int|string $proveedor_id          = '';
    public string     $numero_orden          = '';
    public string     $fecha_orden;
    public ?string    $fecha_estimada_llegada = null;
    public ?string    $observaciones          = null;

    // ── Ítems del detalle ────────────────────────────────────────────────────

    /**
     * Cada ítem:
     * [
     *   'producto_id'      => int|null,
     *   'producto_label'   => string,   // "[CÓDIGO] — Nombre"
     *   'piezas_por_juego' => string,   // nro de barras por paquete (editable)
     *   'cantidad_juegos'  => string,   // cuántos juegos/paquetes se piden
     *   'precio_por_juego' => string,   // precio USD por paquete
     *   'search_term'      => string,   // texto del buscador
     *   'search_open'      => bool,     // dropdown visible
     * ]
     */
    public array $items         = [];

    /** Resultados del buscador, indexados por posición de ítem. */
    public array $searchResults = [];

    // ────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->fecha_orden  = now()->toDateString();
        $this->numero_orden = $this->generarNumeroOrden();
        $this->items        = [$this->blankItem()];
    }

    public function render()
    {
        return view('livewire.ordenes-compra-page', [
            'proveedores' => Proveedor::query()->orderBy('nombre')->get(),
            'ordenes'     => OrdenCompra::query()
                ->with(['proveedor', 'detalles.producto'])
                ->withCount('detalles')
                ->orderByDesc('fecha_orden')
                ->limit(25)
                ->get(),
        ]);
    }

    // ── Buscador de productos ────────────────────────────────────────────────

    /**
     * Livewire 3 lifecycle: se dispara al cambiar cualquier campo de $items.
     * $name tendrá forma "0.search_term", "1.precio_por_juego", etc.
     */
    public function updatedItems(mixed $value, string $name): void
    {
        [$index, $field] = array_pad(explode('.', $name, 2), 2, '');

        if ($field === 'search_term') {
            $this->doSearch((int) $index, (string) $value);
        }
    }

    private function doSearch(int $index, string $term): void
    {
        $term = trim($term);

        if (strlen($term) < 1) {
            $this->searchResults[$index]    = [];
            $this->items[$index]['search_open'] = false;
            return;
        }

        $resultados = Producto::query()
            ->where('activo', true)
            ->where(function ($q) use ($term) {
                $q->where('codigo', 'like', "%{$term}%")
                  ->orWhere('nombre', 'like', "%{$term}%");
            })
            ->orderBy('codigo')
            ->limit(10)
            ->get(['id', 'codigo', 'nombre', 'unidades_por_empaque'])
            ->map(fn ($p) => [
                'id'               => $p->id,
                'label'            => $p->codigo . ' — ' . $p->nombre,
                'piezas_por_juego' => $p->unidades_por_empaque,
            ])
            ->all();

        $this->searchResults[$index]        = $resultados;
        $this->items[$index]['search_open'] = count($resultados) > 0;
    }

    public function selectProducto(int $index, int $productoId): void
    {
        $producto = Producto::query()->find($productoId, ['id', 'codigo', 'nombre', 'unidades_por_empaque']);
        if (! $producto) {
            return;
        }

        $this->items[$index]['producto_id']      = $producto->id;
        $this->items[$index]['producto_label']   = $producto->codigo . ' — ' . $producto->nombre;
        $this->items[$index]['piezas_por_juego'] = (string) ($producto->unidades_por_empaque ?? '');
        $this->items[$index]['search_term']      = '';
        $this->items[$index]['search_open']      = false;
        $this->searchResults[$index]             = [];
    }

    public function clearProducto(int $index): void
    {
        $this->items[$index]['producto_id']      = null;
        $this->items[$index]['producto_label']   = '';
        $this->items[$index]['piezas_por_juego'] = '';
        $this->items[$index]['search_term']      = '';
        $this->items[$index]['search_open']      = false;
        $this->searchResults[$index]             = [];
    }

    public function closeDropdown(int $index): void
    {
        $this->items[$index]['search_open'] = false;
    }

    // ── Gestión de ítems ────────────────────────────────────────────────────

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index], $this->searchResults[$index]);
        $this->items         = array_values($this->items);
        $this->searchResults = array_values($this->searchResults);

        if ($this->items === []) {
            $this->addItem();
        }
    }

    // ── Total en tiempo real ─────────────────────────────────────────────────

    #[Computed]
    public function totalOrden(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $cant   = (float) ($item['cantidad_juegos']  ?? 0);
            $precio = (float) ($item['precio_por_juego'] ?? 0);
            $total += $cant * $precio;
        }
        return $total;
    }

    // ── Guardar orden ────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->numero_orden = trim($this->numero_orden);
        if ($this->numero_orden === '') {
            $this->numero_orden = $this->generarNumeroOrden();
        }

        $this->validate([
            'proveedor_id'          => ['required', 'integer', Rule::exists('proveedores', 'id')],
            'numero_orden'          => ['required', 'string', 'max:255', Rule::unique('ordenes_compra', 'numero_orden')],
            'fecha_orden'           => ['required', 'date'],
            'fecha_estimada_llegada'=> ['nullable', 'date'],
            'observaciones'         => ['nullable', 'string'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.producto_id'   => ['required', 'integer', Rule::exists('productos', 'id')],
            'items.*.cantidad_juegos'   => ['required', 'numeric', 'min:1'],
            'items.*.precio_por_juego'  => ['required', 'numeric', 'min:0'],
        ]);

        // Construir ítems normalizados
        $itemsNorm = [];
        $total     = 0.0;

        foreach ($this->items as $item) {
            $productoId    = (int)   $item['producto_id'];
            $cantJuegos    = (float) $item['cantidad_juegos'];
            $precioPorJuego= (float) $item['precio_por_juego'];
            $piezasPorJuego= is_numeric($item['piezas_por_juego'] ?? '') && (int) $item['piezas_por_juego'] > 0
                                ? (int) $item['piezas_por_juego']
                                : 1;

            // Almacenamos unidades totales en la BD para que Recepciones funcione
            $cantidadUnidades = (int) round($cantJuegos * $piezasPorJuego);
            $precioUnitario   = $piezasPorJuego > 0
                                    ? $precioPorJuego / $piezasPorJuego
                                    : $precioPorJuego;
            $subtotal         = $cantJuegos * $precioPorJuego;
            $total           += $subtotal;

            $itemsNorm[] = [
                'producto_id'    => $productoId,
                'cantidad'       => $cantidadUnidades,
                'precio_unitario'=> round($precioUnitario, 4),
                'subtotal'       => round($subtotal, 2),
            ];
        }

        DB::transaction(function () use ($itemsNorm, $total) {
            $orden = OrdenCompra::query()->create([
                'proveedor_id'           => (int) $this->proveedor_id,
                'numero_orden'           => $this->numero_orden,
                'fecha_orden'            => $this->fecha_orden,
                'fecha_estimada_llegada' => $this->fecha_estimada_llegada,
                'estado'                 => 'pendiente',
                'total'                  => round($total, 2),
                'observaciones'          => $this->observaciones,
            ]);

            foreach ($itemsNorm as $row) {
                $orden->detalles()->create([
                    'producto_id'    => $row['producto_id'],
                    'cantidad'       => $row['cantidad'],
                    'precio_unitario'=> number_format($row['precio_unitario'], 4, '.', ''),
                    'subtotal'       => number_format($row['subtotal'], 2, '.', ''),
                ]);
            }
        });

        Session::flash('status', "Orden {$this->numero_orden} creada correctamente.");

        $this->reset(['proveedor_id', 'fecha_estimada_llegada', 'observaciones']);
        $this->fecha_orden   = now()->toDateString();
        $this->numero_orden  = $this->generarNumeroOrden();
        $this->items         = [$this->blankItem()];
        $this->searchResults = [];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function blankItem(): array
    {
        return [
            'producto_id'      => null,
            'producto_label'   => '',
            'piezas_por_juego' => '',
            'cantidad_juegos'  => '1',
            'precio_por_juego' => '0.00',
            'search_term'      => '',
            'search_open'      => false,
        ];
    }

    private function generarNumeroOrden(): string
    {
        $fecha  = now()->format('Ymd');
        $prefix = "COT-{$fecha}-";

        $ultimo = OrdenCompra::query()
            ->where('numero_orden', 'like', "{$prefix}%")
            ->orderByDesc('numero_orden')
            ->value('numero_orden');

        $siguiente = 1;
        if ($ultimo) {
            $sufijo    = substr($ultimo, strlen($prefix));
            $siguiente = ((int) $sufijo) + 1;
        }

        return $prefix . str_pad((string) $siguiente, 3, '0', STR_PAD_LEFT);
    }
}
