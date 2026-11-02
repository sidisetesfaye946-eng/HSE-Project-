<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes material_id and quantity nullable on the parent table so that
     * new multi-material requests store items in `material_request_items`
     * instead.  Existing rows are migrated to the items table first.
     */
    public function up(): void
    {
        // Step 1 — Copy existing material_id + quantity into the items table
        $rows = DB::table('material_requests')
            ->whereNotNull('material_id')
            ->get(['id', 'material_id', 'quantity']);

        foreach ($rows as $row) {
            $alreadyMigrated = DB::table('material_request_items')
                ->where('material_request_id', $row->id)
                ->exists();

            if (! $alreadyMigrated) {
                DB::table('material_request_items')->insert([
                    'material_request_id' => $row->id,
                    'material_id' => $row->material_id,
                    'quantity' => $row->quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Step 2 — Make columns nullable
        Schema::table('material_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('material_id')->nullable()->change();
            $table->integer('quantity')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('material_id')->nullable(false)->change();
            $table->integer('quantity')->nullable(false)->change();
        });
    }
};
