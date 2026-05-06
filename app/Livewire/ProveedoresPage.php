<?php

namespace App\Livewire;

use App\Models\Proveedor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Proveedores')]
class ProveedoresPage extends Component
{
    public ?int $editingId = null;
    public bool $showForm = false;

    public string $nombre = '';
    public ?string $telefono = null;
    public ?string $email = null;
    public ?string $direccion = null;
    public ?string $observaciones = null;
    public bool $activo = true;

    public function render()
    {
        return view('livewire.proveedores-page', [
            'proveedores' => Proveedor::query()->orderBy('nombre')->get(),
        ]);
    }

    public function edit(int $id): void
    {
        $p = Proveedor::query()->findOrFail($id);

        $this->editingId = $p->id;
        $this->nombre = $p->nombre;
        $this->telefono = $p->telefono;
        $this->email = $p->email;
        $this->direccion = $p->direccion;
        $this->observaciones = $p->observaciones;
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
        $data = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ]);

        Proveedor::query()->updateOrCreate(
            ['id' => $this->editingId],
            $data,
        );

        session()->flash('status', $this->editingId ? 'Proveedor actualizado.' : 'Proveedor creado.');
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $p = Proveedor::query()->findOrFail($id);
        $p->delete();

        session()->flash('status', 'Proveedor eliminado.');

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->showForm = false;
        $this->nombre = '';
        $this->telefono = null;
        $this->email = null;
        $this->direccion = null;
        $this->observaciones = null;
        $this->activo = true;
    }
}
