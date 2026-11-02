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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('project')->nullable();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->string('material_type')->nullable();
            $table->date('date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['material_id']);
            $table->dropColumn(['name', 'project_id', 'project', 'material_id', 'material_type', 'date']);
        });
    }
};
