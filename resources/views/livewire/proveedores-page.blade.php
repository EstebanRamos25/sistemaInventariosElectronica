<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Proveedores</h1>
            <p class="mt-1 text-sm text-gray-600">Crea y administra proveedores.</p>
        </div>

        <div class="flex items-center gap-2">
            @if (session('status'))
                <div class="rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($showForm)
                <button class="rounded border border-gray-300 bg-white px-3 py-2 text-sm" type="button" wire:click="cancelEdit">
                    Cerrar formulario
                </button>
            @else
                <button class="rounded bg-gray-900 px-3 py-2 text-sm text-white" type="button" wire:click="createNew">
                    Nuevo proveedor
                </button>
            @endif
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="rounded border border-gray-200 bg-white p-4 {{ $showForm ? '' : 'hidden' }}">
            <h2 class="font-medium">{{ $editingId ? 'Editar' : 'Nuevo' }} proveedor</h2>

            <form class="mt-4 space-y-3" wire:submit.prevent="save">
                <div>
                    <label class="block text-sm font-medium">Nombre</label>
                    <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="nombre" />
                    @error('nombre') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Teléfono</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="telefono" />
                        @error('telefono') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="email" />
                        @error('email') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Dirección</label>
                    <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="direccion" />
                    @error('direccion') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium">Observaciones</label>
                    <textarea class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" rows="3" wire:model="observaciones"></textarea>
                    @error('observaciones') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="rounded border-gray-300" wire:model="activo" />
                    Activo
                </label>

                <div class="flex gap-2">
                    <button class="rounded bg-gray-900 px-3 py-2 text-sm text-white" type="submit">
                        {{ $editingId ? 'Actualizar' : 'Crear' }}
                    </button>
                    <button class="rounded border border-gray-300 bg-white px-3 py-2 text-sm" type="button" wire:click="cancelEdit">
                        {{ $editingId ? 'Cancelar' : 'Cerrar' }}
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded border border-gray-200 bg-white p-4 {{ $showForm ? 'lg:col-span-2' : 'lg:col-span-3' }}">
            <div class="flex items-end justify-between gap-4">
                <h2 class="font-medium">Listado <span class="text-sm text-gray-600">({{ $proveedores->count() }})</span></h2>
                <div class="text-sm text-gray-600">Tip: “Editar” abre el formulario</div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Nombre</th>
                            <th class="py-2">Teléfono</th>
                            <th class="py-2">Email</th>
                            <th class="py-2 hidden xl:table-cell">Dirección</th>
                            <th class="py-2">Activo</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proveedores as $p)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 font-medium">{{ $p->nombre }}</td>
                                <td class="py-2">{{ $p->telefono }}</td>
                                <td class="py-2">{{ $p->email }}</td>
                                <td class="py-2 hidden xl:table-cell text-gray-700">{{ $p->direccion }}</td>
                                <td class="py-2">{{ $p->activo ? 'Sí' : 'No' }}</td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    <button class="rounded border border-gray-300 bg-white px-2 py-1" wire:click="edit({{ $p->id }})">Editar</button>
                                    <button
                                        class="ml-2 rounded border border-red-300 bg-white px-2 py-1 text-red-700"
                                        wire:click="delete({{ $p->id }})"
                                        wire:confirm="¿Eliminar el proveedor “{{ $p->nombre }}”?\nEsta acción no se puede deshacer."
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        @if ($proveedores->isEmpty())
                            <tr>
                                <td class="py-3 text-gray-600" colspan="6">Sin proveedores todavía.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
