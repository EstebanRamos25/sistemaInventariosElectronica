<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Marcas</h1>
            <p class="mt-1 text-sm text-gray-600">Catálogo de marcas (nombre + logo).</p>
        </div>

        <div class="flex items-center gap-2">
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

            @if ($showForm)
                <button class="rounded border border-gray-300 bg-white px-3 py-2 text-sm" type="button" wire:click="cancelEdit">
                    Cerrar formulario
                </button>
            @else
                <button class="rounded bg-gray-900 px-3 py-2 text-sm text-white" type="button" wire:click="createNew">
                    Nueva marca
                </button>
            @endif
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="rounded border border-gray-200 bg-white p-4 {{ $showForm ? '' : 'hidden' }}">
            <h2 class="font-medium">{{ $editingId ? 'Editar' : 'Nueva' }} marca</h2>

            <form class="mt-4 space-y-3" wire:submit.prevent="save">
                <div>
                    <label class="block text-sm font-medium">Nombre</label>
                    <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="nombre" placeholder="Ej: LG" />
                    @error('nombre') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    <div class="mt-1 text-xs text-gray-600">Se guarda en mayúsculas para evitar duplicados.</div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Logo</label>
                    <input type="file" accept="image/*" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="logo" />
                    @error('logo') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror

                    <div class="mt-2 flex items-center gap-3">
                        @if ($logo)
                            <img class="h-12 w-12 rounded border border-gray-200 bg-white object-contain" src="{{ $logo->temporaryUrl() }}" alt="Preview" />
                            <div class="text-xs text-gray-600">Preview</div>
                        @elseif ($currentLogoPath)
                            <img class="h-12 w-12 rounded border border-gray-200 bg-white object-contain" src="{{ Storage::url($currentLogoPath) }}" alt="Logo" />
                            <div class="text-xs text-gray-600">Logo actual</div>
                        @else
                            <div class="h-12 w-12 rounded border border-dashed border-gray-300 bg-gray-50"></div>
                            <div class="text-xs text-gray-600">Sin logo</div>
                        @endif
                    </div>
                </div>

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
                <h2 class="font-medium">Listado <span class="text-sm text-gray-600">({{ $marcas->count() }})</span></h2>
                <div class="text-sm text-gray-600">Tip: no se puede eliminar una marca en uso</div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Logo</th>
                            <th class="py-2">Nombre</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($marcas as $m)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">
                                    @if ($m->logo_path)
                                        <img class="h-10 w-10 rounded border border-gray-200 bg-white object-contain" src="{{ Storage::url($m->logo_path) }}" alt="{{ $m->nombre }}" />
                                    @else
                                        <div class="h-10 w-10 rounded border border-dashed border-gray-300 bg-gray-50"></div>
                                    @endif
                                </td>
                                <td class="py-2 font-medium">{{ $m->nombre }}</td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    <button class="rounded border border-gray-300 bg-white px-2 py-1" wire:click="edit({{ $m->id }})">Editar</button>
                                    <button
                                        class="ml-2 rounded border border-red-300 bg-white px-2 py-1 text-red-700"
                                        wire:click="delete({{ $m->id }})"
                                        wire:confirm="¿Eliminar la marca “{{ $m->nombre }}”?\nEsta acción no se puede deshacer."
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @endforeach

                        @if ($marcas->isEmpty())
                            <tr>
                                <td class="py-3 text-gray-600" colspan="3">Sin marcas todavía.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
