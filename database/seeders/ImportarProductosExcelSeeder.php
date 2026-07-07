<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Importa los productos del archivo INVENTARIO 2026 1.xlsx al sistema.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * OPCIONES DE CONFIGURACIÓN (ajusta según necesidad antes de ejecutar):
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * CODIGO_LEGADO_EN_DESCRIPCION (bool)
 *   true  → El código numérico viejo se antepone en la descripción: "[Ref: 1000] ..."
 *           El campo `codigo` usará el nuevo formato generado automáticamente.
 *   false → El código viejo se usa DIRECTAMENTE como `codigo` del producto.
 *           Esto facilita la transición para operarios que ya conocen los códigos.
 *           *** DEFAULT: false (conservar códigos viejos) ***
 *
 * STOCKS_NEGATIVOS_A_CERO (bool)
 *   true  → Si el stock importado es negativo, se importa como 0. (DEFAULT)
 *   false → Se importa el valor negativo tal como está en el Excel.
 *
 * STOCK_MINIMO_DEFAULT (int)
 *   Stock mínimo que se asigna a todos los productos importados (puede editarse después).
 *
 * STOCK_IDEAL_DEFAULT (int)
 *   Stock ideal que se asigna a todos los productos importados.
 *
 * CATEGORIA_DEFAULT (string)
 *   Nombre de la categoría que se creará/usará para todos los productos.
 *   En el Excel no hay categorías, solo marcas.
 *
 * TIEMPO_REPOSICION_DEFAULT (int|null)
 *   Días estimados de reposición para todos los productos importados.
 *
 * OMITIR_SI_EXISTE (bool)
 *   true  → Si el código ya existe en la BD, omite ese producto (no sobreescribe).
 *   false → Actualiza el producto existente con los datos del Excel.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
class ImportarProductosExcelSeeder extends Seeder
{
    // ─── Ajusta estas opciones según tu preferencia ───────────────────────────

    /** Poner el código viejo en la descripción en lugar de usarlo como código directo */
    private const CODIGO_LEGADO_EN_DESCRIPCION = true;

    /** Convertir stocks negativos a 0 */
    private const STOCKS_NEGATIVOS_A_CERO = false;

    /** Stock mínimo por defecto para todos los productos importados */
    private const STOCK_MINIMO_DEFAULT = 2;

    /** Stock ideal por defecto para todos los productos importados */
    private const STOCK_IDEAL_DEFAULT = 10;

    /** Categoría por defecto (se crea si no existe) */
    private const CATEGORIA_DEFAULT = 'Barras LED TV';

    /** Días de reposición por defecto */
    private const TIEMPO_REPOSICION_DEFAULT = 7;

    /** true = omitir productos cuyo código ya exista | false = actualizar */
    private const OMITIR_SI_EXISTE = true;

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $dataPath = database_path('seeders/data/productos_importacion.json');

        if (! file_exists($dataPath)) {
            $this->command->error("No se encontró el archivo de datos: {$dataPath}");
            $this->command->info("Ejecuta primero el script Python para generar el JSON:");
            $this->command->info("  .venv/bin/python3 scripts/extraer_productos_excel.py");
            return;
        }

        $productos = json_decode(file_get_contents($dataPath), true);

        if (! $productos || ! is_array($productos)) {
            $this->command->error("El archivo JSON está vacío o malformado.");
            return;
        }

        $this->command->info("Iniciando importación de " . count($productos) . " productos...");
        $this->command->newLine();

        // ── 1. Obtener / crear categoría base ─────────────────────────────────
        $categoria = Categoria::firstOrCreate(
            ['nombre' => self::CATEGORIA_DEFAULT],
            ['descripcion' => 'Barras de retroiluminación LED para televisores. Importado desde inventario 2026.']
        );
        $this->command->line("✓ Categoría: [{$categoria->id}] {$categoria->nombre}");

        // ── 2. Pre-crear todas las marcas ─────────────────────────────────────
        $marcasEnDatos = array_unique(array_column($productos, 'marca'));
        $marcasMap     = [];

        foreach ($marcasEnDatos as $nombreMarca) {
            $marca = Marca::firstOrCreate(['nombre' => $nombreMarca]);
            $marcasMap[$nombreMarca] = $marca->id;
        }
        $this->command->line("✓ Marcas creadas/encontradas: " . count($marcasMap));
        $this->command->newLine();

        // ── 3. Importar productos ─────────────────────────────────────────────
        $contadores = [
            'importados' => 0,
            'actualizados' => 0,
            'omitidos'   => 0,
            'errores'    => 0,
        ];

        $bar = $this->command->getOutput()->createProgressBar(count($productos));
        $bar->start();

        DB::beginTransaction();

