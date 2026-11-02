<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_requests', function (Blueprint $table): void {
            $table->timestamp('distributed_at')->nullable()->after('approved_at');
            $table->unsignedInteger('permitted_quantity')->nullable()->after('quantity')
                ->comment('Final quantity approved/permitted by HSE Officer');
        });

        Schema::table('material_request_employees', function (Blueprint $table): void {
            $table->unsignedInteger('permitted_quantity')->nullable()->after('quantity')
                ->comment('Quantity permitted by HSE Officer for this employee');
        });
    }

    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table): void {
            $table->dropColumn(['distributed_at', 'permitted_quantity']);
        });

        Schema::table('material_request_employees', function (Blueprint $table): void {
            $table->dropColumn('permitted_quantity');
        });
    }
};
