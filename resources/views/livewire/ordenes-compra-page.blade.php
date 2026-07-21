<div class="space-y-6">

    {{-- ── Encabezado ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Órdenes de Cotización</h1>
            <p class="mt-0.5 text-sm text-gray-500">Registra una orden de compra con el proveedor. Siempre en USD por juego/paquete.</p>
        </div>
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

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">

        {{-- ── Formulario nueva orden ─────────────────────────────────────── --}}
        <section class="xl:col-span-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Nueva orden de cotización</h2>

            <form wire:submit.prevent="save" class="space-y-4">

                {{-- Proveedor --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Proveedor <span class="text-red-500">*</span></label>
                    <select
                        class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                        wire:model="proveedor_id"
                    >
                        <option value="">— Seleccionar proveedor —</option>
                        @foreach ($proveedores as $pv)
                            <option value="{{ $pv->id }}">{{ $pv->nombre }}</option>
                        @endforeach
                    </select>
                    @error('proveedor_id') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                </div>

                {{-- Número orden + Fechas --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Número de orden <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none"
                            wire:model="numero_orden"
                            placeholder="COT-20260720-001"
                        />
                        <div class="mt-0.5 text-xs text-gray-400">Generado automáticamente, editable</div>
                        @error('numero_orden') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha orden</label>
                        <input type="date" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" wire:model="fecha_orden" />
                        @error('fecha_orden') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Llegada estimada</label>
                        <input type="date" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" wire:model="fecha_estimada_llegada" />
                    </div>
                </div>

                {{-- Observaciones --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                    <textarea
                        class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                        rows="2"
                        placeholder="Notas sobre esta cotización…"
                        wire:model="observaciones"
                    ></textarea>
                </div>

                {{-- ── Detalle de productos ──────────────────────────────── --}}
                <div class="rounded-lg border border-gray-200 overflow-hidden">
                    {{-- Cabecera del detalle --}}
                    <div class="flex items-center justify-between bg-gray-50 px-4 py-2.5 border-b border-gray-200">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Detalle de productos</div>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                            wire:click="addItem"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar producto
                        </button>
                    </div>

                    {{-- Cabecera columnas --}}
                    <div class="hidden sm:grid grid-cols-12 gap-2 bg-gray-50 border-b border-gray-100 px-4 py-2 text-xs font-medium uppercase tracking-wide text-gray-400">
                        <div class="col-span-5">Producto</div>
                        <div class="col-span-2 text-center">Piezas/juego</div>
                        <div class="col-span-2 text-center">Cantidad<br><span class="normal-case font-normal text-gray-300">(juegos)</span></div>
                        <div class="col-span-2 text-right">Precio/juego<br><span class="normal-case font-normal text-gray-300">(USD)</span></div>
                        <div class="col-span-1"></div>
                    </div>

                    {{-- Filas de ítems --}}
                    <div class="divide-y divide-gray-100">
                        @foreach ($items as $i => $item)
                            @php
                                $cant    = (float) ($item['cantidad_juegos']  ?? 0);
                                $precio  = (float) ($item['precio_por_juego'] ?? 0);
                                $subtot  = $cant * $precio;
                                $results = $searchResults[$i] ?? [];
                            @endphp

                            <div class="px-4 py-3 space-y-2" wire:key="item-{{ $i }}">
                                <div class="grid grid-cols-12 gap-2 items-start">

                                    {{-- Buscador de producto --}}
                                    <div class="col-span-12 sm:col-span-5 relative">
                                        <label class="block text-xs text-gray-500 mb-1 sm:hidden">Producto</label>

                                        @if ($item['producto_id'])
                                            {{-- Producto seleccionado --}}
                                            <div class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2">
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-medium text-gray-800 truncate">{{ $item['producto_label'] }}</div>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="shrink-0 text-gray-400 hover:text-red-500 transition-colors"
                                                    wire:click="clearProducto({{ $i }})"
                                                    title="Cambiar producto"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        @else
                                            {{-- Buscador activo --}}
                                            <input
                                                type="text"
                                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                placeholder="Buscar por código o nombre…"
                                                wire:model.live.debounce.300ms="items.{{ $i }}.search_term"
                                                autocomplete="off"
                                            />

                                            {{-- Dropdown de resultados --}}
                                            @if ($item['search_open'] && count($results) > 0)
                                                <div class="absolute left-0 right-0 top-full z-30 mt-1 max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                                                    @foreach ($results as $res)
                                                        <button
                                                            type="button"
                                                            class="w-full px-3 py-2.5 text-left text-sm hover:bg-blue-50 transition-colors border-b border-gray-50 last:border-0"
                                                            wire:click="selectProducto({{ $i }}, {{ $res['id'] }})"
                                                        >
                                                            <span class="font-mono font-medium text-gray-900">{{ explode(' — ', $res['label'])[0] ?? '' }}</span>
                                                            <span class="text-gray-500"> — {{ explode(' — ', $res['label'], 2)[1] ?? '' }}</span>
                                                            @if ($res['piezas_por_juego'])
                                                                <span class="ml-1 text-xs text-blue-500">({{ $res['piezas_por_juego'] }} pz/juego)</span>
                                                            @endif
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @elseif (strlen(trim($item['search_term'] ?? '')) >= 1 && count($results) === 0)
                                                <div class="absolute left-0 right-0 top-full z-30 mt-1 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-400 shadow-lg">
                                                    Sin resultados para "{{ $item['search_term'] }}"
                                                </div>
                                            @endif
                                        @endif

                                        @error('items.'.$i.'.producto_id')
                                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Piezas por juego --}}
                                    <div class="col-span-4 sm:col-span-2">
                                        <label class="block text-xs text-gray-500 mb-1 sm:hidden">Piezas/juego</label>
                                        <div class="relative">
                                            <input
                                                type="number"
                                                min="1"
                                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-2 py-2 text-sm text-center focus:border-blue-500 focus:bg-white focus:outline-none"
                                                wire:model="items.{{ $i }}.piezas_por_juego"
                                                placeholder="—"
                                                title="Número de barras/piezas en un juego/paquete cerrado"
                                            />
                                        </div>
                                        <div class="mt-0.5 text-center text-xs text-gray-400">piezas</div>
                                    </div>

                                    {{-- Cantidad de juegos --}}
                                    <div class="col-span-4 sm:col-span-2">
                                        <label class="block text-xs text-gray-500 mb-1 sm:hidden">Cantidad (juegos)</label>
                                        <input
                                            type="number"
                                            min="1"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm text-center focus:border-blue-500 focus:outline-none"
                                            wire:model.live.debounce.400ms="items.{{ $i }}.cantidad_juegos"
                                        />
                                        @error('items.'.$i.'.cantidad_juegos')
                                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Precio por juego --}}
                                    <div class="col-span-3 sm:col-span-2">
                                        <label class="block text-xs text-gray-500 mb-1 sm:hidden">Precio/juego $</label>
                                        <div class="relative">
                                            <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-gray-400">$</span>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-5 pr-2 text-sm text-right focus:border-blue-500 focus:outline-none"
                                                wire:model.live.debounce.400ms="items.{{ $i }}.precio_por_juego"
                                            />
                                        </div>
                                        @error('items.'.$i.'.precio_por_juego')
                                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Quitar --}}
                                    <div class="col-span-1 sm:col-span-1 flex items-center justify-center pt-1">
                                        <button
                                            type="button"
                                            class="rounded-lg p-1.5 text-gray-300 hover:bg-red-50 hover:text-red-500 transition-colors"
                                            wire:click="removeItem({{ $i }})"
                                            title="Quitar ítem"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Subtotal del ítem --}}
                                @if ($subtot > 0)
                                    <div class="flex justify-end">
                                        <span class="text-xs text-gray-500">
                                            Subtotal:
                                            <strong class="text-gray-800">$ {{ number_format($subtot, 2) }}</strong>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Total de la orden --}}
                    <div class="flex items-center justify-between border-t-2 border-gray-200 bg-gray-50 px-4 py-3">
                        <span class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Total orden</span>
                        <span class="text-xl font-bold text-gray-900">
                            $ {{ number_format($this->totalOrden, 2) }} <span class="text-sm font-normal text-gray-500">USD</span>
                        </span>
                    </div>
                </div>

                {{-- Botón guardar --}}
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-700 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Crear orden de cotización
                </button>
            </form>
        </section>

        {{-- ── Historial de órdenes ────────────────────────────────────────── --}}
        <section class="xl:col-span-2 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-800">Órdenes recientes</h2>
                <p class="text-xs text-gray-400 mt-0.5">Últimas 25 órdenes registradas</p>
            </div>

            <div class="overflow-y-auto max-h-[70vh]">
                @forelse ($ordenes as $o)
                    @php
                        $estadoClases = match($o->estado) {
                            'pendiente'  => 'bg-yellow-100 text-yellow-700',
                            'recibida'   => 'bg-green-100 text-green-700',
                            'parcial'    => 'bg-blue-100 text-blue-700',
                            'cancelada'  => 'bg-red-100 text-red-700',
                            default      => 'bg-gray-100 text-gray-600',
                        };
                        $detalles = $o->detalles ?? collect();
                        $preview  = $detalles->take(3)->map(fn ($d) =>
                            ($d->producto?->codigo ?? '#'.$d->producto_id)
                        )->implode(', ');
                        $restantes = max(0, $detalles->count() - 3);
                    @endphp
                    <div class="border-b border-gray-50 px-5 py-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-sm font-medium text-gray-900">{{ $o->numero_orden }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $estadoClases }}">
                                        {{ ucfirst($o->estado) }}
                                    </span>
                                </div>
                                <div class="mt-0.5 text-xs text-gray-500">
                                    {{ $o->proveedor?->nombre }} · {{ \Carbon\Carbon::parse($o->fecha_orden)->format('d/m/Y') }}
                                </div>
                                @if ($preview)
                                    <div class="mt-1 text-xs text-gray-400 truncate">
                                        {{ $preview }}@if($restantes > 0)<span class="text-gray-300"> +{{ $restantes }} más</span>@endif
                                    </div>
                                @endif
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-sm font-semibold text-gray-900">$ {{ number_format((float)$o->total, 2) }}</div>
                                <div class="text-xs text-gray-400">{{ $o->detalles_count }} ítem(s)</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-12 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-sm">Sin órdenes registradas aún</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
