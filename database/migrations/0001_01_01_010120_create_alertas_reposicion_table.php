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
        Schema::create('alertas_reposicion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->integer('stock_actual');
            $table->integer('stock_minimo');

            $table->string('estado', 30)->default('pendiente');

            $table->dateTime('fecha_alerta')->useCurrent();

            $table->timestamps();

            $table->index(['producto_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas_reposicion');
    }
};
