<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Caja</h1>
            <p class="mt-1 text-sm text-gray-600">Apertura y cierre de caja (una tienda).</p>
        </div>

        @if (session('status'))
            <div class="rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded border border-gray-200 bg-white p-4">
            <h2 class="font-medium">Estado actual</h2>

            @if ($cajaAbierta)
                <div class="mt-3 rounded border border-gray-200 p-3">
                    <div class="text-sm"><span class="font-medium">Caja abierta</span> desde {{ $cajaAbierta->fecha_apertura }}</div>
                    <div class="mt-1 text-sm text-gray-700">Monto inicial: {{ $cajaAbierta->monto_inicial }}</div>
                    @php
                        $resMov = $resumenMovimientos->get($cajaAbierta->id);
                        $resVen = $resumenVentas->get($cajaAbierta->id);
                        $ventasTotal = (float) ($resVen?->total ?? 0);
                        $ventasCantidad = (int) ($resVen?->cantidad ?? 0);
                        $netoMov = (float) ($resMov?->neto ?? 0);
                        $ingresosMov = (float) ($resMov?->ingresos ?? 0);
                        $egresosMov = (float) ($resMov?->egresos ?? 0);
                        $esperado = $montoEsperadoCajaAbierta;
                        $dif = $esperado !== null ? ((float) $monto_final - (float) $esperado) : null;
                    @endphp

                    <div class="mt-3 grid grid-cols-1 gap-2 text-sm md:grid-cols-3">
                        <div class="rounded border border-gray-200 p-2">
                            <div class="text-xs text-gray-600">Ventas registradas</div>
                            <div class="font-medium">$ {{ number_format($ventasTotal, 2, '.', ',') }}</div>
                            <div class="text-xs text-gray-600">{{ $ventasCantidad }} {{ $ventasCantidad === 1 ? 'venta' : 'ventas' }}</div>
                        </div>
                        <div class="rounded border border-gray-200 p-2">
                            <div class="text-xs text-gray-600">Movimientos</div>
                            <div class="font-medium">$ {{ number_format($netoMov, 2, '.', ',') }}</div>
                            <div class="text-xs text-gray-600">Ing: {{ number_format($ingresosMov, 2, '.', ',') }} · Egr: {{ number_format($egresosMov, 2, '.', ',') }}</div>
                        </div>
                        <div class="rounded border border-gray-200 p-2">
                            <div class="text-xs text-gray-600">Monto esperado</div>
                            <div class="font-medium">$ {{ number_format((float) ($esperado ?? 0), 2, '.', ',') }}</div>
                            <div class="text-xs text-gray-600">Inicial + neto movimientos</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">Monto final (conteo físico)</label>
                    <input type="number" step="0.01" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="monto_final" />

                    <div class="mt-1 text-xs text-gray-600">
                        Es el efectivo contado al cerrar. El sistema calcula “Esperado” con base en ventas/movimientos registrados.
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <button type="button" class="rounded border border-gray-300 bg-white px-2 py-1" wire:click="usarMontoFinalEsperado">
                            Usar monto esperado
                        </button>
                        @if ($dif !== null)
                            <div class="text-gray-700">
                                Diferencia: <span class="font-medium {{ $dif < 0 ? 'text-red-700' : ($dif > 0 ? 'text-green-700' : '') }}">
                                    $ {{ number_format($dif, 2, '.', ',') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <button class="mt-3 rounded bg-gray-900 px-3 py-2 text-sm text-white" wire:click="cerrar({{ $cajaAbierta->id }})">
                    Cerrar caja
                </button>
            @else
                <div class="mt-3 text-sm text-gray-700">No hay caja abierta.</div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">Monto inicial</label>
                    <input type="number" step="0.01" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="monto_inicial" />

                    <div class="mt-1 text-xs text-gray-600">
                        Efectivo con el que arrancas el día/turno (cambio en caja). No es ganancia ni ventas.
                    </div>
                </div>

                <button class="mt-3 rounded bg-gray-900 px-3 py-2 text-sm text-white" wire:click="abrir">
                    Abrir caja
                </button>
            @endif
        </section>

        <section class="rounded border border-gray-200 bg-white p-4">
            <h2 class="font-medium">Historial</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Apertura</th>
                            <th class="py-2">Cierre</th>
                            <th class="py-2">Estado</th>
                            <th class="py-2">Inicial</th>
                            <th class="py-2">Ventas (registradas)</th>
                            <th class="py-2">Esperado (sistema)</th>
                            <th class="py-2">Final (contado)</th>
                            <th class="py-2">Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cajas as $c)
                            @php
                                $resMov = $resumenMovimientos->get($c->id);
                                $resVen = $resumenVentas->get($c->id);

                                $ventasTotal = (float) ($resVen?->total ?? 0);
                                $netoMov = (float) ($resMov?->neto ?? 0);
                                $esperado = (float) $c->monto_inicial + $netoMov;
                            @endphp
                            <tr class="border-b border-gray-100">
                                <td class="py-2">{{ $c->fecha_apertura }}</td>
                                <td class="py-2">{{ $c->fecha_cierre }}</td>
                                <td class="py-2">{{ $c->estado }}</td>
                                <td class="py-2">{{ $c->monto_inicial }}</td>
                                <td class="py-2">$ {{ number_format($ventasTotal, 2, '.', ',') }}</td>
                                <td class="py-2">$ {{ number_format($esperado, 2, '.', ',') }}</td>
                                <td class="py-2">{{ $c->monto_final }}</td>
                                <td class="py-2">
                                    @php $items = $productosVendidosPorCaja[$c->id] ?? []; @endphp
                                    @if ($items !== [])
                                        <details>
                                            <summary class="cursor-pointer text-gray-700">Ver ({{ count($items) }})</summary>
                                            <div class="mt-2 space-y-1 text-xs text-gray-700">
                                                @foreach ($items as $it)
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="truncate">{{ $it->codigo }} - {{ $it->nombre }}</div>
                                                        <div class="shrink-0 text-gray-600">x{{ (int) $it->cantidad }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-gray-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if ($cajas->isEmpty())
                            <tr>
                                <td class="py-3 text-gray-600" colspan="9">Sin historial de cajas.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="mt-6 rounded border border-gray-200 bg-white p-4">
        <h2 class="font-medium">Reporte diario</h2>
        <p class="mt-1 text-sm text-gray-600">
            Selecciona un día para ver ventas, productos vendidos y movimientos de caja registrados.
        </p>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div>
                <label class="block text-sm font-medium">Día</label>
                <input type="date" class="mt-1 rounded border border-gray-300 bg-white px-3 py-2 text-sm" wire:model="reporte_fecha" />
                @error('reporte_fecha')
                    <div class="mt-1 text-xs text-red-700">{{ $message }}</div>
                @enderror
            </div>

            <button type="button" class="rounded bg-gray-900 px-3 py-2 text-sm text-white" wire:click="generarReporte">
                Ver reporte
            </button>
        </div>

        @if ($reporte_cargado)
            @php
                $res = $reporte['resumen'] ?? [];
                $ventasCantidad = (int) ($res['ventas_cantidad'] ?? 0);
                $ventasTotal = (float) ($res['ventas_total'] ?? 0);

                $movNeto = (float) ($res['mov_neto'] ?? 0);
                $movIngresos = (float) ($res['mov_ingresos'] ?? 0);
                $movEgresos = (float) ($res['mov_egresos'] ?? 0);
                $movCantidad = (int) ($res['mov_cantidad'] ?? 0);
                $productosDistintos = (int) ($res['productos_distintos'] ?? 0);
            @endphp

            <div class="mt-4 grid grid-cols-1 gap-2 text-sm md:grid-cols-4">
                <div class="rounded border border-gray-200 p-2">
                    <div class="text-xs text-gray-600">Ventas</div>
                    <div class="font-medium">$ {{ number_format($ventasTotal, 2, '.', ',') }}</div>
                    <div class="text-xs text-gray-600">{{ $ventasCantidad }} {{ $ventasCantidad === 1 ? 'venta' : 'ventas' }}</div>
                </div>
                <div class="rounded border border-gray-200 p-2">
                    <div class="text-xs text-gray-600">Movimientos (neto)</div>
                    <div class="font-medium">$ {{ number_format($movNeto, 2, '.', ',') }}</div>
                    <div class="text-xs text-gray-600">Ing: {{ number_format($movIngresos, 2, '.', ',') }} · Egr: {{ number_format($movEgresos, 2, '.', ',') }}</div>
                </div>
                <div class="rounded border border-gray-200 p-2">
                    <div class="text-xs text-gray-600">Movimientos</div>
                    <div class="font-medium">{{ $movCantidad }}</div>
                    <div class="text-xs text-gray-600">Registros del día</div>
                </div>
                <div class="rounded border border-gray-200 p-2">
                    <div class="text-xs text-gray-600">Productos vendidos</div>
                    <div class="font-medium">{{ $productosDistintos }}</div>
                    <div class="text-xs text-gray-600">Productos distintos</div>
                </div>
            </div>

            @php $totalesPago = $reporte['totales_por_pago'] ?? []; @endphp
            @if ($totalesPago !== [])
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-600">
                                <th class="py-2">Tipo de pago</th>
                                <th class="py-2">Cantidad</th>
                                <th class="py-2">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($totalesPago as $tp)
                                <tr class="border-b border-gray-100">
                                    <td class="py-2">{{ $tp['tipo_pago'] }}</td>
                                    <td class="py-2">{{ (int) ($tp['cantidad'] ?? 0) }}</td>
                                    <td class="py-2">$ {{ number_format((float) ($tp['total'] ?? 0), 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <h3 class="text-sm font-medium text-gray-800">Productos vendidos</h3>
                    @php $productos = $reporte['productos'] ?? []; @endphp
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-600">
                                    <th class="py-2">Producto</th>
                                    <th class="py-2">Cant.</th>
                                    <th class="py-2">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($productos as $p)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2">
                                            <div class="truncate">{{ $p['codigo'] }} - {{ $p['nombre'] }}</div>
                                        </td>
                                        <td class="py-2">{{ (int) ($p['cantidad'] ?? 0) }}</td>
                                        <td class="py-2">$ {{ number_format((float) ($p['total_vendido'] ?? 0), 2, '.', ',') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-3 text-gray-600" colspan="3">Sin productos vendidos ese día.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <h3 class="text-sm font-medium text-gray-800">Ventas</h3>
                    @php $ventas = $reporte['ventas'] ?? []; @endphp
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-600">
                                    <th class="py-2">Fecha</th>
                                    <th class="py-2">Venta</th>
                                    <th class="py-2">Pago</th>
                                    <th class="py-2">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ventas as $v)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2">{{ $v['fecha_venta'] }}</td>
                                        <td class="py-2">{{ $v['numero_venta'] }}</td>
                                        <td class="py-2">{{ $v['tipo_pago'] }}</td>
                                        <td class="py-2">$ {{ number_format((float) ($v['total'] ?? 0), 2, '.', ',') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-3 text-gray-600" colspan="4">Sin ventas ese día.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <h3 class="text-sm font-medium text-gray-800">Movimientos de caja</h3>
                    @php $movs = $reporte['movimientos'] ?? []; @endphp
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-600">
                                    <th class="py-2">Fecha</th>
                                    <th class="py-2">Tipo</th>
                                    <th class="py-2">Categoría</th>
                                    <th class="py-2">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movs as $m)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2">{{ $m['fecha_movimiento'] }}</td>
                                        <td class="py-2">{{ $m['tipo'] }}</td>
                                        <td class="py-2">
                                            <div class="truncate" title="{{ $m['descripcion'] }}">{{ $m['categoria'] }}</div>
                                        </td>
                                        <td class="py-2">$ {{ number_format((float) ($m['monto'] ?? 0), 2, '.', ',') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-3 text-gray-600" colspan="4">Sin movimientos ese día.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>
