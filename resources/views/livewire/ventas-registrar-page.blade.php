<div>
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

    @if ($ultima_venta_numero)
        <div class="mt-4 rounded border border-gray-200 bg-white p-4 text-sm">
            <div><span class="font-medium">Última venta:</span> {{ $ultima_venta_numero }}</div>
            <div class="mt-1"><span class="font-medium">Total:</span> {{ $ultima_venta_total }}</div>
        </div>
    @endif

    <div class="mt-6 rounded border border-gray-200 bg-white p-4">
        @if ($cajaAbierta)
            <div class="text-sm text-gray-700">Caja abierta desde {{ $cajaAbierta->fecha_apertura }}</div>
        @else
            <div class="rounded border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-900">
                No hay caja abierta. Abre la caja para registrar ventas.
            </div>
        @endif

        <form class="mt-4 space-y-3" wire:submit.prevent="registrar">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium">Tipo de pago</label>
                    <select class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="tipo_pago">
                        <option value="efectivo">Efectivo</option>
                        <option value="qr">QR</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                    @error('tipo_pago') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Descuento</label>
                    <input type="number" step="0.01" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="descuento" />
                    @error('descuento') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium">Observaciones</label>
                    <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="observaciones" />
                </div>
            </div>

            <div class="rounded border border-gray-200 p-3">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium">Items</div>
                    <button type="button" class="rounded border border-gray-300 bg-white px-2 py-1 text-sm" wire:click="addItem">+ Item</button>
                </div>

                <div class="mt-3 space-y-3">
                    @foreach ($items as $i => $item)
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
                            <div class="md:col-span-3">
                                <label class="block text-xs text-gray-600">Producto</label>
                                <select class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="items.{{ $i }}.producto_id">
                                    <option value="">-- Seleccionar --</option>
                                    @foreach ($productos as $p)
                                        <option value="{{ $p->id }}">{{ $p->codigo }} - {{ $p->nombre }} (Stock: {{ $p->stock_actual }})</option>
                                    @endforeach
                                </select>
                                @error('items.'.$i.'.producto_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600">Cantidad</label>
                                <input type="number" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="items.{{ $i }}.cantidad" />
                                @error('items.'.$i.'.cantidad') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="button" class="text-sm text-red-700" wire:click="removeItem({{ $i }})">Quitar</button>
                        </div>
                    @endforeach
                </div>
            </div>

            <button class="rounded bg-gray-900 px-3 py-2 text-sm text-white" type="submit" @disabled(! $cajaAbierta)>
                Registrar venta
            </button>
        </form>
    </div>
</div>
