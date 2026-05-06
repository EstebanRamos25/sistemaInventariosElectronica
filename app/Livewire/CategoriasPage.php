<?php

namespace App\Livewire;

use App\Models\Categoria;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Categorías')]
class CategoriasPage extends Component
{
    public ?int $editingId = null;
    public bool $showForm = false;

    public string $nombre = '';
    public ?string $descripcion = null;

    public function render()
    {
        return view('livewire.categorias-page', [
            'categorias' => Categoria::query()->orderBy('nombre')->get(),
        ]);
    }

    public function edit(int $id): void
    {
        $categoria = Categoria::query()->findOrFail($id);

        $this->editingId = $categoria->id;
        $this->nombre = $categoria->nombre;
        $this->descripcion = $categoria->descripcion;

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
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias', 'nombre')->ignore($this->editingId),
            ],
            'descripcion' => ['nullable', 'string'],
        ]);

        Categoria::query()->updateOrCreate(
            ['id' => $this->editingId],
            $data,
        );

        session()->flash('status', $this->editingId ? 'Categoría actualizada.' : 'Categoría creada.');

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $categoria = Categoria::query()->findOrFail($id);
        $categoria->delete();

        session()->flash('status', 'Categoría eliminada.');

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->showForm = false;
        $this->nombre = '';
        $this->descripcion = null;
    }
}
