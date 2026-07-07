<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Actualiza la base de datos con los datos del nuevo Excel
 * "INVENTARIO 2026 1 (1).xlsx".
 *
 * Estrategia:
 *  1. Lee el JSON generado por el script Python (productos_nuevo.json).
 *  2. Para cada producto del JSON, busca el producto en BD por su código viejo
 *     (guardado en el campo descripcion como "Ref. vieja: XXXX").
 *  3. Actualiza los campos que cambiaron: stock_actual, precio_venta, precio_compra,
 *     nombre, pulgadas_tv, leds_por_barra, unidades_por_empaque.
 *  4. Inserta los productos completamente nuevos (que no tienen "Ref. vieja" en BD).
 *
 * NO elimina productos existentes.
 * NO modifica el código descriptivo nuevo (LG-32IN-3B-7LED) que ya se asignó.
 *
 * Uso:
 *   php artisan db:seed --class=ActualizarInventarioNuevoExcelSeeder
 */
class ActualizarInventarioNuevoExcelSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/data/productos_nuevo.json');

        if (! file_exists($dataPath)) {
            $this->command->error("No se encontró: {$dataPath}");
            $this->command->info("Ejecuta primero:");
            $this->command->info('  PYTHONPATH=".venv/lib/python3.13/site-packages" .venv/bin/python3 scripts/extraer_productos_excel.py --excel "INVENTARIO 2026 1 (1).xlsx" --output database/seeders/data/productos_nuevo.json');
            return;
        }

        $jsonData = json_decode(file_get_contents($dataPath), true);
        $total    = count($jsonData);
        $this->command->info("Procesando {$total} productos del nuevo Excel...");

        // ── Construir índice BD: codigoViejo → producto ────────────────────
        // Los productos importados tienen "Ref. vieja: XXXX" en la descripción
        $this->command->line("Construyendo índice de productos existentes...");
        $indexPorRefVieja = [];

        Producto::whereNotNull('descripcion')
            ->where('descripcion', 'like', 'Ref. vieja:%')
            ->each(function ($p) use (&$indexPorRefVieja) {
                if (preg_match('/Ref\. vieja:\s*(\d+)/', $p->descripcion, $m)) {
                    $indexPorRefVieja[(int) $m[1]] = $p;
                }
            });

        $this->command->line("Productos con Ref. vieja en BD: " . count($indexPorRefVieja));

        // ── Marcas y categorías ────────────────────────────────────────────
        $marcaCache      = Marca::pluck('id', 'nombre')->toArray();
        $categoriaDefault = Categoria::where('nombre', 'Barras LED TV')->first()?->id
            ?? Categoria::first()?->id;

        $contadores = [
            'actualizados' => 0,
            'nuevos'       => 0,
            'sin_cambios'  => 0,
            'errores'      => 0,
        ];

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($jsonData as $row) {
                $codigoViejo = (int) $row['codigo'];

                if (isset($indexPorRefVieja[$codigoViejo])) {
                    // ── Producto EXISTENTE: actualizar campos que cambiaron ──
                    $producto = $indexPorRefVieja[$codigoViejo];
                    $changed  = false;

                    // Stock
                    if ((int) $producto->stock_actual !== (int) $row['stock_actual']) {
                        $producto->stock_actual = (int) $row['stock_actual'];
                        $changed = true;
                    }

                    // Precio venta
                    if (round((float) $producto->precio_venta, 2) !== round((float) $row['precio_venta'], 2)) {
                        $producto->precio_venta = (float) $row['precio_venta'];
                        $changed = true;
                    }

                    // Precio compra
                    if (round((float) $producto->precio_compra, 2) !== round((float) $row['precio_compra'], 2)) {
                        $producto->precio_compra = (float) $row['precio_compra'];
                        $changed = true;
                    }

                    // Nombre (si cambió)
                    if ($producto->nombre !== $row['nombre']) {
                        $producto->nombre = $row['nombre'];
                        $changed = true;
                    }

                    // Datos técnicos mejorados
                    if ($producto->pulgadas_tv !== $row['pulgadas_tv']) {
                        $producto->pulgadas_tv = $row['pulgadas_tv'];
                        $changed = true;
                    }

                    if ($producto->leds_por_barra !== $row['leds_por_barra']) {
                        $producto->leds_por_barra = $row['leds_por_barra'];
                        $changed = true;
                    }

                    if ($producto->unidades_por_empaque !== $row['unidades_por_empaque']) {
                        $producto->unidades_por_empaque = $row['unidades_por_empaque'];
                        $changed = true;
                    }

                    if ($changed) {
                        $producto->save();
                        $contadores['actualizados']++;
                    } else {
                        $contadores['sin_cambios']++;
                    }

                } else {
                    // ── Producto NUEVO: insertar en BD ──────────────────────
                    $marcaNombre = strtoupper(trim((string) $row['marca']));

                    // Crear marca si no existe
                    if (! isset($marcaCache[$marcaNombre])) {
                        $marca = Marca::firstOrCreate(['nombre' => $marcaNombre]);
                        $marcaCache[$marcaNombre] = $marca->id;
                    }

                    $marcaId = $marcaCache[$marcaNombre];

                    // Generar código descriptivo nuevo para este producto
                    $nuevoCodigo = $this->generarCodigo(
                        marcaNombre:  $marcaNombre,
                        pulgadas:     $row['pulgadas_tv'],
                        barras:       $row['unidades_por_empaque'],
                        leds:         $row['leds_por_barra'],
                    );

                    $descripcion = "Ref. vieja: {$codigoViejo}";

                    Producto::create([
                        'codigo'               => $nuevoCodigo,
                        'nombre'               => $row['nombre'],
                        'descripcion'          => $descripcion,
                        'marca_id'             => $marcaId,
                        'categoria_id'         => $categoriaDefault,
                        'modelo_tv'            => $row['modelo_tv'] ?? null,
                        'pulgadas_tv'          => $row['pulgadas_tv'],
                        'voltaje_led'          => $row['voltaje_led'] ?? null,
                        'leds_por_barra'       => $row['leds_por_barra'],
                        'caracteristicas_barra' => $row['caracteristicas_barra'] ?? null,
                        'unidad'               => $row['unidad'] ?? 'juego',
                        'empaque'              => $row['empaque'] ?? null,
                        'unidades_por_empaque' => $row['unidades_por_empaque'],
                        'precio_compra'        => (float) $row['precio_compra'],
                        'precio_venta'         => (float) $row['precio_venta'],
                        'stock_actual'         => (int) $row['stock_actual'],
                        'stock_minimo'         => (int) ($row['stock_minimo'] ?? 2),
                        'stock_ideal'          => (int) ($row['stock_ideal'] ?? 10),
                        'tiempo_reposicion_dias' => (int) ($row['tiempo_reposicion_dias'] ?? 7),
                        'activo'               => true,
                    ]);

                    $contadores['nuevos']++;
                }

                $bar->advance();
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            $bar->finish();
            $this->command->newLine(2);
            $this->command->error("Error: " . $e->getMessage());
            $this->command->error("En: " . $e->getFile() . ':' . $e->getLine());
            return;
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->command->table(
            ['Resultado', 'Cantidad'],
            [
                ['✓ Actualizados (con cambios)',  $contadores['actualizados']],
                ['  Sin cambios',                  $contadores['sin_cambios']],
                ['★ Nuevos insertados',            $contadores['nuevos']],
                ['✗ Errores',                      $contadores['errores']],
                ['TOTAL procesados',               $total],
            ]
        );

        // Mostrar los nuevos insertados
        if ($contadores['nuevos'] > 0) {
            $this->command->newLine();
            $this->command->info("Productos nuevos insertados:");
            Producto::where('descripcion', 'like', 'Ref. vieja:%')
                ->orderByDesc('id')
                ->take($contadores['nuevos'])
                ->get()
                ->each(function ($p) {
                    $this->command->line(
                        "  [{$p->codigo}] {$p->nombre} | Stock: {$p->stock_actual} | \${$p->precio_venta}"
                    );
                });
        }

        $this->command->newLine();
        $this->command->info("✓ Actualización completada. Total en BD: " . Producto::count());
    }

    /**
     * Genera código descriptivo: MARCA-{PULGADAS}IN-{BARRAS}B-{LEDS}LED
     * Resuelve colisiones con sufijo numérico.
     */
    private function generarCodigo(
        ?string $marcaNombre,
        ?int    $pulgadas,
        ?int    $barras,
        ?int    $leds,
    ): string {
        $parts = [];

        if ($marcaNombre && trim($marcaNombre) !== '') {
            $parts[] = strtoupper(
                preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '', $marcaNombre))
            );
        }

        if ($pulgadas && $pulgadas > 0) {
            $parts[] = $pulgadas . 'IN';
        }

        if ($barras && $barras > 0) {
            $parts[] = $barras . 'B';
        }

        if ($leds && $leds > 0) {
            $parts[] = $leds . 'LED';
        }

        if ($parts === []) {
            $parts[] = 'PROD';
        }

        $base      = substr(implode('-', $parts), 0, 60);
        $candidate = $base;

        for ($i = 1; $i <= 999; $i++) {
            if (! Producto::where('codigo', $candidate)->exists()) {
                return $candidate;
            }
            $candidate = $base . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        return $base . '-' . strtoupper(Str::random(4));
    }
}
