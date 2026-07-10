<x-slot name="header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Tasa de Cambio</h1>
            <p class="mt-0.5 text-sm text-gray-500">Historial del valor del dólar (USD → Bs)</p>
        </div>
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-700 transition-colors"
            wire:click="nuevaTasa"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Registrar tasa
        </button>
    </div>
</x-slot>

<div class="space-y-6">

    {{-- Flash messages --}}
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Tasa vigente (banner) ─────────────────────────────────────────── --}}
    @if ($tasaVigente)
        <div class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-600">Tasa vigente</div>
                    <div class="mt-1 flex items-baseline gap-3">
                        <span class="text-4xl font-bold text-gray-900">
                            Bs {{ number_format((float) $tasaVigente->tasa, 2) }}
                        </span>
                        <span class="text-lg text-gray-500">por $1 USD</span>
                    </div>
                    <div class="mt-1.5 flex flex-wrap gap-4 text-sm text-gray-600">
                        <span>
                            📅 Vigente desde:
                            <strong>{{ $tasaVigente->fecha->format('d/m/Y') }}</strong>
                        </span>
                        @if ($tasaVigente->fuente)
                            <span>
                                🏦 Fuente:
                                <strong>{{ $tasaVigente->fuente }}</strong>
                            </span>
                        @endif
                    </div>
                    @if ($tasaVigente->notas)
                        <p class="mt-1 text-xs text-gray-500 italic">{{ $tasaVigente->notas }}</p>
                    @endif
                </div>
                <div class="rounded-lg bg-white border border-blue-100 px-4 py-3 text-sm text-gray-700 shadow-sm">
                    <div class="font-semibold text-gray-800 mb-1">Conversiones rápidas</div>
                    @php $t = (float) $tasaVigente->tasa; @endphp
                    <div class="space-y-0.5">
                        <div>$1 USD = <strong>Bs {{ number_format($t, 2) }}</strong></div>
                        <div>$10 USD = <strong>Bs {{ number_format($t * 10, 2) }}</strong></div>
                        <div>$50 USD = <strong>Bs {{ number_format($t * 50, 2) }}</strong></div>
                        <div>$100 USD = <strong>Bs {{ number_format($t * 100, 2) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5 text-sm text-yellow-800">
            ⚠ No hay tasa de cambio registrada aún. Registra la primera tasa para activar las conversiones de moneda en el sistema.
        </div>
    @endif

    {{-- ── Formulario nueva/editar tasa ─────────────────────────────────── --}}
    @if ($showForm)
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-gray-800">
                {{ $editingId ? 'Editar tasa' : 'Registrar nueva tasa' }}
            </h2>

            <form wire:submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                    {{-- Tasa --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Bs por $1 USD <span class="text-red-500">*</span>
                        </label>
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">Bs</span>
                            <input
                                id="campo-tasa"
                                type="number"
                                step="0.0001"
                                min="0"
                                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="6.9600"
                                wire:model.blur="tasa"
                                autofocus
                            />
                        </div>
                        @error('tasa') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror

                        {{-- Preview conversión --}}
                        @if ((float)$tasa > 0)
                            <div class="mt-1 text-xs text-blue-600">
                                $1 = Bs {{ number_format((float)$tasa, 2) }} ·
                                $100 = Bs {{ number_format((float)$tasa * 100, 2) }}
                            </div>
                        @endif
                    </div>

                    {{-- Fecha --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Fecha de vigencia <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="campo-fecha"
                            type="date"
                            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            wire:model="fecha"
                        />
                        @error('fecha') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>

                    {{-- Fuente --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fuente</label>
                        <select
                            id="campo-fuente"
                            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                            wire:model="fuente"
                        >
                            <option value="manual">Manual</option>
                            <option value="BCB">BCB (Banco Central Bolivia)</option>
                            <option value="mercado">Mercado</option>
                            <option value="paralelo">Paralelo</option>
                        </select>
                    </div>

                    {{-- Notas --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notas</label>
                        <input
                            type="text"
                            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                            placeholder="Observaciones opcionales…"
                            wire:model="notas"
                        />
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 transition-colors"
                    >
                        {{ $editingId ? 'Actualizar' : 'Guardar tasa' }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                        wire:click="cancelar"
                    >
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ── Historial de tasas ────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-800">Historial de tasas</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Fecha</th>
                        <th class="px-5 py-3 text-right">Tasa (Bs/$)</th>
                        <th class="px-5 py-3 text-right">$100 USD en Bs</th>
                        <th class="px-5 py-3 text-left">Fuente</th>
                        <th class="px-5 py-3 text-left">Notas</th>
                        <th class="px-5 py-3 text-left">Registrado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($historial as $registro)
                        @php $esVigente = $tasaVigente && $registro->id === $tasaVigente->id; @endphp
                        <tr class="{{ $esVigente ? 'bg-blue-50/60' : 'bg-white hover:bg-gray-50' }} transition-colors">
                            <td class="px-5 py-3 font-medium text-gray-900">
                                {{ $registro->fecha->format('d/m/Y') }}
                                @if ($esVigente)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                        Vigente
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-semibold tabular-nums text-gray-900">
                                {{ number_format((float)$registro->tasa, 4) }}
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums text-gray-600">
                                Bs {{ number_format((float)$registro->tasa * 100, 2) }}
                            </td>
                            <td class="px-5 py-3 text-gray-600">
                                {{ $registro->fuente ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 italic">
                                {{ $registro->notas ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-400">
                                {{ $registro->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded px-2 py-1 text-xs text-blue-600 hover:bg-blue-50 transition-colors"
                                        wire:click="editarTasa({{ $registro->id }})"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded px-2 py-1 text-xs text-red-600 hover:bg-red-50 transition-colors"
                                        wire:click="eliminar({{ $registro->id }})"
                                        wire:confirm="¿Eliminar esta tasa del historial?"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-400">
                                No hay tasas registradas aún.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($historial->hasPages())
            <div class="border-t border-gray-100 px-5 py-3">
                {{ $historial->links() }}
            </div>
        @endif
    </div>

</div>
