<?php

namespace App\Livewire;

use App\Models\Caja;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Caja')]
class CajasPage extends Component
{
    public string|int|float $monto_inicial = '0.00';
    public string|int|float $monto_final = '0.00';

    public function render()
    {
        return view('livewire.cajas-page', [
            'cajaAbierta' => Caja::query()->where('estado', 'abierta')->orderByDesc('fecha_apertura')->first(),
            'cajas' => Caja::query()->orderByDesc('fecha_apertura')->limit(20)->get(),
        ]);
    }

    public function abrir(): void
    {
        DB::transaction(function () {
            $existe = Caja::query()->lockForUpdate()->where('estado', 'abierta')->exists();
            if ($existe) {
                return;
            }

            Caja::query()->create([
                'fecha_apertura' => now(),
                'monto_inicial' => $this->monto_inicial,
                'estado' => 'abierta',
            ]);
        });

        session()->flash('status', 'Caja abierta.');
    }

    public function cerrar(int $cajaId): void
    {
        DB::transaction(function () use ($cajaId) {
            $caja = Caja::query()->lockForUpdate()->findOrFail($cajaId);
            if ($caja->estado !== 'abierta') {
                return;
            }

            $caja->fecha_cierre = now();
            $caja->monto_final = $this->monto_final;
            $caja->estado = 'cerrada';
            $caja->save();
        });

        session()->flash('status', 'Caja cerrada.');

        $this->monto_final = '0.00';
    }
}
