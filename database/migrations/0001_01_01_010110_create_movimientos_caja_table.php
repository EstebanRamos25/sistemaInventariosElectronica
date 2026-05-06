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
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();

            $table->foreignId('caja_id')
                ->constrained('cajas')
                ->restrictOnDelete();

            $table->string('tipo', 20);
            $table->string('categoria', 30);

            $table->decimal('monto', 14, 2);

            $table->string('descripcion');

            $table->nullableMorphs('referencia');

            $table->dateTime('fecha_movimiento')->useCurrent();

            $table->timestamps();

            $table->index(['caja_id', 'fecha_movimiento']);
            $table->index('tipo');
            $table->index('categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};
