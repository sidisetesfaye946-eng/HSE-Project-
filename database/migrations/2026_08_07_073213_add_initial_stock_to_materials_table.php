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
        if (! Schema::hasColumn('materials', 'initial_stock')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->integer('initial_stock')->default(0)->after('quantity');
            });

            DB::table('materials')->where('initial_stock', 0)->update([
                'initial_stock' => DB::raw('quantity'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('materials', 'initial_stock')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->dropColumn('initial_stock');
            });
        }
    }
};
