<?php

namespace App\Livewire;

use App\Models\AlertaReposicion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Alertas')]
class AlertasPage extends Component
{
    public function render()
    {
        return view('livewire.alertas-page', [
            'pendientes' => AlertaReposicion::query()
                ->with('producto')
                ->where('estado', 'pendiente')
                ->orderByDesc('fecha_alerta')
                ->limit(200)
                ->get(),
            'resueltas' => AlertaReposicion::query()
                ->with('producto')
                ->where('estado', 'resuelto')
                ->orderByDesc('fecha_alerta')
                ->limit(50)
                ->get(),
        ]);
    }
}
