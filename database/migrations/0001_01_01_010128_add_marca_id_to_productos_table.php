<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('marca_id')
                ->nullable()
                ->after('categoria_id')
                ->constrained('marcas')
                ->restrictOnDelete();

            $table->index('marca_id');
        });

        // Backfill: crear marcas desde productos.marca y asignar marca_id.
        $distinct = DB::table('productos')
            ->select('marca')
            ->whereNotNull('marca')
            ->where('marca', '!=', '')
            ->distinct()
            ->pluck('marca');

        $normalizedToOriginals = [];
        foreach ($distinct as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }

            $normalized = strtoupper($raw);
            $normalizedToOriginals[$normalized] = $raw;
        }

        foreach ($normalizedToOriginals as $normalized => $_original) {
            DB::table('marcas')->updateOrInsert(
                ['nombre' => $normalized],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }

        $marcaIds = DB::table('marcas')
            ->whereIn('nombre', array_keys($normalizedToOriginals))
            ->pluck('id', 'nombre');

        DB::table('productos')
            ->select('id', 'marca')
            ->whereNotNull('marca')
            ->where('marca', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($marcaIds) {
                foreach ($rows as $row) {
                    $raw = trim((string) $row->marca);
                    if ($raw === '') {
                        continue;
                    }

                    $normalized = strtoupper($raw);
                    $marcaId = $marcaIds[$normalized] ?? null;
                    if (! $marcaId) {
                        continue;
                    }

                    DB::table('productos')
                        ->where('id', (int) $row->id)
                        ->update(['marca_id' => (int) $marcaId]);
                }
            }, 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marca_id');
        });
    }
};
