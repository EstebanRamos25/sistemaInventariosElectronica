<div class="space-y-6">

    {{-- ── Encabezado ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Recepciones</h1>
            <p class="mt-0.5 text-sm text-gray-500">Registra los juegos/paquetes que llegan de una orden de cotización.</p>
        </div>
        <div class="space-y-1.5">
            @if (session('status'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">

        {{-- ── Formulario de recepción ─────────────────────────────────────── --}}
        <section class="xl:col-span-3 space-y-4">

            {{-- Selección de orden --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">1. Seleccionar orden de cotización</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Orden de cotización <span class="text-red-500">*</span></label>
                    <select
                        class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                        wire:model.live="orden_compra_id"
                    >
                        <option value="">— Seleccionar orden pendiente —</option>
                        @foreach ($ordenes as $o)
                            <option value="{{ $o->id }}">
                                {{ $o->numero_orden }}
                                @if($o->proveedor) · {{ $o->proveedor->nombre }} @endif
                                · {{ \Carbon\Carbon::parse($o->fecha_orden)->format('d/m/Y') }}
                                @if($o->fecha_estimada_llegada)
                                    (llega ~{{ \Carbon\Carbon::parse($o->fecha_estimada_llegada)->format('d/m/Y') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @if ($ordenes->isEmpty())
                        <p class="mt-1.5 text-xs text-amber-600">No hay órdenes pendientes. Todas las órdenes han sido recibidas.</p>
                    @endif
                </div>
            </div>

            {{-- Detalle de recepción --}}
            @if ($orden_compra_id && count($items) > 0)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
                    <h2 class="text-sm font-semibold text-gray-800">2. Confirmar cantidades recibidas (en juegos)</h2>

                    {{-- Fecha de recepción real --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fecha de recepción real <span class="text-red-500">*</span></label>
                            <input
                                type="date"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                                wire:model="fecha_recepcion"
                            />
                            <div class="mt-0.5 text-xs text-gray-400">Fecha en que llegaron físicamente los productos</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                            <input
                                type="text"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                                placeholder="Nota opcional sobre esta recepción…"
                                wire:model="observaciones"
                            />
                        </div>
                    </div>

                    {{-- Tabla de ítems --}}
                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wide text-gray-500">
                                    <th class="py-2 px-3 text-left">Producto</th>
                                    <th class="py-2 px-3 text-center">Piezas/juego</th>
                                    <th class="py-2 px-3 text-center">Juegos ordenados</th>
                                    <th class="py-2 px-3 text-center">Juegos recibidos ahora</th>
                                    <th class="py-2 px-3 text-right">Unidades totales</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($items as $i => $item)
                                    @php
                                        $juegosRec     = (int) ($item['juegos_recibidos'] ?? 0);
                                        $piezas        = (int) ($item['piezas_por_juego'] ?? 0);
                                        $unidadesTot   = $piezas > 0 ? $juegosRec * $piezas : $juegosRec;
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition-colors" wire:key="rec-item-{{ $i }}">
                                        <td class="py-2.5 px-3">
                                            <div class="font-mono text-xs font-medium text-gray-700">{{ $item['codigo'] }}</div>
                                            <div class="text-xs text-gray-500 truncate max-w-xs">{{ $item['nombre'] }}</div>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            @if ($item['piezas_por_juego'])
                                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                                    {{ $item['piezas_por_juego'] }} pz
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="font-medium text-gray-800">{{ $item['cantidad_ordenada_juegos'] }}</span>
                                            <span class="text-xs text-gray-400"> juegos</span>
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <div class="flex items-center justify-center">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-center focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                    wire:model.live="items.{{ $i }}.juegos_recibidos"
                                                    placeholder="0"
                                                />
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            @if ($juegosRec > 0)
                                                <span class="font-medium text-green-700">
                                                    {{ $unidadesTot }}
                                                    <span class="text-xs font-normal text-gray-400">
                                                        @if ($piezas > 0) ud. @else juego(s) @endif
                                                    </span>
                                                </span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Info juegos → unidades --}}
                    <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-2.5 text-xs text-blue-700">
                        <strong>Nota:</strong> Solo se actualiza el stock de <strong>juegos (paquetes cerrados)</strong>.
                        Las barras LED sueltas se gestionan por separado al abrir los paquetes.
                    </div>

                    {{-- Botón registrar --}}
                    @php
                        $canRegistrar = (bool) $orden_compra_id
                            && collect($items)->contains(fn ($it) => (int) ($it['juegos_recibidos'] ?? 0) > 0);
                    @endphp

                    <div class="flex items-center gap-4">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            wire:click="registrar"
                            @disabled(! $canRegistrar)
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Registrar recepción
                        </button>
                        @if (! $canRegistrar && $orden_compra_id)
                            <span class="text-xs text-gray-500">Ingresa la cantidad de juegos recibidos en al menos un producto.</span>
                        @endif
                    </div>
                </div>
            @elseif ($orden_compra_id && count($items) === 0)
                <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-5 text-sm text-yellow-800">
                    Esta orden no tiene productos en el detalle.
                </div>
            @endif
        </section>

        {{-- ── Historial de recepciones ────────────────────────────────────── --}}
        <section class="xl:col-span-2 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-800">Historial de recepciones</h2>
                <p class="text-xs text-gray-400 mt-0.5">Últimas 30 recepciones registradas</p>
            </div>

            <div class="overflow-y-auto max-h-[75vh] divide-y divide-gray-50">
                @forelse ($historial as $rec)
                    <div class="px-5 py-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-xs font-medium text-gray-700">
                                        {{ $rec->ordenCompra?->numero_orden ?? '#'.$rec->orden_compra_id }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                                        Recibida
                                    </span>
                                </div>
                                <div class="mt-0.5 text-xs text-gray-500">
                                    {{ $rec->ordenCompra?->proveedor?->nombre }}
                                    · {{ $rec->fecha_recepcion?->format('d/m/Y') ?? '—' }}
                                </div>
                                {{-- Productos de esta recepción --}}
                                <div class="mt-1.5 space-y-0.5">
                                    @foreach ($rec->detalles->take(4) as $det)
                                        @php
                                            $piezas  = (int) ($det->producto?->unidades_por_empaque ?? 0);
                                            $unidades = (int) $det->cantidad_recibida;
                                            $juegos   = $piezas > 0 ? (int) ceil($unidades / $piezas) : $unidades;
                                        @endphp
                                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                            <span class="font-mono font-medium text-gray-700">{{ $det->producto?->codigo }}</span>
                                            <span class="text-gray-300">·</span>
                                            <span class="text-green-700 font-medium">+{{ $juegos }} juego(s)</span>
                                            @if ($piezas > 0)
                                                <span class="text-gray-400">({{ $unidades }} ud.)</span>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if ($rec->detalles->count() > 4)
                                        <div class="text-xs text-gray-400">+ {{ $rec->detalles->count() - 4 }} productos más</div>
                                    @endif
                                </div>
                                @if ($rec->observaciones)
                                    <div class="mt-1 text-xs italic text-gray-400">{{ $rec->observaciones }}</div>
                                @endif
                            </div>
                            <div class="shrink-0 text-right text-xs text-gray-400">
                                #{{ $rec->id }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-12 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <span class="text-sm">Sin recepciones registradas aún</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
