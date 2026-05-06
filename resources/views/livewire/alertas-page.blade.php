<div>
    <h1 class="text-2xl font-semibold">Alertas de reposición</h1>
    <p class="mt-1 text-sm text-gray-600">Pendientes y resueltas (se resuelven automáticamente al recepcionar stock).</p>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded border border-gray-200 bg-white p-4">
            <h2 class="font-medium">Pendientes</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Producto</th>
                            <th class="py-2">Stock</th>
                            <th class="py-2">Mínimo</th>
                            <th class="py-2">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendientes as $a)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">
                                    <div class="font-medium">{{ $a->producto?->codigo }}</div>
                                    <div class="text-gray-700">{{ $a->producto?->nombre }}</div>
                                </td>
                                <td class="py-2">{{ $a->stock_actual }}</td>
                                <td class="py-2">{{ $a->stock_minimo }}</td>
                                <td class="py-2">{{ $a->fecha_alerta }}</td>
                            </tr>
                        @endforeach
                        @if ($pendientes->isEmpty())
                            <tr>
                                <td class="py-3 text-gray-600" colspan="4">Sin alertas pendientes.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded border border-gray-200 bg-white p-4">
            <h2 class="font-medium">Resueltas (últimas 50)</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600">
                            <th class="py-2">Producto</th>
                            <th class="py-2">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resueltas as $a)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">
                                    <div class="font-medium">{{ $a->producto?->codigo }}</div>
                                    <div class="text-gray-700">{{ $a->producto?->nombre }}</div>
                                </td>
                                <td class="py-2">{{ $a->fecha_alerta }}</td>
                            </tr>
                        @endforeach
                        @if ($resueltas->isEmpty())
                            <tr>
                                <td class="py-3 text-gray-600" colspan="2">Sin alertas resueltas todavía.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
