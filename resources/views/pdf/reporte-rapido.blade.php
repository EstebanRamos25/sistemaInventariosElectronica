<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>{{ $tipoReporte }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #111;
            background: #fff;
            line-height: 1.45;
        }

        /* ── Encabezado ───────────────────────────────────────────────── */
        .header {
            border-bottom: 3px solid #1e293b;
            padding-bottom: 10px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .header-left h1 {
            font-size: 15pt;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: -0.3px;
        }

        .header-left .subtitle {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }

        .header-right {
            text-align: right;
            font-size: 7.5pt;
            color: #64748b;
        }

        .header-right .fecha {
            font-size: 8pt;
            color: #1e293b;
            font-weight: 600;
        }

        /* ── Resumen rápido ───────────────────────────────────────────── */
        .resumen {
            display: flex;
            gap: 0;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .resumen-item {
            flex: 1;
            padding: 7px 10px;
            border-right: 1px solid #e2e8f0;
            text-align: center;
        }

        .resumen-item:last-child { border-right: none; }

        .resumen-valor {
            font-size: 13pt;
            font-weight: 800;
            color: #1e293b;
            display: block;
        }

        .resumen-item.danger .resumen-valor { color: #dc2626; }

        .resumen-etiqueta {
            font-size: 6.5pt;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* ── Sección de marca ─────────────────────────────────────────── */
        .marca-block {
            margin-bottom: 16px;
            break-inside: avoid;
        }

        .marca-header {
            background: #1e293b;
            color: #fff;
            padding: 5px 9px;
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .marca-header .marca-count {
            font-size: 7.5pt;
            font-weight: 400;
            opacity: 0.7;
        }

        /* ── Tabla de productos por marca ─────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        thead th {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 8px;
            text-align: left;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #cbd5e1;
        }

        thead th.right { text-align: right; }

        tbody td {
            padding: 4px 8px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) td { background: #f8fafc; }

        td.code {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 7.5pt;
            color: #1e293b;
            font-weight: 700;
            white-space: nowrap;
            width: 22%;
        }

        td.nombre {
            width: 58%;
        }

        td.nombre .desc {
            font-size: 7pt;
            color: #94a3b8;
            margin-top: 1px;
        }

        td.stock {
            text-align: right;
            width: 20%;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        td.stock.low {
            color: #dc2626;
        }

        td.stock.ok {
            color: #16a34a;
        }

        /* ── Pie ──────────────────────────────────────────────────────── */
        .footer {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    {{-- ── ENCABEZADO ─────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-left">
            <h1>{{ $tipoReporte }}</h1>
            <div class="subtitle">
                ELECTRÓNICA ERLAN — Reporte Rápido de Inventario
                @if ($marca) · Solo marca: <strong>{{ $marca->nombre }}</strong> @endif
            </div>
        </div>
        <div class="header-right">
            <div class="fecha">{{ $generadoEn }}</div>
            <div>{{ $stats['total'] }} producto(s)</div>
        </div>
    </div>

    {{-- ── RESUMEN ─────────────────────────────────────────────────────────── --}}
    <div class="resumen">
        <div class="resumen-item">
            <span class="resumen-valor">{{ $stats['total'] }}</span>
            <div class="resumen-etiqueta">Productos</div>
        </div>
        <div class="resumen-item">
            <span class="resumen-valor">{{ number_format($stats['stock_total']) }}</span>
            <div class="resumen-etiqueta">Stock total</div>
        </div>
        <div class="resumen-item {{ $stats['bajo_minimo'] > 0 ? 'danger' : '' }}">
            <span class="resumen-valor">{{ $stats['bajo_minimo'] }}</span>
            <div class="resumen-etiqueta">Bajo mínimo</div>
        </div>
        @if (!$marca)
        <div class="resumen-item">
            <span class="resumen-valor">{{ $marcas->count() }}</span>
            <div class="resumen-etiqueta">Marcas</div>
        </div>
        @endif
    </div>

    {{-- ── PRODUCTOS AGRUPADOS POR MARCA ──────────────────────────────────── --}}
    @foreach ($marcas as $marcaNombre => $productosGrupo)
        <div class="marca-block">
            <div class="marca-header">
                <span>{{ $marcaNombre }}</span>
                <span class="marca-count">{{ $productosGrupo->count() }} producto(s)</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width:22%">Código</th>
                        <th style="width:58%">Nombre / Descripción</th>
                        <th class="right" style="width:20%">Stock actual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productosGrupo as $p)
                        @php
                            $bajo = (int) $p->stock_actual <= (int) $p->stock_minimo;
                            // Descripción limpia (sin "Ref. vieja: XXXX | ")
                            $descLimpia = $p->descripcion
                                ? trim(preg_replace('/\|\s*Ref\. vieja:\s*\d+|Ref\. vieja:\s*\d+/', '', $p->descripcion))
                                : null;
                        @endphp
                        <tr>
                            <td class="code">{{ $p->codigo }}</td>
                            <td class="nombre">
                                {{ $p->nombre }}
                                @if ($descLimpia)
                                    <div class="desc">{{ $descLimpia }}</div>
                                @endif
                            </td>
                            <td class="stock {{ $bajo ? 'low' : 'ok' }}">
                                {{ $p->stock_actual }}
                                @if ($p->unidades_por_empaque)
                                    <span style="font-weight:400; font-size:6.5pt; color:#94a3b8">/ {{ $p->unidades_por_empaque }}B</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @if ($productos->isEmpty())
        <div style="text-align:center; padding:24px; color:#94a3b8; font-size:9pt;">
            No se encontraron productos con los filtros aplicados.
        </div>
    @endif

    {{-- ── PIE ────────────────────────────────────────────────────────────── --}}
    <div class="footer">
        <div>ELECTRÓNICA ERLAN — Sistema de Inventarios</div>
        <div>{{ $generadoEn }}</div>
        <div>{{ $stats['total'] }} producto(s)</div>
    </div>

</body>
</html>
