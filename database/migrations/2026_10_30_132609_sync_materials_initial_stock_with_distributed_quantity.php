<?php

use Illuminate\Database\Migrations\Migration;

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
            $currentInitialStock = (int) ($material->initial_stock ?? 0);
            $requiredInitialStock = $currentQuantity + $totalDistributed;

            if ($currentInitialStock < $requiredInitialStock) {
                DB::table('materials')
                    ->where('id', $material->id)
                    ->update(['initial_stock' => $requiredInitialStock]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: initial_stock synchronization cannot be reverted losslessly.
    }
};
