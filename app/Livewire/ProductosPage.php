<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Productos')]
class ProductosPage extends Component
{
    public ?int $editingId = null;
    public bool $showForm = false;

    public int|string $categoria_id = '';
    public string $codigo = '';
    public string $nombre = '';
    public ?string $descripcion = null;
    public ?string $marca = null;
    public ?string $modelo_tv = null;
    public int|string|null $pulgadas_tv = null;
    public string|int|float|null $voltaje_led = null;
    public int|string|null $leds_por_barra = null;
    public ?string $caracteristicas_barra = null;

    public string $unidad = 'pieza';
    public ?string $empaque = null;
    public int|string|null $unidades_por_empaque = null;

    public string|int|float $precio_compra = '0.00';
    public string|int|float $precio_venta = '0.00';

    public int|string $stock_actual = 0;
    public int|string $stock_minimo = 0;
    public int|string $stock_ideal = 0;

    public int|string|null $tiempo_reposicion_dias = null;
    public ?string $ubicacion = null;

    public bool $activo = true;

    public function render()
    {
        return view('livewire.productos-page', [
            'categorias' => Categoria::query()->orderBy('nombre')->get(),
            'productos' => Producto::query()->with('categoria')->orderBy('nombre')->get(),
        ]);
    }

    public function edit(int $id): void
    {
        $p = Producto::query()->findOrFail($id);

        $this->editingId = $p->id;
        $this->categoria_id = $p->categoria_id;
        $this->codigo = $p->codigo;
        $this->nombre = $p->nombre;
        $this->descripcion = $p->descripcion;
        $this->marca = $p->marca;
        $this->modelo_tv = $p->modelo_tv;
        $this->pulgadas_tv = $p->pulgadas_tv;
        $this->voltaje_led = $p->voltaje_led === null ? null : (string) $p->voltaje_led;
        $this->leds_por_barra = $p->leds_por_barra;
        $this->caracteristicas_barra = $p->caracteristicas_barra;
        $this->unidad = $p->unidad ?? 'pieza';
        $this->empaque = $p->empaque;
        $this->unidades_por_empaque = $p->unidades_por_empaque;
        $this->precio_compra = (string) $p->precio_compra;
        $this->precio_venta = (string) $p->precio_venta;
        $this->stock_actual = $p->stock_actual;
        $this->stock_minimo = $p->stock_minimo;
        $this->stock_ideal = $p->stock_ideal;
        $this->tiempo_reposicion_dias = $p->tiempo_reposicion_dias;
        $this->ubicacion = $p->ubicacion;
        $this->activo = (bool) $p->activo;

        $this->showForm = true;
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $this->codigo = trim($this->codigo);
        if ($this->codigo === '') {
            $this->codigo = $this->sugerirCodigo();
        }

        $data = $this->validate([
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')],
            'codigo' => ['required', 'string', 'max:255', Rule::unique('productos', 'codigo')->ignore($this->editingId)],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo_tv' => ['nullable', 'string', 'max:255'],
            'pulgadas_tv' => ['nullable', 'integer', 'min:1', 'max:200'],
            'voltaje_led' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'leds_por_barra' => ['nullable', 'integer', 'min:1', 'max:500'],
            'caracteristicas_barra' => ['nullable', 'string', 'max:255'],
            'unidad' => ['required', 'string', 'max:20'],
            'empaque' => ['nullable', 'string', 'max:30'],
            'unidades_por_empaque' => ['nullable', 'integer', 'min:1'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'stock_actual' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'stock_ideal' => ['required', 'integer', 'min:0'],
            'tiempo_reposicion_dias' => ['nullable', 'integer', 'min:0'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ]);

        Producto::query()->updateOrCreate(
            ['id' => $this->editingId],
            $data,
        );

        session()->flash('status', $this->editingId ? 'Producto actualizado.' : 'Producto creado.');

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $p = Producto::query()->findOrFail($id);
        $p->delete();

        session()->flash('status', 'Producto eliminado.');

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->showForm = false;

        $this->categoria_id = '';
        $this->codigo = '';
        $this->nombre = '';
        $this->descripcion = null;
        $this->marca = null;
        $this->modelo_tv = null;
        $this->pulgadas_tv = null;
        $this->voltaje_led = null;
        $this->leds_por_barra = null;
        $this->caracteristicas_barra = null;
        $this->unidad = 'pieza';
        $this->empaque = null;
        $this->unidades_por_empaque = null;
        $this->precio_compra = '0.00';
        $this->precio_venta = '0.00';
        $this->stock_actual = 0;
        $this->stock_minimo = 0;
        $this->stock_ideal = 0;
        $this->tiempo_reposicion_dias = null;
        $this->ubicacion = null;
        $this->activo = true;
    }

    private function sugerirCodigo(): string
    {
        $parts = [];

        $marca = trim((string) ($this->marca ?? ''));
        $modelo = trim((string) ($this->modelo_tv ?? ''));
        $nombre = trim((string) ($this->nombre ?? ''));

        if ($marca !== '') {
            $parts[] = $marca;
        }

        $pulgadas = is_numeric($this->pulgadas_tv) ? (int) $this->pulgadas_tv : null;
        if ($pulgadas !== null && $pulgadas > 0) {
            $parts[] = $pulgadas.'IN';
        }

        if ($modelo !== '') {
            $parts[] = $modelo;
        }

        $voltaje = is_numeric($this->voltaje_led) ? (float) $this->voltaje_led : null;
        if ($voltaje !== null && $voltaje > 0) {
            $voltStr = rtrim(rtrim(number_format($voltaje, 2, '.', ''), '0'), '.');
            $parts[] = $voltStr.'V';
        }

        $ledsPorBarra = is_numeric($this->leds_por_barra) ? (int) $this->leds_por_barra : null;
        if ($ledsPorBarra !== null && $ledsPorBarra > 0) {
            $parts[] = $ledsPorBarra.'LED';
        }

        $unidadesPorEmpaque = is_numeric($this->unidades_por_empaque) ? (int) $this->unidades_por_empaque : null;
        if ($unidadesPorEmpaque !== null && $unidadesPorEmpaque > 0) {
            $parts[] = 'PK'.$unidadesPorEmpaque;
        }

        if ($parts === []) {
            $parts[] = $nombre !== '' ? $nombre : 'PROD';
        }

        $base = Str::upper(Str::slug(implode('-', $parts), '-'));
        $base = substr($base, 0, 60);
        $base = $base !== '' ? $base : 'PROD';

        $candidate = $base;
        for ($i = 1; $i <= 99; $i++) {
            $exists = Producto::query()
                ->where('codigo', $candidate)
                ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->exists();

            if (! $exists) {
                return $candidate;
            }

            $candidate = $base.'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        return $base.'-'.Str::upper(Str::random(6));
    }
}
