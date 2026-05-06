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
            $table->string('unidad', 20)->default('pieza')->after('modelo_tv');
            $table->string('empaque', 30)->nullable()->after('unidad');
            $table->unsignedInteger('unidades_por_empaque')->nullable()->after('empaque');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['unidad', 'empaque', 'unidades_por_empaque']);
        });
    }
};
