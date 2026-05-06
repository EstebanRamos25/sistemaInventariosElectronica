<div>
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="mt-1 text-sm text-gray-600">Resumen rápido del estado del sistema.</p>

    @if (!empty($dbWarning))
        <div class="mt-4 rounded border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-900">
            {{ $dbWarning }}
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded border border-gray-200 bg-white p-4">
            <div class="text-sm text-gray-600">Ventas hoy</div>
            <div class="mt-1 text-2xl font-semibold">$ {{ number_format($ventasHoyTotal, 2, '.', ',') }}</div>
            <div class="mt-1 text-sm text-gray-600">{{ $ventasHoyCantidad }} {{ $ventasHoyCantidad === 1 ? 'venta' : 'ventas' }}</div>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4">
            <div class="text-sm text-gray-600">Ventas del mes ({{ $mesLabel }})</div>
            <div class="mt-1 text-2xl font-semibold">$ {{ number_format($ventasMesTotal, 2, '.', ',') }}</div>
            <div class="mt-1 text-sm text-gray-600">{{ $ventasMesCantidad }} {{ $ventasMesCantidad === 1 ? 'venta' : 'ventas' }}</div>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4">
            <div class="text-sm text-gray-600">Productos</div>
            <div class="mt-1 text-2xl font-semibold">{{ $productosTotal }}</div>
            <div class="mt-1 text-sm text-gray-600">Activos: {{ $productosActivos }}</div>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4">
            <div class="text-sm text-gray-600">Bajo stock mínimo</div>
            <div class="mt-1 text-2xl font-semibold">{{ $productosBajoMinimo }}</div>
            <div class="mt-1 text-sm text-gray-600">Revisar en Alertas</div>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4">
            <div class="text-sm text-gray-600">Órdenes pendientes</div>
            <div class="mt-1 text-2xl font-semibold">{{ $ordenesPendientes }}</div>
            <div class="mt-1 text-sm text-gray-600">Proveedores: {{ $proveedoresTotal }}</div>
        </div>
    </div>

    <section class="mt-6 rounded border border-gray-200 bg-white p-4">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="font-medium">Top 5 productos del mes</h2>
                <p class="mt-1 text-sm text-gray-600">Ordenado por cantidad vendida ({{ $mesLabel }}).</p>
            </div>

            <div class="text-sm text-gray-600">Categorías: {{ $categoriasTotal }}</div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600">
                        <th class="py-2">Código</th>
                        <th class="py-2">Producto</th>
                        <th class="py-2">Cantidad</th>
                        <th class="py-2">Total vendido</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topProductosMes as $row)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 font-medium">{{ $row->codigo }}</td>
                            <td class="py-2">{{ $row->nombre }}</td>
                            <td class="py-2">{{ (int) $row->cantidad }}</td>
                            <td class="py-2">$ {{ number_format((float) $row->total_vendido, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                    @if ($topProductosMes->isEmpty())
                        <tr>
                            <td class="py-3 text-gray-600" colspan="4">Sin ventas registradas este mes.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-6 rounded border border-gray-200 bg-white p-4">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="font-medium">Ventas últimos 7 días</h2>
                <p class="mt-1 text-sm text-gray-600">Total diario (escala relativa).</p>
            </div>
        </div>

        <div class="mt-4">
            <div class="flex h-32 items-end gap-2">
                @foreach ($ventas7d as $bar)
                    <div class="flex-1">
                        <div class="flex h-24 items-end">
                            <div class="w-full rounded bg-gray-900" style="height: {{ $bar['height'] }}%"></div>
                        </div>
                        <div class="mt-1 text-center text-xs text-gray-600">{{ $bar['label'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 text-sm text-gray-700">
                Total 7 días: <span class="font-medium">$ {{ number_format(collect($ventas7d)->sum('value'), 2, '.', ',') }}</span>
            </div>
        </div>
    </section>
</div>
