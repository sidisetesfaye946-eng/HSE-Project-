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
        Schema::table('material_request_items', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('quantity');
            $table->unsignedInteger('permitted_quantity')->nullable()->after('status');
            $table->text('rejection_reason')->nullable()->after('permitted_quantity');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        // Backfill status from parent material_requests for existing records
        $requests = DB::table('material_requests')
            ->whereIn('status', ['approved', 'rejected', 'partial_approved'])
            ->get(['id', 'status', 'approved_by', 'approved_at', 'rejection_reason']);

        foreach ($requests as $req) {
            $itemStatus = $req->status === 'approved' ? 'approved' : ($req->status === 'rejected' ? 'rejected' : 'pending');
            DB::table('material_request_items')
                ->where('material_request_id', $req->id)
                ->update([
                    'status' => $itemStatus,
                    'permitted_quantity' => $itemStatus === 'approved' ? DB::raw('quantity') : null,
                    'rejection_reason' => $itemStatus === 'rejected' ? $req->rejection_reason : null,
                    'approved_by' => $req->approved_by,
                    'approved_at' => $req->approved_at,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'permitted_quantity', 'rejection_reason', 'approved_by', 'approved_at']);
        });
    }
};
