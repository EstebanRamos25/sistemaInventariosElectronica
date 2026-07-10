<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega soporte para doble stock (Juegos + Barras sueltas) y precios
 * diferenciados por tipo de venta (juego completo vs. barra individual).
 *
 * - stock_barras_sueltas : barras LED sueltas (sacadas de bolsas abiertas)
 * - precio_venta_barra   : precio de venta por barra individual
 * - precio_compra_barra  : precio de costo por barra individual
 *
 * El campo existente `stock_actual` pasa a representar "stock de juegos".
 * El campo existente `unidades_por_empaque` ya representa "barras por juego".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Stock de barras sueltas (unidades individuales fuera de su empaque)
            $table->integer('stock_barras_sueltas')
                ->default(0)
                ->after('stock_actual')
                ->comment('Barras LED sueltas disponibles (sacadas de juegos abiertos)');

            // Precios diferenciados para venta por barra individual
            $table->decimal('precio_venta_barra', 14, 2)
                ->default(0)
                ->after('precio_venta')
                ->comment('Precio de venta por barra individual');

            $table->decimal('precio_compra_barra', 14, 2)
                ->default(0)
                ->after('precio_compra')
                ->comment('Precio de costo por barra individual');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'stock_barras_sueltas',
                'precio_venta_barra',
                'precio_compra_barra',
            ]);
        });
    }
};
