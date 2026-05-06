<?php

namespace App\Livewire;

use App\Models\Categoria;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $dbWarning = null;

        $productosTotal = 0;
        $productosActivos = 0;
        $productosBajoMinimo = 0;
        $categoriasTotal = 0;
        $proveedoresTotal = 0;
        $ordenesPendientes = 0;
        $ventasHoyCantidad = 0;
        $ventasHoyTotal = 0.0;

        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $ventasMesCantidad = 0;
        $ventasMesTotal = 0.0;
        $topProductosMes = collect();

        $desde = now()->subDays(6)->startOfDay();
        $hasta = now()->endOfDay();

        $labels = [];
        for ($i = 0; $i < 7; $i++) {
            $labels[] = $desde->copy()->addDays($i)->format('d/m');
        }

        $ventas7d = array_map(fn (string $label) => ['label' => $label, 'value' => 0.0, 'height' => 0], $labels);

        try {
            $productosTotal = Producto::query()->count();
            $productosActivos = Producto::query()->where('activo', true)->count();
            $productosBajoMinimo = Producto::query()->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

            $categoriasTotal = Categoria::query()->count();
            $proveedoresTotal = Proveedor::query()->count();
            $ordenesPendientes = OrdenCompra::query()->where('estado', 'pendiente')->count();

            $inicioHoy = now()->startOfDay();
            $finHoy = now()->endOfDay();
            $ventasHoyCantidad = Venta::query()->whereBetween('fecha_venta', [$inicioHoy, $finHoy])->count();
            $ventasHoyTotal = (float) (Venta::query()->whereBetween('fecha_venta', [$inicioHoy, $finHoy])->sum('total') ?? 0);

            $ventasMesCantidad = Venta::query()->whereBetween('fecha_venta', [$inicioMes, $finMes])->count();
            $ventasMesTotal = (float) (Venta::query()->whereBetween('fecha_venta', [$inicioMes, $finMes])->sum('total') ?? 0);

            $topProductosMes = VentaDetalle::query()
                ->join('ventas', 'venta_detalles.venta_id', '=', 'ventas.id')
                ->join('productos', 'venta_detalles.producto_id', '=', 'productos.id')
                ->whereBetween('ventas.fecha_venta', [$inicioMes, $finMes])
                ->selectRaw('productos.id as producto_id, productos.codigo as codigo, productos.nombre as nombre')
                ->selectRaw('SUM(venta_detalles.cantidad) as cantidad')
                ->selectRaw('SUM(venta_detalles.subtotal) as total_vendido')
                ->groupBy('productos.id', 'productos.codigo', 'productos.nombre')
                ->orderByDesc('cantidad')
                ->limit(5)
                ->get();

            /** @var \Illuminate\Support\Collection<string, float|int|string> $ventasPorDia */
            $ventasPorDia = Venta::query()
                ->whereBetween('fecha_venta', [$desde, $hasta])
                ->selectRaw('DATE(fecha_venta) as fecha, COALESCE(SUM(total), 0) as total')
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->pluck('total', 'fecha');

            $valores = [];
            for ($i = 0; $i < 7; $i++) {
                $fecha = $desde->copy()->addDays($i)->toDateString();
                $valores[] = (float) ($ventasPorDia[$fecha] ?? 0);
            }

            $max = max($valores) ?: 1;
            $ventas7d = [];
            foreach ($valores as $idx => $valor) {
                $ventas7d[] = [
                    'label' => $labels[$idx],
                    'value' => $valor,
                    'height' => (int) round(($valor / $max) * 100),
                ];
            }
        } catch (\Throwable $e) {
            $dbWarning = 'No se pudieron cargar las métricas (base de datos no disponible o sin migraciones).';
        }

        return view('livewire.dashboard', [
            'dbWarning' => $dbWarning,
            'productosTotal' => $productosTotal,
            'productosActivos' => $productosActivos,
            'productosBajoMinimo' => $productosBajoMinimo,
            'categoriasTotal' => $categoriasTotal,
            'proveedoresTotal' => $proveedoresTotal,
            'ordenesPendientes' => $ordenesPendientes,
            'ventasHoyCantidad' => $ventasHoyCantidad,
            'ventasHoyTotal' => $ventasHoyTotal,
            'ventasMesCantidad' => $ventasMesCantidad,
            'ventasMesTotal' => $ventasMesTotal,
            'topProductosMes' => $topProductosMes,
            'mesLabel' => $inicioMes->format('m/Y'),
            'ventas7d' => $ventas7d,
        ]);
    }
}
