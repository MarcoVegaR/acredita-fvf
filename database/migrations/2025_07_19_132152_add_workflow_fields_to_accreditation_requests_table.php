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
        Schema::table('accreditation_requests', function (Blueprint $table) {
            // Campos para revisión (visto bueno del área)
            $table->timestamp('reviewed_at')->nullable()->after('requested_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->after('reviewed_at');
            $table->text('review_comments')->nullable()->after('reviewed_by');
            
            // Campos para aprobación
            $table->timestamp('approved_at')->nullable()->after('review_comments');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('approved_at');
            
            // Campos para rechazo
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->after('rejected_at');
            $table->text('rejection_reason')->nullable()->after('rejected_by');
            
            // Campos para devolución a borrador
            $table->timestamp('returned_at')->nullable()->after('rejection_reason');
            $table->foreignId('returned_by')->nullable()->constrained('users')->after('returned_at');
            $table->text('return_reason')->nullable()->after('returned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accreditation_requests', function (Blueprint $table) {
            $table->dropColumn([
                'reviewed_at', 'reviewed_by', 'review_comments',
                'approved_at', 'approved_by',
                'rejected_at', 'rejected_by', 'rejection_reason',
                'returned_at', 'returned_by', 'return_reason'
            ]);
        });
    }
};
