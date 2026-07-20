<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Regenera los códigos de todos los productos usando el nuevo formato:
 *   [MARCA 4 letras] - [pulgadas''] - [ledsLED]
 *
 * Ejemplos:
 *   SAMS-32''-108LED
 *   LG-49''
 *   AOCJ-75''-7LED
 *   CHIN-42''
 *
 * Se omiten los datos que estén vacíos/nulos.
 * Si el código nuevo ya existe en otro producto, se añade -02, -03, etc.
 */
class RegenerarCodigosProductosSeeder extends Seeder
{
    public function run(): void
    {
        $productos = Producto::with('marca')->orderBy('id')->get();

        $this->command->info("Regenerando códigos de {$productos->count()} productos...");
        $this->command->newLine();

        // Tabla temporal para rastrear códigos ya usados en esta ejecución
        $usados = [];

        DB::transaction(function () use ($productos, &$usados) {
            foreach ($productos as $producto) {
                $codigoNuevo = $this->generarCodigo($producto, $usados);

                $codigoViejo = $producto->codigo;
                $usados[]    = $codigoNuevo;

                if ($codigoNuevo === $codigoViejo) {
                    $this->command->line("  [sin cambio]  {$codigoViejo}");
                    continue;
                }

                $producto->codigo = $codigoNuevo;
                $producto->save();

                $this->command->line("  {$codigoViejo}  →  <fg=green>{$codigoNuevo}</>");
            }
        });

        $this->command->newLine();
        $this->command->info('✅ Códigos regenerados correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function generarCodigo(Producto $producto, array $usados): string
    {
        $parts = [];

        // 1. Prefijo de marca — primeras 3-4 letras sin caracteres especiales
        $nombreMarca = trim((string) ($producto->marca?->nombre ?? ''));
        if ($nombreMarca !== '') {
            $solo    = preg_replace('/[^A-Za-z0-9]/', '', $nombreMarca);
            $prefijo = strtoupper(substr($solo, 0, min(4, strlen($solo))));
            if ($prefijo !== '') {
                $parts[] = $prefijo;
            }
        }

        // 2. Pulgadas con símbolo ''
        $pulgadas = $producto->pulgadas_tv ? (int) $producto->pulgadas_tv : null;
        if ($pulgadas !== null && $pulgadas > 0) {
            $parts[] = $pulgadas . "''";
        }

        // 3. LEDs por barra con sufijo LED
        $leds = $producto->leds_por_barra ? (int) $producto->leds_por_barra : null;
        if ($leds !== null && $leds > 0) {
            $parts[] = $leds . 'LED';
        }

        // Fallback si no hay ningún dato útil
        if ($parts === []) {
            $nombre  = trim((string) $producto->nombre);
            $parts[] = $nombre !== ''
                ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nombre), 0, 8))
                : 'PROD';
        }

        $base = substr(implode('-', $parts), 0, 40);

        // Verificar unicidad (contra BD + contra los que ya asignamos en esta ejecución)
        $candidate = $base;
        for ($i = 2; $i <= 99; $i++) {
            $existeEnBd = Producto::where('codigo', $candidate)
                ->where('id', '!=', $producto->id)
                ->exists();

            $existeEnSesion = in_array($candidate, $usados, true);

            if (! $existeEnBd && ! $existeEnSesion) {
                return $candidate;
            }

            $candidate = $base . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        return $base . '-' . strtoupper(substr(md5((string) $producto->id), 0, 4));
    }
}
