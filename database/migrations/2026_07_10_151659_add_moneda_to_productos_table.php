<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el campo `moneda` a productos para indicar en qué divisa
 * están expresados sus precios (USD o Bs).
 *
 * Los 295 productos existentes se marcarán como 'USD' por defecto
 * ya que los precios del inventario importado están en dólares.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->enum('moneda', ['USD', 'Bs'])
                ->default('USD')
                ->after('precio_venta_barra')
                ->comment('Moneda en que están expresados los precios: USD o Bs (bolivianos)');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('moneda');
        });
    }
};
