<?php

namespace Database\Seeders;

use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Actualiza los productos del Excel ya en BD (identificados por "Ref. vieja: XXXX")
 * con los datos técnicos mejorados extraídos del JSON:
 *
 *  - pulgadas_tv   : ahora con 97% de cobertura (parser mejorado)
 *  - leds_por_barra: dato técnico de la descripción
 *  - stock_actual  : restaura el valor real (incluyendo negativos)
 *  - codigo        : regenerado con la info completa MARCA-PULGADAS-BARRAS-LEDS
 *
 * Uso:
 *   php artisan db:seed --class=ActualizarDatosTecnicosSeeder
 */
class ActualizarDatosTecnicosSeeder extends Seeder
{
    public function run(): void
    {
        // ── Cargar JSON mejorado ───────────────────────────────────────────────
        $dataPath = database_path('seeders/data/productos_importacion.json');

        if (! file_exists($dataPath)) {
            $this->command->error("No se encontró: {$dataPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($dataPath), true);

        // Índice por código viejo (el que está en la descripción)
        $porCodigoViejo = [];
        foreach ($jsonData as $row) {
            $porCodigoViejo[(string) $row['codigo']] = $row;
        }

        // ── Obtener productos importados del Excel (tienen "Ref. vieja:" en desc) ──
        $productosImportados = Producto::with('marca')
            ->where('descripcion', 'like', 'Ref. vieja:%')
            ->get();

        $total = $productosImportados->count();

        if ($total === 0) {
            $this->command->warn("No se encontraron productos con 'Ref. vieja:' en la descripción.");
            return;
        }

        $this->command->info("Productos importados a actualizar: {$total}");
        $this->command->newLine();

        $contadores = [
            'actualizados'     => 0,
            'sin_datos_json'   => 0,
            'codigo_igual'     => 0,
            'codigo_actualizado' => 0,
        ];

        $marcaCache = Marca::pluck('nombre', 'id')->toArray();

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($productosImportados as $producto) {
                // Extraer código viejo desde la descripción
                // Formato: "Ref. vieja: 1000" o "... | Ref. vieja: 1000"
                $codigoViejo = null;
                if (preg_match('/Ref\. vieja:\s*(\d+)/', $producto->descripcion ?? '', $m)) {
                    $codigoViejo = $m[1];
                }

                if ($codigoViejo === null) {
                    $contadores['sin_datos_json']++;
                    $bar->advance();
                    continue;
                }

                $datosJson = $porCodigoViejo[$codigoViejo] ?? null;

                if ($datosJson === null) {
                    $contadores['sin_datos_json']++;
                    $bar->advance();
                    continue;
                }

                // Actualizar datos técnicos mejorados
                $producto->pulgadas_tv    = $datosJson['pulgadas_tv'];
                $producto->leds_por_barra = $datosJson['leds_por_barra'];
                $producto->stock_actual   = (int) $datosJson['stock_actual'];

                // Regenerar código con datos completos
                $nuevoCodigo = $this->generarCodigo(
                    marcaNombre:       $marcaCache[$producto->marca_id] ?? null,
                    pulgadas:          $datosJson['pulgadas_tv'],
                    barras:            $datosJson['unidades_por_empaque'],
                    leds:              $datosJson['leds_por_barra'],
                    excluirProductoId: $producto->id,
                );

                if ($nuevoCodigo !== $producto->codigo) {
                    $producto->codigo = $nuevoCodigo;
                    $contadores['codigo_actualizado']++;
                } else {
                    $contadores['codigo_igual']++;
                }

                $producto->save();
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

        $this->command->table(
            ['Resultado', 'Cantidad'],
            [
                ['✓ Productos actualizados',          $contadores['actualizados']],
                ['  - Código regenerado (mejorado)',   $contadores['codigo_actualizado']],
                ['  - Código sin cambios',             $contadores['codigo_igual']],
                ['⚠ Sin datos en JSON',                $contadores['sin_datos_json']],
            ]
        );

        // Verificación final de pulgadas
        $conPulgadas = Producto::whereNotNull('pulgadas_tv')
            ->where('descripcion', 'like', 'Ref. vieja:%')
            ->count();
        $sinPulgadas = Producto::whereNull('pulgadas_tv')
            ->where('descripcion', 'like', 'Ref. vieja:%')
            ->count();

        $this->command->newLine();
        $this->command->info("✓ Con pulgadas_tv: {$conPulgadas} | Sin pulgadas_tv: {$sinPulgadas} (genuinamente sin ellas)");

        // Muestra de resultados
        $this->command->newLine();
        $this->command->info("Muestra de productos actualizados:");
        Producto::with('marca')
            ->where('descripcion', 'like', 'Ref. vieja:%')
            ->take(6)
            ->get()
            ->each(function ($p) {
                $pulgadas = $p->pulgadas_tv ? $p->pulgadas_tv . '"' : 'N/A';
                $leds     = $p->leds_por_barra ? $p->leds_por_barra . 'L' : 'N/A';
                $this->command->line(
                    "  [{$p->codigo}] {$p->nombre} | {$p->descripcion} | {$pulgadas} | {$leds} | Stock:{$p->stock_actual}"
                );
            });
    }

    /**
     * Genera el código descriptivo: MARCA-{PULGADAS}IN-{BARRAS}B-{LEDS}LED
     */
    private function generarCodigo(
        ?string $marcaNombre,
        ?int    $pulgadas,
        ?int    $barras,
        ?int    $leds,
        int     $excluirProductoId,
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

        $base = substr(implode('-', $parts), 0, 60);

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

        return $base . '-' . strtoupper(Str::random(4));
    }
}
