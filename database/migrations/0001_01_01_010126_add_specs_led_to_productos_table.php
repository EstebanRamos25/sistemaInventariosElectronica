<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedSmallInteger('pulgadas_tv')->nullable()->after('modelo_tv');
            $table->decimal('voltaje_led', 6, 2)->nullable()->after('pulgadas_tv');
            $table->unsignedSmallInteger('leds_por_barra')->nullable()->after('voltaje_led');
            $table->string('caracteristicas_barra')->nullable()->after('leds_por_barra');

            $table->index(['marca', 'pulgadas_tv']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['marca', 'pulgadas_tv']);
            $table->dropColumn(['pulgadas_tv', 'voltaje_led', 'leds_por_barra', 'caracteristicas_barra']);
        });
    }
};
