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
    public string $tipo_pago   = 'efectivo';
    public string $descuento   = '0.00';   // siempre string para compatibilidad Livewire
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
            'producto_id'      => '',
            'cantidad'         => 1,
            'tipo_venta'       => 'juego',   // 'juego' | 'barra'
            'precio_unitario'  => '0.00',
            'precio_juego'     => '0.00',    // precio de referencia juego
            'precio_barra'     => '0.00',    // precio de referencia barra
            'stock_juegos'     => 0,         // para mostrar disponibilidad
            'stock_barras'     => 0,         // para mostrar disponibilidad
            'barras_por_juego' => 0,
            'search'           => '',
            'open'             => false,
            'nombre_display'   => '',
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
            ->get(['id', 'codigo', 'nombre', 'stock_actual', 'stock_barras_sueltas', 'precio_venta', 'precio_venta_barra', 'unidades_por_empaque'])
            ->toArray();
    }

    /**
     * Cuando el usuario escribe en el campo descuento, normalizar el valor.
     * Si queda vacío, lo volvemos a '0.00' para que los computed no fallen.
     */
    public function updatedDescuento(mixed $value): void
    {
        if ($value === '' || $value === null) {
            $this->descuento = '';
        }
    }

    /**
     * Cuando el usuario escribe en el buscador de un ítem o cambia el tipo de venta,
     * ajustar la selección/precio automáticamente.
     */
    public function updatedItems(mixed $value, string $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, '');
        $index = (int) $index;

        if ($field === 'search') {
            if ($this->items[$index]['producto_id'] !== '') {
                $this->items[$index]['producto_id']     = '';
                $this->items[$index]['precio_unitario'] = '0.00';
                $this->items[$index]['precio_juego']    = '0.00';
                $this->items[$index]['precio_barra']    = '0.00';
                $this->items[$index]['stock_juegos']    = 0;
                $this->items[$index]['stock_barras']    = 0;
                $this->items[$index]['nombre_display']  = '';
            }
            $this->items[$index]['open'] = trim((string) $value) !== '';
        }

        if ($field === 'tipo_venta' && $this->items[$index]['producto_id'] !== '') {
            // Auto-cambiar el precio al cambiar el tipo
            $this->items[$index]['precio_unitario'] = $value === 'barra'
                ? $this->items[$index]['precio_barra']
                : $this->items[$index]['precio_juego'];
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
            ->first(['id', 'codigo', 'nombre', 'stock_actual', 'stock_barras_sueltas',
                     'precio_venta', 'precio_venta_barra', 'unidades_por_empaque']);

        if (! $producto) {
            return;
        }

        $tipoVenta     = $this->items[$index]['tipo_venta'] ?? 'juego';
        $precioJuego   = (float) $producto->precio_venta;
        $precioBarra   = (float) $producto->precio_venta_barra > 0
            ? (float) $producto->precio_venta_barra
            : round($precioJuego / max(1, (int) $producto->unidades_por_empaque), 2);

        $this->items[$index]['producto_id']      = $producto->id;
        $this->items[$index]['precio_juego']     = number_format($precioJuego, 2, '.', '');
        $this->items[$index]['precio_barra']     = number_format($precioBarra, 2, '.', '');
        $this->items[$index]['precio_unitario']  = $tipoVenta === 'barra'
            ? number_format($precioBarra, 2, '.', '')
            : number_format($precioJuego, 2, '.', '');
        $this->items[$index]['stock_juegos']     = (int) $producto->stock_actual;
        $this->items[$index]['stock_barras']     = (int) $producto->stock_barras_sueltas;
        $this->items[$index]['barras_por_juego'] = (int) $producto->unidades_por_empaque;
        $this->items[$index]['nombre_display']   = "{$producto->codigo} — {$producto->nombre}";
        $this->items[$index]['search']           = "{$producto->codigo} — {$producto->nombre}";
        $this->items[$index]['open']             = false;
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

        // Normalizar descuento: si quedó vacío, usar 0
        if ($this->descuento === '' || $this->descuento === null) {
            $this->descuento = '0.00';
        }

        $data = $this->validate([
            'tipo_pago'                => ['required', 'string', Rule::in(['efectivo', 'qr', 'transferencia'])],
            'descuento'                => ['required', 'numeric', 'min:0'],
            'observaciones'            => ['nullable', 'string'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.producto_id'      => ['required', 'integer', Rule::exists('productos', 'id')],
            'items.*.tipo_venta'       => ['required', 'string', Rule::in(['juego', 'barra'])],
            'items.*.cantidad'         => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario'  => ['required', 'numeric', 'min:0'],
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
