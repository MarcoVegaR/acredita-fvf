<?php

use App\Enums\AccreditationStatus;
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
        Schema::create('accreditation_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('event_id')->constrained();
            $table->string('status')->default(AccreditationStatus::Draft->value);
            $table->timestamp('requested_at')->nullable();
            $table->text('comments')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['status', 'event_id']);
            $table->unique(['employee_id', 'event_id', 'status'], 'unique_active_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accreditation_requests');
    }
};
