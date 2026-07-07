<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductosPdfController extends Controller
{
    /**
     * Genera un PDF con el reporte de productos.
     *
     * Query params:
     *   marca_id (int|null)  — si se pasa, filtra por esa marca
     *   q        (string)    — si se pasa, filtra por búsqueda de texto
     *   tipo     (string)    — 'completo' (default) | 'rapido'
     */
    public function __invoke(Request $request): Response
    {
        $marcaId = $request->integer('marca_id', 0) ?: null;
        $search  = trim($request->string('q', ''));
        $tipo    = $request->input('tipo', 'completo');

        /** @var Marca|null $marca */
        $marca = $marcaId ? Marca::find($marcaId) : null;

        $productos = Producto::query()
            ->with(['categoria', 'marca'])
            ->when($marca, fn ($q) => $q->where('marca_id', $marca->id))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('codigo', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%")
                        ->orWhere('modelo_tv', 'like', "%{$search}%");
                });
            })
            ->orderBy('marca_id')
            ->orderBy('nombre')
            ->get();

        // Estadísticas para el encabezado
        $stats = [
            'total'            => $productos->count(),
            'activos'          => $productos->where('activo', true)->count(),
            'stock_total'      => $productos->sum('stock_actual'),
            'valor_inventario' => $productos->sum(fn ($p) => $p->precio_compra * max(0, $p->stock_actual)),
            'valor_venta'      => $productos->sum(fn ($p) => $p->precio_venta * max(0, $p->stock_actual)),
            'bajo_minimo'      => $productos->filter(fn ($p) => $p->stock_actual <= $p->stock_minimo)->count(),
        ];

        // Título del reporte
        $tipoReporte = $marca
            ? "Inventario — {$marca->nombre}"
            : ($search !== '' ? "Búsqueda: \"{$search}\"" : 'Inventario General');

        $generadoEn = now()->format('d/m/Y H:i');

        if ($tipo === 'rapido') {
            return $this->generarRapido($productos, $marca, $search, $tipoReporte, $stats, $generadoEn);
        }

        return $this->generarCompleto($productos, $marca, $search, $tipoReporte, $stats, $generadoEn);
    }

    private function generarCompleto($productos, $marca, $search, $tipoReporte, $stats, $generadoEn): Response
    {
        $pdf = Pdf::loadView('pdf.reporte-productos', [
            'productos'   => $productos,
            'marca'       => $marca,
            'search'      => $search,
            'tipoReporte' => $tipoReporte,
            'stats'       => $stats,
            'generadoEn'  => $generadoEn,
        ])->setPaper('a4', 'landscape');

        $filename = 'reporte-completo-' . now()->format('Ymd-Hi') . '.pdf';

        return $pdf->download($filename);
    }

    private function generarRapido($productos, $marca, $search, $tipoReporte, $stats, $generadoEn): Response
    {
        // Agrupar por nombre de marca para la vista de reporte rápido
        $marcas = $productos
            ->groupBy(fn ($p) => $p->marca?->nombre ?? '(Sin marca)')
            ->sortKeys();

        $pdf = Pdf::loadView('pdf.reporte-rapido', [
            'productos'   => $productos,
            'marcas'      => $marcas,
            'marca'       => $marca,
            'search'      => $search,
            'tipoReporte' => $tipoReporte . ' — Rápido',
            'stats'       => $stats,
            'generadoEn'  => $generadoEn,
        ])->setPaper('a4', 'portrait');

        $filename = 'reporte-rapido-' . now()->format('Ymd-Hi') . '.pdf';

        return $pdf->download($filename);
    }
}
