<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitOfMeasureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('unit_of_measures')->insertOrIgnore([
            ['name' => 'Pcs', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pair', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Meter', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Liter', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bag', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Roll', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pack', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
