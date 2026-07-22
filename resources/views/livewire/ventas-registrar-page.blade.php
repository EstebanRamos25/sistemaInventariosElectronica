<div>
    {{-- ── ENCABEZADO ──────────────────────────────────────────────────────── --}}
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Registrar venta</h1>
            <p class="mt-1 text-sm text-gray-600">Crea una venta y registra kardex + caja automáticamente.</p>
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

    {{-- ── ÚLTIMA VENTA ─────────────────────────────────────────────────────── --}}
    @if ($ultima_venta_numero)
        <div class="mt-4 rounded border border-green-200 bg-green-50 p-4 text-sm">
            <div class="font-medium text-green-800">✓ Venta registrada exitosamente</div>
            <div class="mt-1 text-green-700">
                <span class="font-medium">Número:</span> {{ $ultima_venta_numero }}
                &nbsp;·&nbsp;
                <span class="font-medium">Total:</span> ${{ $ultima_venta_total }}
            </div>
        </div>
    @endif

    {{-- ── FORMULARIO ───────────────────────────────────────────────────────── --}}
    <div class="mt-6 rounded border border-gray-200 bg-white p-4">
        {{-- Estado de caja --}}
        @if ($cajaAbierta)
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                Caja abierta desde {{ $cajaAbierta->fecha_apertura }}
            </div>
        @else
            <div class="rounded border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-900">
                ⚠ No hay caja abierta. Abre la caja para registrar ventas.
            </div>
        @endif

        <form class="mt-5 space-y-5" wire:submit.prevent="registrar">

            {{-- Tipo pago / Descuento / Observaciones --}}
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium">Tipo de pago</label>
                    <select class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model.live="tipo_pago">
                        <option value="efectivo">💵 Efectivo</option>
                        <option value="qr">📱 QR</option>
                        <option value="pendiente_pago">⏳ Pendiente / Pagar después</option>
                    </select>
                    @error('tipo_pago') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror

                    @if ($tipo_pago === 'pendiente_pago')
                        <div class="mt-2 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            ⚠️ El inventario se reduce inmediatamente, pero <strong>el monto NO se registra en caja</strong> hasta que se confirme el cobro.
                        </div>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium">Descuento ($)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm"
                        wire:model.blur="descuento"
                        placeholder="0.00"
                    />
                    @error('descuento') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Observaciones</label>
                    <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="observaciones" />
                </div>
            </div>

            {{-- ── ÍTEMS ──────────────────────────────────────────────────────── --}}
            <div class="rounded border border-gray-200">
                {{-- Cabecera ítems --}}
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <div class="text-sm font-semibold text-gray-800">
                        Ítems de la venta
                        <span class="ml-1 text-gray-400 font-normal">({{ count($items) }})</span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                        wire:click="addItem"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Agregar ítem
                    </button>
                </div>

                {{-- Cabecera columnas --}}
                <div class="hidden grid-cols-12 gap-3 border-b border-gray-100 bg-gray-50 px-4 py-2 text-xs font-medium uppercase tracking-wide text-gray-500 md:grid">
                    <div class="col-span-5">Producto</div>
                    <div class="col-span-2 text-right">Precio unit.</div>
                    <div class="col-span-2 text-center">Cantidad</div>
                    <div class="col-span-2 text-right">Subtotal</div>
                    <div class="col-span-1"></div>
                </div>

                {{-- Filas de ítems --}}
                <div class="divide-y divide-gray-100 px-4">
                    @foreach ($items as $i => $item)
                        @php
                            $precioItem    = (float) ($item['precio_unitario'] ?? 0);
                            $cantidadItem  = max(0, (int) ($item['cantidad'] ?? 0));
                            $subtotalItem  = $precioItem * $cantidadItem;
                            $hayProducto   = $item['producto_id'] !== '' && $item['producto_id'] !== null;
                            $tipoVentaItem = $item['tipo_venta'] ?? 'juego';
                            $stockJuegos   = (int) ($item['stock_juegos'] ?? 0);
                            $stockBarras   = (int) ($item['stock_barras'] ?? 0);
                            $barrasPorJuego = (int) ($item['barras_por_juego'] ?? 0);
                        @endphp
                        <div class="py-3" wire:key="item-{{ $i }}">
                            {{-- Fila 1: Producto + Tipo de venta --}}
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-start">

                                {{-- Buscador de producto (combobox) --}}
                                <div class="relative md:col-span-5">
                                    <label class="block text-xs font-medium text-gray-600 md:hidden">Producto</label>
                                    <input
                                        id="producto-search-{{ $i }}"
                                        type="text"
                                        autocomplete="off"
                                        placeholder="Buscar por código o nombre…"
                                        class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm {{ $hayProducto ? 'text-gray-900 font-medium' : 'text-gray-700' }}"
                                        wire:model.live.debounce.250ms="items.{{ $i }}.search"
                                        wire:focus="$set('items.{{ $i }}.open', true)"
                                    />

                                    {{-- Dropdown de resultados --}}
                                    @if (!empty($item['open']) && trim($item['search']) !== '')
                                        @php $resultados = $this->buscarProductos($i); @endphp
                                        <div
                                            class="absolute left-0 top-full z-50 mt-1 w-full rounded border border-gray-200 bg-white shadow-lg"
                                            wire:click.stop
                                        >
                                            @if (count($resultados) > 0)
                                                <ul class="max-h-60 overflow-y-auto divide-y divide-gray-100">
                                                    @foreach ($resultados as $prod)
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="flex w-full items-center justify-between px-3 py-2.5 text-left text-sm hover:bg-blue-50 transition-colors"
                                                                wire:click="seleccionarProducto({{ $i }}, {{ $prod['id'] }})"
                                                            >
                                                                <div>
                                                                    <span class="font-medium text-gray-900">{{ $prod['codigo'] }}</span>
                                                                    <span class="ml-2 text-gray-600">{{ $prod['nombre'] }}</span>
                                                                </div>
                                                                <div class="ml-3 flex-shrink-0 text-right text-xs">
                                                                    <div class="font-semibold text-gray-800">${{ number_format((float)$prod['precio_venta'], 2) }} juego</div>
                                                                    <div class="{{ $prod['stock_actual'] > 0 ? 'text-green-600' : 'text-red-500' }}">
                                                                        {{ $prod['stock_actual'] }} juegos · {{ $prod['stock_barras_sueltas'] ?? 0 }} barras
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <div class="px-3 py-3 text-sm text-gray-500">
                                                    Sin resultados para "{{ $item['search'] }}"
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @error('items.'.$i.'.producto_id')
                                        <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Tipo de venta: Juego / Barra --}}
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-600">Tipo</label>
                                    <div class="mt-1 flex rounded border border-gray-300 overflow-hidden text-sm">
                                        <button
                                            type="button"
                                            class="flex-1 py-2 text-center transition-colors {{ $tipoVentaItem === 'juego' ? 'bg-gray-900 text-white font-medium' : 'bg-white text-gray-600 hover:bg-gray-50' }}"
                                            wire:click="$set('items.{{ $i }}.tipo_venta', 'juego')"
                                        >🛍 Juego</button>
                                        <button
                                            type="button"
                                            class="flex-1 py-2 text-center border-l border-gray-300 transition-colors {{ $tipoVentaItem === 'barra' ? 'bg-blue-600 text-white font-medium' : 'bg-white text-gray-600 hover:bg-gray-50' }}"
                                            wire:click="$set('items.{{ $i }}.tipo_venta', 'barra')"
                                        >📦 Barra</button>
                                    </div>
                                    @error('items.'.$i.'.tipo_venta')
                                        <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Stock disponible contextual --}}
                                @if ($hayProducto)
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-600">Disponible</label>
                                    <div class="mt-1 rounded border border-gray-100 bg-gray-50 px-2 py-2 text-xs">
                                        @if ($tipoVentaItem === 'juego')
                                            <div class="{{ $stockJuegos > 0 ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold' }}">
                                                {{ $stockJuegos }} juego{{ $stockJuegos !== 1 ? 's' : '' }}
                                            </div>
                                            @if ($barrasPorJuego > 0)
                                                <div class="text-gray-500">{{ $barrasPorJuego }} barras/juego</div>
                                            @endif
                                        @else
                                            <div class="{{ $stockBarras > 0 ? 'text-blue-700 font-semibold' : 'text-orange-600 font-semibold' }}">
                                                {{ $stockBarras }} barra{{ $stockBarras !== 1 ? 's' : '' }} sueltas
                                            </div>
                                            @if ($stockJuegos > 0 && $barrasPorJuego > 0)
                                                <div class="text-gray-500">+{{ $stockJuegos }}×{{ $barrasPorJuego }} en juegos</div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                @else
                                <div class="md:col-span-2"></div>
                                @endif

                                {{-- Precio unitario (editable) --}}
                                <div class="{{ $hayProducto ? 'md:col-span-1' : 'md:col-span-3' }}">
                                    <label class="block text-xs font-medium text-gray-600 md:hidden">Precio ($)</label>
                                    <div class="relative mt-1">
                                        <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-gray-400">$</span>

                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full rounded border border-gray-300 bg-white py-2 pl-6 pr-2 text-sm text-right"
                                            wire:model.live="items.{{ $i }}.precio_unitario"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    @error('items.'.$i.'.precio_unitario')
                                        <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Cantidad --}}
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 md:hidden">Cantidad</label>
                                    <input
                                        type="number"
                                        min="1"
                                        class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-center"
                                        wire:model.live="items.{{ $i }}.cantidad"
                                    />
                                    @error('items.'.$i.'.cantidad')
                                        <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Subtotal del ítem --}}
                                <div class="flex items-center justify-end md:col-span-2">
                                    <div class="mt-1 text-sm font-semibold text-gray-800 md:pt-2">
                                        @if ($hayProducto && $cantidadItem > 0)
                                            ${{ number_format($subtotalItem, 2) }}
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Quitar --}}
                                <div class="flex items-center justify-end md:col-span-1 md:pt-2">
                                    <button
                                        type="button"
                                        class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                                        title="Quitar ítem"
                                        wire:click="removeItem({{ $i }})"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ── RESUMEN TOTALES ─────────────────────────────────────────── --}}
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="ml-auto max-w-xs space-y-1.5 text-sm">
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-medium text-gray-800">${{ number_format($this->subtotal, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Descuento</span>
                            <span class="text-red-600">− ${{ number_format((float) $descuento, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-gray-300 pt-1.5 font-semibold text-gray-900">
                            <span class="text-base">Total</span>
                            <span class="text-base text-gray-900">${{ number_format($this->totalFinal, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botón registrar --}}
            <div class="flex items-center gap-3">
                <button
                    class="rounded px-4 py-2 text-sm font-medium text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed
                        {{ $tipo_pago === 'pendiente_pago' ? 'bg-amber-600 hover:bg-amber-500' : 'bg-gray-900 hover:bg-gray-700' }}"
                    type="submit"
                    @disabled(! $cajaAbierta)
                >
                    @if ($tipo_pago === 'pendiente_pago')
                        ⏳ Registrar venta (pendiente de cobro) · ${{ number_format($this->totalFinal, 2) }}
                    @else
                        Registrar venta · ${{ number_format($this->totalFinal, 2) }}
                    @endif
                </button>
                @if (!$cajaAbierta)
                    <span class="text-sm text-yellow-700">Abre la caja primero</span>
                @endif
            </div>

        </form>
    </div>

    {{-- ── PANEL: VENTAS PENDIENTES DE COBRO ──────────────────────────────── --}}
    @if ($ventasPendientes->isNotEmpty())
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-lg">⏳</span>
                <h2 class="text-sm font-semibold text-amber-900">Ventas pendientes de cobro</h2>
                <span class="ml-1 inline-flex items-center rounded-full bg-amber-200 px-2 py-0.5 text-xs font-medium text-amber-800">
                    {{ $ventasPendientes->count() }}
                </span>
            </div>

            <div class="space-y-2">
                @foreach ($ventasPendientes as $vp)
                    <div class="rounded-lg border border-amber-100 bg-white px-4 py-3 flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-sm font-medium text-gray-900">{{ $vp->numero_venta }}</span>
                                <span class="text-xs text-gray-500">· {{ \Carbon\Carbon::parse($vp->fecha_venta)->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="mt-0.5 text-xs text-gray-500">
                                {{ $vp->detalles->map(fn($d) => ($d->producto?->codigo ?? '#'.$d->producto_id).' x'.$d->cantidad)->join(', ') }}
                            </div>
                            @if ($vp->observaciones)
                                <div class="mt-0.5 text-xs italic text-amber-700">{{ $vp->observaciones }}</div>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-base font-bold text-gray-900">${{ number_format((float)$vp->total, 2) }}</span>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    wire:click="cobrarPendiente({{ $vp->id }}, 'efectivo')"
                                    wire:confirm="¿Confirmar cobro de ${{ number_format((float)$vp->total, 2) }} en efectivo?"
                                >
                                    💵 Efectivo
                                </button>
                                <button
                                    type="button"
                                    class="rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    wire:click="cobrarPendiente({{ $vp->id }}, 'qr')"
                                    wire:confirm="¿Confirmar cobro de ${{ number_format((float)$vp->total, 2) }} por QR?"
                                >
                                    📱 QR
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
