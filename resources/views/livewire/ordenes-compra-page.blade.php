<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Compras / Órdenes</h1>
            <p class="mt-1 text-sm text-gray-600">Crear orden de compra con detalle.</p>
        </div>

        @if (session('status'))
            <div class="rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded border border-gray-200 bg-white p-4">
            <h2 class="font-medium">Nueva orden</h2>

            <form class="mt-4 space-y-3" wire:submit.prevent="save">
                <div>
                    <label class="block text-sm font-medium">Proveedor</label>
                    <select class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="proveedor_id">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($proveedores as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                    @error('proveedor_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Número orden</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="numero_orden" />
                        @error('numero_orden') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Fecha orden</label>
                        <input type="date" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="fecha_orden" />
                        @error('fecha_orden') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Fecha estimada llegada</label>
                    <input type="date" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="fecha_estimada_llegada" />
                    @error('fecha_estimada_llegada') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Observaciones</label>
                    <textarea class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" rows="2" wire:model="observaciones"></textarea>
                </div>

                <div class="rounded border border-gray-200 p-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium">Detalle</div>
                        <button type="button" class="rounded border border-gray-300 bg-white px-2 py-1 text-sm" wire:click="addItem">+ Item</button>
                    </div>

                    <div class="mt-3 space-y-3">
                        @foreach ($items as $i => $item)
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-gray-600">Producto</label>
                                    <select class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="items.{{ $i }}.producto_id">
                                        <option value="">-- Seleccionar --</option>
                                        @foreach ($productos as $prod)
                                            <option value="{{ $prod->id }}">{{ $prod->codigo }} - {{ $prod->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('items.'.$i.'.producto_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600">Cantidad</label>
                                    <input type="number" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="items.{{ $i }}.cantidad" />
                                    @error('items.'.$i.'.cantidad') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600">Precio unit.</label>
                                    <input type="number" step="0.01" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="items.{{ $i }}.precio_unitario" />
                                    @error('items.'.$i.'.precio_unitario') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="button" class="text-sm text-red-700" wire:click="removeItem({{ $i }})">Quitar</button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button class="rounded bg-gray-900 px-3 py-2 text-sm text-white" type="submit">
                    Crear orden
                </button>
            </form>
        </section>

        <section class="rounded border border-gray-200 bg-white p-4">
            <h2 class="font-medium">Órdenes recientes</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Número</th>
                            <th class="py-2">Proveedor</th>
                            <th class="py-2">Fecha</th>
                            <th class="py-2">Estado</th>
                            <th class="py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ordenes as $o)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 font-medium">{{ $o->numero_orden }}</td>
                                <td class="py-2">{{ $o->proveedor?->nombre }}</td>
                                <td class="py-2">{{ $o->fecha_orden }}</td>
                                <td class="py-2">{{ $o->estado }}</td>
                                <td class="py-2">{{ $o->total }}</td>
                            </tr>
                        @endforeach
                        @if ($ordenes->isEmpty())
                            <tr>
                                <td class="py-3 text-gray-600" colspan="5">Sin órdenes todavía.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
