<?php

namespace Database\Seeders;

use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Corrige los 283 productos ya importados desde el Excel:
 *
 *  1. Genera un nuevo código descriptivo automático (MARCA-PULGADAS-BARRAS-LEDS)
 *     y lo asigna como `codigo` del producto.
 *
 *  2. Guarda el código viejo numérico en el campo `descripcion`
 *     con el prefijo "Ref. vieja: XXXX" para trazabilidad histórica.
 *
 *  3. Restaura los stocks negativos reales del Excel que antes se habían
 *     forzado a 0 durante la importación inicial.
 *
 * CONDICIÓN: Solo afecta productos cuyo campo `codigo` sea puramente numérico
 * (los que vienen del Excel). No toca productos creados manualmente.
 *
 * Uso:
 *   php artisan db:seed --class=CorregirCodigosProductosSeeder
 */
class CorregirCodigosProductosSeeder extends Seeder
{
    public function run(): void
    {
        // ── Cargar JSON de referencia (con stocks negativos reales) ───────────
        $dataPath = database_path('seeders/data/productos_importacion.json');

        if (! file_exists($dataPath)) {
            $this->command->error("No se encontró: {$dataPath}");
            $this->command->info("Ejecuta primero: .venv/bin/python3 scripts/extraer_productos_excel.py");
            return;
        }

        $jsonData = json_decode(file_get_contents($dataPath), true);

        // Índice rápido por código viejo → datos del Excel
        $porCodigoViejo = [];
        foreach ($jsonData as $row) {
            $porCodigoViejo[(string) $row['codigo']] = $row;
        }

        // ── Obtener productos con código puramente numérico (del Excel) ───────
        $productosDelExcel = Producto::with('marca')
            ->get()
            ->filter(fn ($p) => ctype_digit((string) $p->codigo));

        $total = $productosDelExcel->count();

        if ($total === 0) {
            $this->command->warn("No se encontraron productos con código numérico. ¿Ya fueron corregidos?");
            return;
        }

        $this->command->info("Productos a corregir (código numérico): {$total}");
        $this->command->newLine();

        $contadores = ['actualizados' => 0, 'sin_datos_json' => 0, 'errores' => 0];

        // Caché de nombres de marca → para evitar N+1
        $marcaCache = Marca::pluck('nombre', 'id')->toArray();

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($productosDelExcel as $producto) {
                $codigoViejo = (string) $producto->codigo;
                $datosJson   = $porCodigoViejo[$codigoViejo] ?? null;

                // Generar nuevo código descriptivo
                $nuevoCodigo = $this->generarCodigo(
                    marcaNombre:        $marcaCache[$producto->marca_id] ?? null,
                    pulgadas:           $producto->pulgadas_tv,
                    barras:             $producto->unidades_por_empaque,
                    leds:               $producto->leds_por_barra,
                    excluirProductoId:  $producto->id,
                );

                // Descripción: conservar la que ya tuviera + añadir ref vieja
                $descripcionActual = $producto->descripcion ?? '';
                $refStr = "Ref. vieja: {$codigoViejo}";

                // No duplicar si ya se ejecutó parcialmente
                if (! str_contains($descripcionActual, 'Ref. vieja:')) {
                    $descripcionNueva = $descripcionActual !== ''
                        ? $descripcionActual . ' | ' . $refStr
                        : $refStr;
                } else {
                    $descripcionNueva = $descripcionActual;
                }

                // Stock real del Excel (puede ser negativo)
                $stockReal = $datosJson !== null
                    ? (int) $datosJson['stock_actual']
                    : $producto->stock_actual;

                $producto->codigo      = $nuevoCodigo;
                $producto->descripcion = $descripcionNueva;
                $producto->stock_actual = $stockReal;
                $producto->save();

                if ($datosJson === null) {
                    $contadores['sin_datos_json']++;
                }

                $contadores['actualizados']++;
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

        // ── Resumen ───────────────────────────────────────────────────────────
        $this->command->table(
            ['Resultado', 'Cantidad'],
            [
                ['✓ Productos actualizados',              $contadores['actualizados']],
                ['⚠ Sin datos JSON (stock no restaurado)', $contadores['sin_datos_json']],
                ['✗ Errores',                              $contadores['errores']],
            ]
        );

        // Verificar que ya no quedan códigos numéricos
        $quedanNumericos = Producto::get()
            ->filter(fn ($p) => ctype_digit((string) $p->codigo))
            ->count();

        $this->command->newLine();
        if ($quedanNumericos === 0) {
            $this->command->info("✓ Verificación OK: No quedan productos con código numérico.");
        } else {
            $this->command->warn("⚠ Aún quedan {$quedanNumericos} productos con código numérico.");
        }

        // Mostrar muestra de resultados
        $this->command->newLine();
        $this->command->info("Muestra de productos corregidos:");
        Producto::with('marca')
            ->whereNotNull('descripcion')
            ->where('descripcion', 'like', 'Ref. vieja:%')
            ->take(5)
            ->get()
            ->each(function ($p) {
                $this->command->line(
                    "  [{$p->codigo}] {$p->nombre} | {$p->descripcion} | Stock: {$p->stock_actual}"
                );
            });
    }

    /**
     * Genera el código descriptivo: MARCA-{PULGADAS}IN-{BARRAS}B-{LEDS}LED
     *
     * Formato: LG-32IN-3B-7LED
     *
     * Si hay colisión con otro producto (excluyendo el producto actual),
     * agrega sufijo numérico: LG-32IN-3B-7LED-02
     */
    private function generarCodigo(
        ?string $marcaNombre,
        ?int    $pulgadas,
        ?int    $barras,
        ?int    $leds,
        int     $excluirProductoId,
    ): string {
        $parts = [];

        // Marca: normalizar (quitar espacios y caracteres especiales)
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

        $base = substr(implode('-', $parts), 0, 60);

        // Resolver colisiones
        $candidate = $base;
        for ($i = 1; $i <= 999; $i++) {
            $existe = Producto::where('codigo', $candidate)
                ->where('id', '!=', $excluirProductoId)
                ->exists();

            if (! $existe) {
                return $candidate;
            }

            $candidate = $base . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        // Fallback con parte aleatoria si hay demasiadas colisiones
        return $base . '-' . strtoupper(Str::random(4));
    }
}
