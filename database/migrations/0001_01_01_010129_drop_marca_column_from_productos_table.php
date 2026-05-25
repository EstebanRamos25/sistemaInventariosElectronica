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
            // Índice creado en 010126: (marca, pulgadas_tv)
            $table->dropIndex('productos_marca_pulgadas_tv_index');

            $table->dropColumn('marca');

            $table->index(['marca_id', 'pulgadas_tv']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['marca_id', 'pulgadas_tv']);

            $table->string('marca')->nullable()->after('nombre');

            $table->index(['marca', 'pulgadas_tv']);
        });
    }
};
