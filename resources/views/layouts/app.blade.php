<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <div class="min-h-screen flex">
        <aside class="w-64 shrink-0 border-r border-gray-200 bg-white p-4">
            <div class="text-lg font-semibold">{{ config('app.name', 'Sistema') }}</div>
            <div class="mt-1 text-sm text-gray-600">Inventarios y Ventas</div>

            <nav class="mt-6 space-y-1 text-sm">
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('categorias') }}">Categorías</a>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('marcas') }}">Marcas</a>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('productos') }}">Productos</a>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('proveedores') }}">Proveedores</a>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('ordenes_compra') }}">Compras / Órdenes</a>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('recepciones') }}">Recepciones</a>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('caja') }}">Caja</a>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('ventas') }}">Ventas</a>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('alertas') }}">Alertas de reposición</a>

                <div class="pt-3 pb-1">
                    <div class="px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Configuración</div>
                </div>
                <a class="block rounded px-3 py-2 hover:bg-gray-100" href="{{ route('configuracion.tasa_cambio') }}">
                    💱 Tasa de Cambio
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
