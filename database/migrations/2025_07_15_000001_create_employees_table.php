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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->enum('document_type', ['V', 'E', 'P'])->comment('V: Venezuelan, E: Foreigner, P: Passport');
            $table->string('document_number', 20);
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('function', 100);
            $table->string('photo_path')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint on provider_id, document_type and document_number
            $table->unique(['provider_id', 'document_type', 'document_number']);
            // Index for common queries
            $table->index(['provider_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
