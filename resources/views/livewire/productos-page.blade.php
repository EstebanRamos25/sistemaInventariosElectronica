<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Productos</h1>
            <p class="mt-1 text-sm text-gray-600">CRUD básico de productos e inventario.</p>
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
                    Nuevo producto
                </button>
            @endif
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="rounded border border-gray-200 bg-white p-4 {{ $showForm ? '' : 'hidden' }}">
            <h2 class="font-medium">{{ $editingId ? 'Editar' : 'Nuevo' }} producto</h2>

            <form class="mt-4 space-y-3" wire:submit.prevent="save">
                <div>
                    <label class="block text-sm font-medium">Categoría</label>
                    <select class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="categoria_id">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($categorias as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Código / SKU</label>
                        <input
                            class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm"
                            placeholder='Ej: LG-40IN-LM1234-3V-5LED-PK4'
                            wire:model="codigo"
                        />
                        <div class="mt-1 text-xs text-gray-600">Si lo dejas vacío, el sistema sugiere uno con Marca/Modelo/Pulgadas.</div>
                        @error('codigo') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Nombre</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="nombre" />
                        @error('nombre') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Descripción</label>
                    <textarea class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" rows="2" wire:model="descripcion"></textarea>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Marca</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="marca" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Modelo(s) TV (compatibles)</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" placeholder="Ej: 40LF5650, 40LF5700" wire:model="modelo_tv" />
                        <div class="mt-1 text-xs text-gray-600">Puedes colocar varios modelos separados por coma.</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium">Pulgadas TV</label>
                        <input type="number" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="pulgadas_tv" />
                        @error('pulgadas_tv') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Voltaje LED (V)</label>
                        <input type="number" step="0.01" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="voltaje_led" />
                        @error('voltaje_led') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">LEDs por barra</label>
                        <input type="number" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="leds_por_barra" />
                        @error('leds_por_barra') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Características barra</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="caracteristicas_barra" />
                        @error('caracteristicas_barra') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium">Unidad</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="unidad" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Empaque</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="empaque" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Unidades por empaque</label>
                        <input type="number" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="unidades_por_empaque" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Precio compra</label>
                        <input type="number" step="0.01" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="precio_compra" />
                        @error('precio_compra') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Precio venta</label>
                        <input type="number" step="0.01" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="precio_venta" />
                        @error('precio_venta') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium">Stock actual</label>
                        <input type="number" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="stock_actual" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Stock mínimo</label>
                        <input type="number" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="stock_minimo" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Stock ideal</label>
                        <input type="number" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="stock_ideal" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Tiempo reposición (días)</label>
                        <input type="number" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="tiempo_reposicion_dias" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Ubicación</label>
                        <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="ubicacion" />
                    </div>
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
                <h2 class="font-medium">Listado <span class="text-sm text-gray-600">({{ $productos->count() }})</span></h2>
                <div class="text-sm text-gray-600">Tip: usa “Editar” para abrir el formulario</div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Código</th>
                            <th class="py-2">Nombre</th>
                            <th class="py-2 hidden lg:table-cell">Marca/Modelo</th>
                            <th class="py-2">Categoría</th>
                            <th class="py-2 hidden xl:table-cell">Ubicación</th>
                            <th class="py-2 hidden lg:table-cell">P. compra</th>
                            <th class="py-2">P. venta</th>
                            <th class="py-2">Stock</th>
                            <th class="py-2 hidden lg:table-cell">Mín.</th>
                            <th class="py-2">Activo</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productos as $p)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 font-medium">{{ $p->codigo }}</td>
                                <td class="py-2">{{ $p->nombre }}</td>
                                <td class="py-2 hidden lg:table-cell text-gray-700">
                                    {{ $p->marca }}@if($p->marca && $p->modelo_tv) · @endif{{ $p->modelo_tv }}@if($p->pulgadas_tv) · {{ $p->pulgadas_tv }}&quot;@endif
                                    @if($p->voltaje_led || $p->leds_por_barra)
                                        <span class="text-gray-500">
                                            ·
                                            @if($p->voltaje_led)
                                                {{ rtrim(rtrim(number_format((float) $p->voltaje_led, 2, '.', ''), '0'), '.') }}V
                                            @endif
                                            @if($p->voltaje_led && $p->leds_por_barra)
                                                ·
                                            @endif
                                            @if($p->leds_por_barra)
                                                {{ $p->leds_por_barra }} LED/barra
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2">{{ $p->categoria?->nombre }}</td>
                                <td class="py-2 hidden xl:table-cell text-gray-700">{{ $p->ubicacion }}</td>
                                <td class="py-2 hidden lg:table-cell">{{ $p->precio_compra }}</td>
                                <td class="py-2">{{ $p->precio_venta }}</td>
                                <td class="py-2 {{ (int) $p->stock_actual <= (int) $p->stock_minimo ? 'text-red-700 font-medium' : '' }}">{{ $p->stock_actual }}</td>
                                <td class="py-2 hidden lg:table-cell">{{ $p->stock_minimo }}</td>
                                <td class="py-2">{{ $p->activo ? 'Sí' : 'No' }}</td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    <button class="rounded border border-gray-300 bg-white px-2 py-1" wire:click="edit({{ $p->id }})">Editar</button>
                                    <button
                                        class="ml-2 rounded border border-red-300 bg-white px-2 py-1 text-red-700"
                                        wire:click="delete({{ $p->id }})"
                                        wire:confirm="¿Eliminar el producto {{ $p->codigo }} - {{ $p->nombre }}?\nEsta acción no se puede deshacer."
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        @if ($productos->isEmpty())
                            <tr>
                                <td class="py-3 text-gray-600" colspan="11">Sin productos todavía.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
