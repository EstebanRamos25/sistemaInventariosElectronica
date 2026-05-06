<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Caja</h1>
            <p class="mt-1 text-sm text-gray-600">Apertura y cierre de caja (una tienda).</p>
        </div>

        @if (session('status'))
            <div class="rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded border border-gray-200 bg-white p-4">
            <h2 class="font-medium">Estado actual</h2>

            @if ($cajaAbierta)
                <div class="mt-3 rounded border border-gray-200 p-3">
                    <div class="text-sm"><span class="font-medium">Caja abierta</span> desde {{ $cajaAbierta->fecha_apertura }}</div>
                    <div class="mt-1 text-sm text-gray-700">Monto inicial: {{ $cajaAbierta->monto_inicial }}</div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">Monto final</label>
                    <input type="number" step="0.01" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="monto_final" />
                </div>

                <button class="mt-3 rounded bg-gray-900 px-3 py-2 text-sm text-white" wire:click="cerrar({{ $cajaAbierta->id }})">
                    Cerrar caja
                </button>
            @else
                <div class="mt-3 text-sm text-gray-700">No hay caja abierta.</div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">Monto inicial</label>
                    <input type="number" step="0.01" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="monto_inicial" />
                </div>

                <button class="mt-3 rounded bg-gray-900 px-3 py-2 text-sm text-white" wire:click="abrir">
                    Abrir caja
                </button>
            @endif
        </section>

        <section class="rounded border border-gray-200 bg-white p-4">
            <h2 class="font-medium">Historial</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Apertura</th>
                            <th class="py-2">Cierre</th>
                            <th class="py-2">Estado</th>
                            <th class="py-2">Inicial</th>
                            <th class="py-2">Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cajas as $c)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">{{ $c->fecha_apertura }}</td>
                                <td class="py-2">{{ $c->fecha_cierre }}</td>
                                <td class="py-2">{{ $c->estado }}</td>
                                <td class="py-2">{{ $c->monto_inicial }}</td>
                                <td class="py-2">{{ $c->monto_final }}</td>
                            </tr>
                        @endforeach
                        @if ($cajas->isEmpty())
                            <tr>
                                <td class="py-3 text-gray-600" colspan="5">Sin historial de cajas.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
