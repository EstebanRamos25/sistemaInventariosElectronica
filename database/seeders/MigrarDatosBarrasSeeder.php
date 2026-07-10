<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Migra los datos del nuevo Excel al sistema actualizado.
 *
 * Para todos los productos existentes en BD (que tienen "Ref. vieja: XXXX"):
 *  - Actualiza stock_barras_sueltas
 *  - Actualiza precio_venta_barra y precio_compra_barra
 *  - Actualiza stock_actual (juegos), precio_venta, precio_compra si cambiaron
 *
 * Para los 8 nuevos productos insertados por ActualizarInventarioNuevoExcelSeeder:
 *  - También actualiza sus precios de barra.
 *
 * Uso:
 *   php artisan db:seed --class=MigrarDatosBarrasSeeder
 */
class MigrarDatosBarrasSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('seeders/data/productos_nuevo.json');

        if (! file_exists($dataPath)) {
            $this->command->error("No se encontró: {$dataPath}");
            return;
        }

        $jsonData = json_decode(file_get_contents($dataPath), true);
        $total    = count($jsonData);
        $this->command->info("Actualizando barras y precios para {$total} productos...");

        // Índice BD: ref_vieja → producto
        $indexPorRefVieja = [];
        Producto::whereNotNull('descripcion')
            ->where('descripcion', 'like', '%Ref. vieja:%')
            ->each(function ($p) use (&$indexPorRefVieja) {
                if (preg_match('/Ref\. vieja:\s*(\d+)/', $p->descripcion, $m)) {
                    $indexPorRefVieja[(int) $m[1]] = $p;
                }
            });

        $contadores = ['actualizados' => 0, 'sin_cambios' => 0, 'no_encontrado' => 0];

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($jsonData as $row) {
                $codigoViejo = (int) $row['codigo'];

                if (! isset($indexPorRefVieja[$codigoViejo])) {
                    $contadores['no_encontrado']++;
                    $bar->advance();
                    continue;
                }

                $producto = $indexPorRefVieja[$codigoViejo];
                $changed  = false;

                // Stock barras sueltas
                $nuevoStockB = (int) ($row['stock_barras'] ?? 0);
                if ((int) $producto->stock_barras_sueltas !== $nuevoStockB) {
                    $producto->stock_barras_sueltas = $nuevoStockB;
                    $changed = true;
                }

                // Precio venta barra
                $nuevoPvB = round((float) ($row['precio_venta_barra'] ?? 0), 2);
                if (round((float) $producto->precio_venta_barra, 2) !== $nuevoPvB) {
                    $producto->precio_venta_barra = $nuevoPvB;
                    $changed = true;
                }

                // Precio compra barra
                $nuevoPcB = round((float) ($row['precio_compra_barra'] ?? 0), 2);
                if (round((float) $producto->precio_compra_barra, 2) !== $nuevoPcB) {
                    $producto->precio_compra_barra = $nuevoPcB;
                    $changed = true;
                }

                if ($changed) {
                    $producto->save();
                    $contadores['actualizados']++;
                } else {
                    $contadores['sin_cambios']++;
                }

                $bar->advance();
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            $bar->finish();
            $this->command->newLine(2);
            $this->command->error("Error: " . $e->getMessage());
            return;
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->command->table(
            ['Resultado', 'Cantidad'],
            [
                ['✓ Actualizados (barras + precios)', $contadores['actualizados']],
                ['  Sin cambios',                     $contadores['sin_cambios']],
                ['⚠ No encontrado en BD',             $contadores['no_encontrado']],
                ['TOTAL',                             $total],
            ]
        );

        // Resumen
        $conBarras   = Producto::where('stock_barras_sueltas', '>', 0)->count();
        $conPreciob  = Producto::where('precio_venta_barra', '>', 0)->count();
        $this->command->info("Productos con stock_barras_sueltas > 0: {$conBarras}");
        $this->command->info("Productos con precio_venta_barra > 0:   {$conPreciob}");
    }
}
