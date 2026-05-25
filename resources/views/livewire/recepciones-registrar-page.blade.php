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
            <select class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model.live="orden_compra_id" wire:change="loadOrden">
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

        <div class="mt-4">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" class="rounded border-gray-300" wire:model="recibir_en_empaques" />
                Capturar “recibido ahora” en empaques/paquetes (se convierte a unidades)
            </label>
            <div class="mt-1 text-xs text-gray-600">Si un producto no tiene “Unidades por empaque”, se toma como unidades.</div>
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
                                    @if ($recibir_en_empaques && (int) ($item['unidades_por_empaque'] ?? 0) > 0)
                                        <div class="text-xs text-gray-600">1 empaque = {{ (int) $item['unidades_por_empaque'] }} {{ $item['unidad'] ?? 'unid.' }}</div>
                                    @endif
                                </td>
                                <td class="py-2">{{ $item['cantidad_ordenada'] }}</td>
                                <td class="py-2">
                                    <div class="flex items-center gap-3">
                                        <input type="number" class="w-32 rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model.live="items.{{ $i }}.cantidad_recibida" />
                                        @if ($recibir_en_empaques && (int) ($item['unidades_por_empaque'] ?? 0) > 0)
                                            <div class="text-xs text-gray-600">
                                                = {{ (int) ($item['cantidad_recibida'] ?? 0) * (int) $item['unidades_por_empaque'] }} {{ $item['unidad'] ?? 'unid.' }}
                                            </div>
                                        @endif
                                    </div>
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

        @php
            $canRegistrar = (bool) $orden_compra_id && collect($items)->contains(fn ($it) => (int) ($it['cantidad_recibida'] ?? 0) > 0);
        @endphp

        <button class="mt-4 rounded bg-gray-900 px-3 py-2 text-sm text-white disabled:opacity-50" wire:click="registrar" @disabled(! $canRegistrar)>
            Registrar recepción
        </button>

        @if ($orden_compra_id && ! $canRegistrar)
            <div class="mt-2 text-xs text-gray-600">Ingresa “Recibido ahora” en al menos un producto para habilitar el registro.</div>
        @endif
    </div>
</div>
