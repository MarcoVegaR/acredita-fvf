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
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('accreditation_request_id')->unique();
            
            $table->enum('status', ['pending', 'generating', 'ready', 'failed'])->default('pending');
            
            // Snapshots inmutables de datos
            $table->json('employee_snapshot')->nullable();
            $table->json('template_snapshot')->nullable();
            $table->json('event_snapshot')->nullable();
            $table->json('zones_snapshot')->nullable();
            
            // Archivos generados
            $table->string('qr_code')->unique()->nullable();
            $table->string('qr_image_path')->nullable();
            $table->string('credential_image_path')->nullable();
            $table->string('credential_pdf_path')->nullable();
            
            // Metadatos
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Manejo de errores y retry
            $table->text('error_message')->nullable();
            $table->tinyInteger('retry_count')->default(0);
            
            $table->timestamps();
            
            // Índices y claves foráneas
            $table->foreign('accreditation_request_id')
                  ->references('id')
                  ->on('accreditation_requests')
                  ->onDelete('cascade');
                  
            $table->index(['status']);
            $table->index(['is_active']);
            $table->index(['expires_at']);
            $table->index(['qr_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
