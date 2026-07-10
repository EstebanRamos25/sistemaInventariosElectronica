<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el campo tipo_venta a venta_detalles para registrar
 * si se vendió un juego completo (bolsa cerrada) o barras sueltas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->enum('tipo_venta', ['juego', 'barra'])
                ->default('juego')
                ->after('producto_id')
                ->comment('juego = bolsa cerrada completa | barra = unidad individual suelta');
        });
    }

    public function down(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropColumn('tipo_venta');
        });
    }
};
