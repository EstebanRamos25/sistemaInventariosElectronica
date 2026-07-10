<?php

namespace App\Livewire;

use App\Models\TasaCambio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Tasa de cambio')]
class TasaCambioPage extends Component
{
    use WithPagination;

    // ── Formulario nueva tasa ────────────────────────────────────────────────
    public string $tasa    = '';
    public string $fuente  = 'manual';
    public string $notas   = '';
    public string $fecha   = '';

    public bool $showForm = false;

    // ── Edición ─────────────────────────────────────────────────────────────
    public ?int $editingId = null;

    public function mount(): void
    {
        $this->fecha = now()->toDateString();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.tasa-cambio-page', [
            'tasaVigente' => TasaCambio::vigente(),
            'historial'   => TasaCambio::query()
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    // ── Acciones ─────────────────────────────────────────────────────────────

    public function nuevaTasa(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editarTasa(int $id): void
    {
        $tasa = TasaCambio::findOrFail($id);
        $this->editingId = $id;
        $this->tasa      = (string) $tasa->tasa;
        $this->fuente    = $tasa->fuente ?? 'manual';
        $this->notas     = $tasa->notas ?? '';
        $this->fecha     = $tasa->fecha->toDateString();
        $this->showForm  = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'tasa'   => ['required', 'numeric', 'min:0.0001', 'max:99999'],
            'fuente' => ['required', 'string', 'max:50'],
            'notas'  => ['nullable', 'string', 'max:500'],
            'fecha'  => ['required', 'date'],
        ]);

        TasaCambio::updateOrCreate(
            ['id' => $this->editingId],
            $data,
        );

        Session::flash('status', $this->editingId
            ? 'Tasa actualizada correctamente.'
            : 'Nueva tasa registrada correctamente.'
        );

        $this->resetForm();
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        TasaCambio::findOrFail($id)->delete();
        Session::flash('status', 'Tasa eliminada.');
    }

    public function cancelar(): void
    {
        $this->resetForm();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->showForm  = false;
        $this->tasa      = '';
        $this->fuente    = 'manual';
        $this->notas     = '';
        $this->fecha     = now()->toDateString();
    }
}
