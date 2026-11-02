<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $materials = DB::table('materials')->get();

        foreach ($materials as $material) {
            $totalDistributed = (int) DB::table('material_project_assignments')
                ->where('material_id', $material->id)
                ->sum('quantity');

            $currentQuantity = (int) ($material->quantity ?? 0);
            $correctInitialStock = $currentQuantity + $totalDistributed;

            if ((int) $material->initial_stock !== $correctInitialStock) {
                DB::table('materials')
                    ->where('id', $material->id)
                    ->update(['initial_stock' => $correctInitialStock]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

