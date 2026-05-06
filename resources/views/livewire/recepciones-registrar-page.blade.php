<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Registrar recepción</h1>
            <p class="mt-1 text-sm text-gray-600">Actualiza stock y crea kardex automáticamente.</p>
        </div>

        <div class="space-y-2">
            @if (session('status'))
                <div class="rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    </div>

    @if ($ultima_recepcion_id)
        <div class="mt-4 rounded border border-gray-200 bg-white p-4 text-sm">
            <div><span class="font-medium">Última recepción:</span> #{{ $ultima_recepcion_id }}</div>
        </div>
    @endif

    <div class="mt-6 rounded border border-gray-200 bg-white p-4">
        <div>
            <label class="block text-sm font-medium">Orden de compra</label>
            <select class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="orden_compra_id">
                <option value="">-- Seleccionar --</option>
                @foreach ($ordenes as $o)
                    <option value="{{ $o->id }}">{{ $o->numero_orden }} ({{ $o->estado }})</option>
                @endforeach
            </select>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium">Observaciones</label>
            <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="observaciones" />
        </div>

        <div class="mt-6">
            <h2 class="font-medium">Detalle</h2>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Producto</th>
                            <th class="py-2">Ordenado</th>
                            <th class="py-2">Recibido ahora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $i => $item)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">
                                    <div class="font-medium">{{ $item['codigo'] }}</div>
                                    <div class="text-gray-700">{{ $item['nombre'] }}</div>
                                </td>
                                <td class="py-2">{{ $item['cantidad_ordenada'] }}</td>
                                <td class="py-2">
                                    <input type="number" class="w-32 rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="items.{{ $i }}.cantidad_recibida" />
                                </td>
                            </tr>
                        @endforeach
                        @if (empty($items) && $orden_compra_id)
                            <tr>
                                <td class="py-3 text-gray-600" colspan="3">Esta orden no tiene detalle.</td>
                            </tr>
                        @endif
                        @if (! $orden_compra_id)
                            <tr>
                                <td class="py-3 text-gray-600" colspan="3">Selecciona una orden para cargar el detalle.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <button class="mt-4 rounded bg-gray-900 px-3 py-2 text-sm text-white" wire:click="registrar" @disabled(! $orden_compra_id)>
            Registrar recepción
        </button>
    </div>
</div>
