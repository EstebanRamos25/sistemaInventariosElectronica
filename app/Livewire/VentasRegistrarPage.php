<?php

namespace App\Livewire;

use App\Models\Caja;
use App\Models\Producto;
use App\Services\Ventas\Exceptions\CajaNoAbiertaException;
use App\Services\Ventas\Exceptions\StockInsuficienteException;
use App\Services\Ventas\RegistrarVentaService;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use InvalidArgumentException;

#[Layout('layouts.app')]
#[Title('Registrar venta')]
class VentasRegistrarPage extends Component
{
    public string $tipo_pago = 'efectivo';
    public string|int|float $descuento = '0.00';
    public ?string $observaciones = null;

    /**
     * Cada ítem tiene:
     *   producto_id   : int|string
     *   cantidad      : int|string
     *   precio_unitario: float  (se autocompleta al seleccionar producto)
     *   search        : string  (término de búsqueda del combobox)
     *   open          : bool    (si el dropdown está abierto)
     *   nombre_display: string  (texto que muestra el input una vez seleccionado)
     *
     * @var array<int, array{
     *   producto_id: int|string,
     *   cantidad: int|string,
     *   precio_unitario: string|int|float,
     *   search: string,
     *   open: bool,
     *   nombre_display: string
     * }>
     */
    public array $items = [];

    public ?string $ultima_venta_numero = null;
    public ?string $ultima_venta_total  = null;

    public function mount(): void
    {
        $this->items = [
            $this->blankItem(),
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function blankItem(): array
    {
        return [
            'producto_id'     => '',
            'cantidad'        => 1,
            'precio_unitario' => '0.00',
            'search'          => '',
            'open'            => false,
            'nombre_display'  => '',
        ];
    }

    // ── Búsqueda de productos por ítem ─────────────────────────────────────

    /**
     * Devuelve los productos que coinciden con el término de búsqueda del ítem $index.
     * Máximo 10 resultados para no sobrecargar el dropdown.
     */
    public function buscarProductos(int $index): array
    {
        $term = trim($this->items[$index]['search'] ?? '');

        if ($term === '') {
            return [];
        }

        return Producto::query()
            ->where('activo', true)
            ->where(function ($q) use ($term) {
                $q->where('codigo', 'like', "%{$term}%")
                    ->orWhere('nombre', 'like', "%{$term}%")
                    ->orWhere('modelo_tv', 'like', "%{$term}%");
            })
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'codigo', 'nombre', 'stock_actual', 'precio_venta'])
            ->toArray();
    }

    /**
     * Se llama cuando el usuario escribe en el buscador de un ítem.
     * Abre el dropdown y borra la selección previa si cambia el texto.
     */
    public function updatedItems(mixed $value, string $key): void
    {
        // $key tiene formato "0.search", "1.cantidad", etc.
        [$index, $field] = array_pad(explode('.', $key, 2), 2, '');
        $index = (int) $index;

        if ($field === 'search') {
            // Si el usuario escribe de nuevo, limpiar selección previa
            if ($this->items[$index]['producto_id'] !== '') {
                $this->items[$index]['producto_id']      = '';
                $this->items[$index]['precio_unitario']  = '0.00';
                $this->items[$index]['nombre_display']   = '';
            }
            $this->items[$index]['open'] = trim((string) $value) !== '';
        }
    }

    /**
     * Selecciona un producto del dropdown para el ítem $index.
     */
    public function seleccionarProducto(int $index, int $productoId): void
    {
        $producto = Producto::query()
            ->where('id', $productoId)
            ->where('activo', true)
            ->first(['id', 'codigo', 'nombre', 'stock_actual', 'precio_venta']);

        if (! $producto) {
            return;
        }

        $this->items[$index]['producto_id']     = $producto->id;
        $this->items[$index]['precio_unitario'] = (float) $producto->precio_venta;
        $this->items[$index]['nombre_display']  = "{$producto->codigo} — {$producto->nombre}";
        $this->items[$index]['search']          = "{$producto->codigo} — {$producto->nombre}";
        $this->items[$index]['open']            = false;
    }

    /**
     * Cierra el dropdown de un ítem (cuando se hace clic fuera).
     */
    public function cerrarDropdown(int $index): void
    {
        $this->items[$index]['open'] = false;
    }

    // ── Gestión de ítems ───────────────────────────────────────────────────

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->addItem();
        }
    }

    // ── Computed totales ───────────────────────────────────────────────────

    /**
     * Subtotal = suma de (precio_unitario × cantidad) por ítem con producto seleccionado.
     */
    #[Computed]
    public function subtotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            if ($item['producto_id'] === '' || $item['producto_id'] === null) {
                continue;
            }
            $precio   = (float) ($item['precio_unitario'] ?? 0);
            $cantidad = max(0, (int) ($item['cantidad'] ?? 0));
            $total   += $precio * $cantidad;
        }
        return $total;
    }

    /**
     * Total final = subtotal − descuento.
     */
    #[Computed]
    public function totalFinal(): float
    {
        return max(0.0, $this->subtotal - (float) $this->descuento);
    }

    // ── Render ─────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.ventas-registrar-page', [
            'cajaAbierta' => Caja::query()
                ->where('estado', 'abierta')
                ->orderByDesc('fecha_apertura')
                ->first(),
        ]);
    }

    // ── Registrar venta ────────────────────────────────────────────────────

    public function registrar(RegistrarVentaService $service): void
    {
        $caja = Caja::query()->where('estado', 'abierta')->orderByDesc('fecha_apertura')->first();
        if (! $caja) {
            Session::flash('error', 'No hay caja abierta.');
            return;
        }

        $data = $this->validate([
            'tipo_pago'             => ['required', 'string', Rule::in(['efectivo', 'qr', 'transferencia'])],
            'descuento'             => ['required', 'numeric', 'min:0'],
            'observaciones'         => ['nullable', 'string'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.producto_id'   => ['required', 'integer', Rule::exists('productos', 'id')],
            'items.*.cantidad'      => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
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
            $this->ultima_venta_total  = (string) $venta->total;

            Session::flash('status', "Venta {$venta->numero_venta} registrada. Total: \${$venta->total}");

            $this->descuento    = '0.00';
            $this->observaciones = null;
            $this->items        = [$this->blankItem()];
        } catch (CajaNoAbiertaException $e) {
            Session::flash('error', $e->getMessage());
        } catch (StockInsuficienteException $e) {
            Session::flash('error', $e->getMessage());
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            Session::flash('error', 'Error registrando venta.');
            report($e);
        }
    }
}
