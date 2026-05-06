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
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();

            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->restrictOnDelete();

            $table->string('numero_orden')->unique();

            $table->date('fecha_orden');
            $table->date('fecha_estimada_llegada')->nullable();

            $table->string('estado', 20)->default('pendiente');

            $table->decimal('total', 14, 2)->default(0);

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(['proveedor_id', 'fecha_orden']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
