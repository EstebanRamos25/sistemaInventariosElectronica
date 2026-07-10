<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de historial de tasas de cambio USD → Moneda local (Bs).
 *
 * Siempre se consulta la tasa vigente como la de mayor fecha <= hoy.
 * Permite llevar un historial completo de cuánto valió el dólar cada día.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas_cambio', function (Blueprint $table) {
            $table->id();

            $table->decimal('tasa', 14, 4)
                ->comment('Bolivianos (Bs) por 1 USD. Ej: 6.96');

            $table->string('fuente', 50)
                ->nullable()
                ->comment('Fuente de la tasa: BCB, mercado, manual, etc.');

            $table->text('notas')
                ->nullable()
                ->comment('Observaciones adicionales sobre este cambio de tasa');

            $table->date('fecha')
                ->comment('Fecha de vigencia de esta tasa');

            $table->timestamps();

            // Índice para obtener rápidamente la tasa vigente más reciente
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas_cambio');
    }
};
