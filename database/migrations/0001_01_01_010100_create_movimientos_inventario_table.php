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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->string('tipo', 20);
            $table->string('motivo', 30)->nullable();

            $table->integer('cantidad');

            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');

            $table->nullableMorphs('referencia');

            $table->text('observaciones')->nullable();

            $table->dateTime('fecha_movimiento')->useCurrent();

            $table->timestamps();

            $table->index(['producto_id', 'fecha_movimiento']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
