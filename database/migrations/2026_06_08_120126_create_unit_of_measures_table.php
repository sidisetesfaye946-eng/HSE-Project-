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
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        DB::table('unit_of_measures')->insert([
            ['name' => 'Pcs', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pair', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Meter', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Liter', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bag', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
