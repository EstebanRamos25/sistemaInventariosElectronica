<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $tipoReporte }}</title>
    <style>
        /* ── Reset & Base ─────────────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.5pt;
            color: #1a1a2e;
            background: #fff;
            line-height: 1.4;
        }

        /* ── Portada / Encabezado ─────────────────────────────────────────── */
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: #fff;
            padding: 18px 24px 14px;
            margin-bottom: 14px;
            border-radius: 4px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-brand {
            font-size: 11pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #e2e8f0;
        }

        .header-brand small {
            display: block;
            font-size: 7.5pt;
            font-weight: 400;
            color: #94a3b8;
            margin-top: 2px;
        }

        .header-meta {
            text-align: right;
            font-size: 7.5pt;
            color: #94a3b8;
        }

        .header-title {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.15);
        }

        .header-title h1 {
            font-size: 14pt;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.3px;
        }

        .header-title .subtitle {
            font-size: 8.5pt;
            color: #93c5fd;
            margin-top: 2px;
        }

        /* ── KPI Cards ────────────────────────────────────────────────────── */
        .kpi-row {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
        }

        .kpi-card {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
        }

        .kpi-card.highlight {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .kpi-card.warning {
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .kpi-card.danger {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .kpi-value {
            font-size: 13pt;
            font-weight: 700;
            color: #1e293b;
            display: block;
            line-height: 1.1;
        }

        .kpi-card.highlight .kpi-value { color: #1d4ed8; }
        .kpi-card.warning .kpi-value   { color: #c2410c; }
        .kpi-card.danger .kpi-value    { color: #dc2626; }

        .kpi-label {
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* ── Tabla ────────────────────────────────────────────────────────── */
        .section-title {
            font-size: 9pt;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 2px solid #1d4ed8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }

        thead tr {
            background: #1e293b;
            color: #f1f5f9;
        }

        thead th {
            padding: 6px 7px;
            text-align: left;
            font-weight: 600;
            font-size: 7pt;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        thead th.right { text-align: right; }
        thead th.center { text-align: center; }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #f1f5f9;
        }

        /* Fila de separación de marca */
        .marca-separator td {
            background: #e2e8f0;
            color: #334155;
            font-weight: 700;
            font-size: 8pt;
            padding: 5px 7px;
            letter-spacing: 0.3px;
        }

        tbody td {
            padding: 5px 7px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        td.right { text-align: right; font-variant-numeric: tabular-nums; }
        td.center { text-align: center; }
        td.mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 7pt; }
        td.bold { font-weight: 700; color: #1e293b; }

        /* Stock bajo mínimo */
        .stock-low {
            color: #dc2626;
            font-weight: 700;
        }

        /* Badge de estado */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 6.5pt;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        .badge-active {
            background: #dcfce7;
            color: #16a34a;
        }
        .badge-inactive {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* ── Pie de página ────────────────────────────────────────────────── */
        .footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            color: #94a3b8;
        }

        /* ── Totales de tabla ─────────────────────────────────────────────── */
        tfoot tr {
            background: #1e293b;
            color: #f8fafc;
        }

        tfoot td {
            padding: 6px 7px;
            font-weight: 700;
            border: none;
        }

        /* ── Page break ───────────────────────────────────────────────────── */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

    {{-- ── ENCABEZADO ──────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-top">
            <div class="header-brand">
                ELECTRÓNICA ERLAN
                <small>Sistema de Control de Inventarios</small>
            </div>
            <div class="header-meta">
                Generado el {{ $generadoEn }}<br/>
                Total de registros: {{ $stats['total'] }}
            </div>
        </div>
        <div class="header-title">
            <h1>{{ $tipoReporte }}</h1>
            <div class="subtitle">
                @if ($marca)
                    Catálogo completo de productos para la marca <strong style="color:#fff">{{ $marca->nombre }}</strong>
                @elseif ($search !== '')
                    Resultados filtrados por búsqueda: "{{ $search }}"
                @else
                    Reporte general del inventario de barras LED TV — todas las marcas
                @endif
            </div>
        </div>
    </div>

    {{-- ── KPI CARDS ────────────────────────────────────────────────────────── --}}
    <div class="kpi-row">
        <div class="kpi-card">
            <span class="kpi-value">{{ $stats['total'] }}</span>
            <div class="kpi-label">Productos totales</div>
        </div>
        <div class="kpi-card highlight">
            <span class="kpi-value">{{ $stats['activos'] }}</span>
            <div class="kpi-label">Activos</div>
        </div>
        <div class="kpi-card">
            <span class="kpi-value">{{ number_format($stats['stock_total']) }}</span>
            <div class="kpi-label">Stock total (juegos)</div>
        </div>
        <div class="kpi-card warning">
            <span class="kpi-value">$ {{ number_format($stats['valor_inventario'], 2) }}</span>
            <div class="kpi-label">Valor costo inventario</div>
        </div>
        <div class="kpi-card highlight">
            <span class="kpi-value">$ {{ number_format($stats['valor_venta'], 2) }}</span>
            <div class="kpi-label">Valor venta inventario</div>
        </div>
        @if ($stats['bajo_minimo'] > 0)
        <div class="kpi-card danger">
            <span class="kpi-value">{{ $stats['bajo_minimo'] }}</span>
            <div class="kpi-label">Bajo stock mínimo</div>
        </div>
        @else
        <div class="kpi-card">
            <span class="kpi-value" style="color:#16a34a">✓ 0</span>
            <div class="kpi-label">Bajo stock mínimo</div>
        </div>
        @endif
    </div>

    {{-- ── TABLA DE PRODUCTOS ───────────────────────────────────────────────── --}}
    <div class="section-title">
        Detalle de Productos
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:12%">Código</th>
                <th style="width:24%">Nombre / Descripción</th>
                @if (!$marca)
                <th style="width:8%">Marca</th>
                @endif
                <th style="width:7%">Categoría</th>
                <th class="center" style="width:5%">Pulgadas</th>
                <th class="center" style="width:4%">Barras</th>
                <th class="right" style="width:7%">P. Compra</th>
                <th class="right" style="width:7%">P. Venta</th>
                <th class="right" style="width:5%">Stock</th>
                <th class="right" style="width:5%">Mín.</th>
                <th class="center" style="width:7%">Estado</th>
                @if (!$marca)
                <th style="width:9%">Ref. Vieja</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php
                $currentMarca = null;
                $rowNum = 0;
            @endphp

            @foreach ($productos as $p)
                @php
                    $marcaNombre = $p->marca?->nombre ?? '—';
                    $rowNum++;
                    $stockBajo = (int) $p->stock_actual <= (int) $p->stock_minimo;

                    // Extraer ref vieja de la descripción
                    $refVieja = null;
                    if ($p->descripcion && preg_match('/Ref\. vieja:\s*(\d+)/', $p->descripcion, $m)) {
                        $refVieja = $m[1];
                    }

                    // Extraer descripción sin la ref vieja
                    $descLimpia = $p->descripcion
                        ? trim(preg_replace('/\|\s*Ref\. vieja:\s*\d+|Ref\. vieja:\s*\d+/', '', $p->descripcion))
                        : null;
                @endphp

                {{-- Separador de marca (solo en reporte general) --}}
                @if (!$marca && $marcaNombre !== $currentMarca)
                    @php $currentMarca = $marcaNombre; @endphp
                    <tr class="marca-separator">
                        <td colspan="{{ $marca ? 10 : 12 }}">{{ strtoupper($marcaNombre) }}</td>
                    </tr>
                @endif

                <tr>
                    <td class="mono bold">{{ $p->codigo }}</td>
                    <td>
                        <span style="font-weight:600; color:#1e293b">{{ $p->nombre }}</span>
                        @if ($descLimpia)
                            <br/><span style="font-size:6.5pt; color:#6b7280">{{ $descLimpia }}</span>
                        @endif
                    </td>
                    @if (!$marca)
                    <td style="color:#4b5563; font-weight:600">{{ $marcaNombre }}</td>
                    @endif
                    <td style="color:#6b7280">{{ $p->categoria?->nombre ?? '—' }}</td>
                    <td class="center">{{ $p->pulgadas_tv ? $p->pulgadas_tv . '"' : '—' }}</td>
                    <td class="center">{{ $p->unidades_por_empaque ?? '—' }}</td>
                    <td class="right">$ {{ number_format((float)$p->precio_compra, 2) }}</td>
                    <td class="right" style="font-weight:600">$ {{ number_format((float)$p->precio_venta, 2) }}</td>
                    <td class="right {{ $stockBajo ? 'stock-low' : '' }}">{{ $p->stock_actual }}</td>
                    <td class="right" style="color:#94a3b8">{{ $p->stock_minimo }}</td>
                    <td class="center">
                        <span class="badge {{ $p->activo ? 'badge-active' : 'badge-inactive' }}">
                            {{ $p->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    @if (!$marca)
                    <td class="mono" style="color:#94a3b8; font-size:6.5pt">{{ $refVieja }}</td>
                    @endif
                </tr>
            @endforeach

            @if ($productos->isEmpty())
                <tr>
                    <td colspan="{{ $marca ? 10 : 12 }}" style="text-align:center; padding:16px; color:#94a3b8">
                        No se encontraron productos con los filtros aplicados.
                    </td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ $marca ? 7 : 8 }}" style="color:#93c5fd">TOTALES</td>
                <td class="right">$ {{ number_format($productos->sum(fn($p) => $p->precio_compra * max(0, $p->stock_actual)), 2) }}</td>
                <td class="right">$ {{ number_format($productos->sum(fn($p) => $p->precio_venta * max(0, $p->stock_actual)), 2) }}</td>
                <td class="right">{{ number_format($productos->sum('stock_actual')) }}</td>
                <td colspan="{{ $marca ? 2 : 3 }}"></td>
            </tr>
        </tfoot>
    </table>

    {{-- ── PIE DE PÁGINA ────────────────────────────────────────────────────── --}}
    <div class="footer">
        <div>ELECTRÓNICA ERLAN — Sistema de Inventarios</div>
        <div>{{ $tipoReporte }} — {{ $generadoEn }}</div>
        <div>{{ $stats['total'] }} producto(s) en este reporte</div>
    </div>

</body>
</html>
