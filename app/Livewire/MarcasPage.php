<?php

namespace App\Livewire;

use App\Models\Marca;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Marcas')]
class MarcasPage extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;
    public bool $showForm = false;

    public string $nombre = '';

    /** @var TemporaryUploadedFile|null */
    public $logo = null;
    public ?string $currentLogoPath = null;

    public function render()
    {
        return view('livewire.marcas-page', [
            'marcas' => Marca::query()->orderBy('nombre')->get(),
        ]);
    }

    public function edit(int $id): void
    {
        $marca = Marca::query()->findOrFail($id);

        $this->editingId = $marca->id;
        $this->nombre = $marca->nombre;
        $this->currentLogoPath = $marca->logo_path;
        $this->logo = null;

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
                Rule::unique('marcas', 'nombre')->ignore($this->editingId),
            ],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $data['nombre'] = strtoupper(trim($data['nombre']));

        $marca = $this->editingId ? Marca::query()->findOrFail($this->editingId) : new Marca();
        $marca->nombre = $data['nombre'];

        if ($this->logo instanceof TemporaryUploadedFile) {
            $newPath = $this->logo->store('marcas', 'public');
            if ($marca->logo_path) {
                Storage::disk('public')->delete($marca->logo_path);
            }
            $marca->logo_path = $newPath;
        }

        $marca->save();

        Session::flash('status', $this->editingId ? 'Marca actualizada.' : 'Marca creada.');

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $marca = Marca::query()->findOrFail($id);

        if ($marca->productos()->exists()) {
            Session::flash('error', 'No se puede eliminar la marca porque ya está asignada a uno o más productos.');
            return;
        }

        if ($marca->logo_path) {
            Storage::disk('public')->delete($marca->logo_path);
        }

        $marca->delete();

        Session::flash('status', 'Marca eliminada.');

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->showForm = false;
        $this->nombre = '';
        $this->logo = null;
        $this->currentLogoPath = null;
    }
}
