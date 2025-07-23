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
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedBigInteger('suspended_by')->nullable();
            $table->text('suspension_reason')->nullable();
            
            $table->foreign('suspended_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accreditation_requests', function (Blueprint $table) {
            $table->dropForeign(['suspended_by']);
            $table->dropColumn(['suspended_at', 'suspended_by', 'suspension_reason']);
        });
    }
};
