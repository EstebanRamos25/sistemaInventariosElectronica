<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Productos</h1>
            <p class="mt-1 text-sm text-gray-600">Gestión de productos e inventario.</p>
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

            {{-- Botones PDF --}}
            <div class="flex items-center gap-1.5">
                {{-- PDF Completo --}}
                <a
                    href="{{ route('productos.pdf', array_filter(['marca_id' => is_numeric($marcaFilter) ? $marcaFilter : null, 'q' => $search !== '' ? $search : null])) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 rounded border border-red-300 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100 transition-colors"
                    title="Reporte completo en PDF (hoja horizontal)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ is_numeric($marcaFilter) && $marcaFilter ? 'PDF Marca' : 'PDF General' }}
                </a>

                {{-- PDF Rápido --}}
                <a
                    href="{{ route('productos.pdf', array_filter(['marca_id' => is_numeric($marcaFilter) ? $marcaFilter : null, 'q' => $search !== '' ? $search : null, 'tipo' => 'rapido'])) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                    title="Reporte rápido en PDF (hoja vertical, solo código/nombre/stock)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    PDF Rápido
                </a>
            </div>

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

    <section class="mt-6 rounded border border-gray-200 bg-white p-4">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="font-medium">Marcas</h2>
                <div class="mt-1 text-sm text-gray-600">Menú por logos para navegar rápido.</div>
            </div>

            <div class="w-full max-w-sm">
                <label class="block text-sm font-medium">Buscar marca</label>
                <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" placeholder="Ej: LG, SAMSUNG" wire:model.live="brandSearch" />
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($marcasMenu as $m)
                <a class="rounded border border-gray-200 bg-white p-3 hover:bg-gray-50" href="{{ route('productos', ['marca' => $m->id, 'q' => $search !== '' ? $search : null]) }}">
                    <div class="flex items-center gap-3">
                        @if ($m->logo_path)
                            <img class="h-10 w-10 rounded border border-gray-200 bg-white object-contain" src="{{ Storage::url($m->logo_path) }}" alt="{{ $m->nombre }}" />
                        @else
                            <div class="h-10 w-10 rounded border border-dashed border-gray-300 bg-gray-50"></div>
                        @endif
                        <div class="font-medium text-sm">{{ $m->nombre }}</div>
                    </div>
                </a>
            @endforeach

            @if ($marcasMenu->isEmpty())
                <div class="rounded border border-gray-200 bg-white p-3 text-sm text-gray-600 sm:col-span-3 lg:col-span-6">
                    Sin marcas que coincidan.
                </div>
            @endif
        </div>
    </section>

    <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium">Buscar producto</label>
            <input class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" placeholder="Código, nombre o modelo" wire:model.live="search" />
        </div>
        <div>
            <label class="block text-sm font-medium">Marca seleccionada</label>
            <div class="mt-1 flex items-center justify-between rounded border border-gray-300 bg-white px-3 py-2 text-sm">
                <div class="text-gray-800">{{ $marcaSeleccionada?->nombre ?? 'Todas' }}</div>
                @if ($marcaSeleccionada)
                    <a class="text-sm text-gray-700 underline" href="{{ route('productos', ['q' => $search !== '' ? $search : null]) }}">Quitar filtro</a>
                @endif
            </div>
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
                        <select class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="marca_id">
                            <option value="">-- Seleccionar --</option>
                            @foreach ($marcasCatalogo as $m)
                                <option value="{{ $m->id }}">{{ $m->nombre }}</option>
                            @endforeach
                        </select>
                        @error('marca_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
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
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h2 class="font-medium">
                    Listado
                    <span class="text-sm text-gray-600">({{ $productos->total() }} en total)</span>
                </h2>

                <div class="flex items-center gap-2">
                    {{-- Selector de registros por página --}}
                    <div class="flex items-center gap-1.5">
                        <label class="text-sm text-gray-600">Mostrar</label>
                        <select class="rounded border border-gray-300 bg-white px-2 py-1.5 text-sm" wire:model.live="perPage">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-sm text-gray-600">por página</span>
                    </div>

                    {{-- Botón PDF Completo --}}
                    <a
                        href="{{ route('productos.pdf', array_filter(['marca_id' => is_numeric($marcaFilter) ? $marcaFilter : null, 'q' => $search !== '' ? $search : null])) }}"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded border border-red-300 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-100 transition-colors"
                        title="Reporte completo (hoja horizontal)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        @if(is_numeric($marcaFilter) && $marcaFilter)
                            PDF — {{ $marcaSeleccionada?->nombre }}
                        @else
                            PDF General
                        @endif
                    </a>

                    {{-- Botón PDF Rápido --}}
                    <a
                        href="{{ route('productos.pdf', array_filter(['marca_id' => is_numeric($marcaFilter) ? $marcaFilter : null, 'q' => $search !== '' ? $search : null, 'tipo' => 'rapido'])) }}"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                        title="Reporte rápido (hoja vertical, solo código/nombre/stock)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF Rápido
                    </a>
                </div>
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
                            <th class="py-2">Estado</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productos as $p)
                            @php
                                $canDelete = (
                                    (int) $p->movimientos_inventario_count === 0 &&
                                    (int) $p->venta_detalles_count === 0 &&
                                    (int) $p->orden_compra_detalles_count === 0 &&
                                    (int) $p->recepcion_detalles_count === 0 &&
                                    (int) $p->alertas_reposicion_count === 0
                                );
                            @endphp

                            <tr class="border-b border-gray-100 {{ $p->activo ? '' : 'opacity-50' }}">
                                <td class="py-2 font-medium">{{ $p->codigo }}</td>
                                <td class="py-2">{{ $p->nombre }}</td>
                                <td class="py-2 hidden lg:table-cell text-gray-700">
                                    {{ $p->marca?->nombre }}@if($p->marca?->nombre && $p->modelo_tv) · @endif{{ $p->modelo_tv }}@if($p->pulgadas_tv) · {{ $p->pulgadas_tv }}&quot;@endif
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
                                <td class="py-2">
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300"
                                            @checked($p->activo)
                                            wire:key="activo-{{ $p->id }}"
                                            wire:change="setActivo({{ $p->id }}, $event.target.checked)"
                                        />
                                        <span class="text-gray-800">{{ $p->activo ? 'Activo' : 'Inactivo' }}</span>
                                    </label>
                                </td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    <button class="rounded border border-gray-300 bg-white px-2 py-1" wire:click="edit({{ $p->id }})">Editar</button>

                                    @if ($canDelete)
                                        <button
                                            class="ml-2 rounded border border-red-300 bg-white px-2 py-1 text-red-700"
                                            wire:click="delete({{ $p->id }})"
                                            wire:confirm="¿Eliminar el producto {{ $p->codigo }} - {{ $p->nombre }}?\nEsta acción no se puede deshacer."
                                        >
                                            Eliminar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if ($productos->isEmpty())
                            <tr>
                                <td class="py-3 text-gray-600" colspan="11">Sin productos para mostrar.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if ($productos->hasPages())
                <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                    <div class="text-sm text-gray-600">
                        Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }} de {{ $productos->total() }} productos
                    </div>
                    <div>
                        {{ $productos->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