        try {
            foreach ($productos as $row) {
                $codigoViejo = (string) $row['codigo'];
                $descripcion = $row['nombre'];

                // Determinar el código a usar en el nuevo sistema
                if (self::CODIGO_LEGADO_EN_DESCRIPCION) {
                    // Código nuevo generado: MARCA-PULGADAS-..., el viejo va en descripción
                    $codigoFinal = $this->generarCodigoNuevo($row, $marcasMap);
                    $descripcionFinal = "[Ref: {$codigoViejo}] {$descripcion}";
                } else {
                    // Conservar el código numérico viejo directamente
                    $codigoFinal      = $codigoViejo;
                    $descripcionFinal = $descripcion;
                }

                // Verificar si ya existe
                $existe = Producto::where('codigo', $codigoFinal)->exists();

                if ($existe && self::OMITIR_SI_EXISTE) {
                    $contadores['omitidos']++;
                    $bar->advance();
                    continue;
                }

                // Calcular stock final
                $stockActual = (int) $row['stock_actual'];
                if (self::STOCKS_NEGATIVOS_A_CERO && $stockActual < 0) {
                    $stockActual = 0;
                }

                $data = [
                    'categoria_id'          => $categoria->id,
                    'marca_id'              => $marcasMap[$row['marca']],
                    'codigo'                => $codigoFinal,
                    'nombre'                => $descripcionFinal,
                    'descripcion'           => null,
                    'modelo_tv'             => $row['modelo_tv'] ?? null,
                    'pulgadas_tv'           => $row['pulgadas_tv'] ?? null,
                    'leds_por_barra'        => $row['leds_por_barra'] ?? null,
                    'caracteristicas_barra' => $row['caracteristicas_barra'] ?? null,
                    'unidad'                => $row['unidad'] ?? 'juego',
                    'empaque'               => $row['empaque'] ?? null,
                    'unidades_por_empaque'  => $row['unidades_por_empaque'] ?? null,
                    'precio_compra'         => (float) ($row['precio_compra'] ?? 0),
                    'precio_venta'          => (float) ($row['precio_venta'] ?? 0),
                    'stock_actual'          => $stockActual,
                    'stock_minimo'          => self::STOCK_MINIMO_DEFAULT,
                    'stock_ideal'           => self::STOCK_IDEAL_DEFAULT,
                    'tiempo_reposicion_dias' => self::TIEMPO_REPOSICION_DEFAULT,
                    'ubicacion'             => null,
                    'activo'                => (bool) ($row['activo'] ?? true),
                ];

                if ($existe) {
                    Producto::where('codigo', $codigoFinal)->update($data);
                    $contadores['actualizados']++;
                } else {
                    Producto::create($data);
                    $contadores['importados']++;
                }

                $bar->advance();
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            $bar->finish();
            $this->command->newLine(2);
            $this->command->error("Error durante la importación: " . $e->getMessage());
            $this->command->error("Línea: " . $e->getFile() . ':' . $e->getLine());
            return;
        }

        $bar->finish();
        $this->command->newLine(2);

        // ── 4. Resumen ────────────────────────────────────────────────────────
        $this->command->table(
            ['Resultado', 'Cantidad'],
            [
                ['✓ Productos importados (nuevos)',   $contadores['importados']],
                ['✓ Productos actualizados',           $contadores['actualizados']],
                ['- Omitidos (ya existían)',            $contadores['omitidos']],
                ['✗ Errores',                           $contadores['errores']],
                ['─────────────────────', '─────'],
                ['TOTAL procesados',                    count($productos)],
            ]
        );

        $this->command->newLine();
        $this->command->info("Importación completada exitosamente.");
        $this->command->info("Verifica los productos en: /productos");

        if (self::CODIGO_LEGADO_EN_DESCRIPCION) {
            $this->command->warn("Nota: Los códigos viejos están guardados en el campo 'nombre' con prefijo [Ref: XXXX].");
        } else {
            $this->command->warn("Nota: Los códigos numéricos del Excel se conservaron tal cual en el nuevo sistema.");
        }
    }

    /**
     * Genera un código descriptivo nuevo basado en los atributos del producto.
     * Solo se usa cuando CODIGO_LEGADO_EN_DESCRIPCION = true.
     */
    private function generarCodigoNuevo(array $row, array $marcasMap): string
    {
        $parts = [];

        $parts[] = strtoupper(str_replace([' ', '-'], '', $row['marca']));

        if (! empty($row['pulgadas_tv'])) {
            $parts[] = $row['pulgadas_tv'] . 'IN';
        }

        if (! empty($row['leds_por_barra'])) {
            $parts[] = $row['leds_por_barra'] . 'LED';
        }

        if (! empty($row['unidades_por_empaque'])) {
            $parts[] = $row['unidades_por_empaque'] . 'B';
        }

        $base = implode('-', $parts);
        $base = substr(strtoupper($base), 0, 60) ?: 'PROD';

        // Evitar duplicados agregando sufijo
        $candidate = $base;
        for ($i = 1; $i <= 999; $i++) {
            if (! Producto::where('codigo', $candidate)->exists()) {
                return $candidate;
            }
            $candidate = $base . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        return $base . '-' . substr(md5(uniqid()), 0, 6);
    }
}
