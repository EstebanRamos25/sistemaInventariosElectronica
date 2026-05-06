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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('categoria_id')
                ->constrained('categorias')
                ->restrictOnDelete();

            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();

            $table->string('marca')->nullable();
            $table->string('modelo_tv')->nullable();

            $table->decimal('precio_compra', 14, 2);
            $table->decimal('precio_venta', 14, 2);

            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->integer('stock_ideal')->default(0);

            $table->unsignedSmallInteger('tiempo_reposicion_dias')->nullable();

            $table->string('ubicacion')->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index(['categoria_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
