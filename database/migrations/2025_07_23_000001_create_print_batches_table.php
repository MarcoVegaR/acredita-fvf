<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Relaciones
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->json('area_id')->nullable(); // Array de IDs de áreas
            $table->json('provider_id')->nullable(); // Array de IDs de proveedores  
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            
            // Estado del lote (refinado con archived)
            $table->enum('status', ['queued', 'processing', 'ready', 'failed', 'archived'])->default('queued');
            
            // Metadatos del lote
            $table->json('filters_snapshot'); // Filtros aplicados al crear el lote
            $table->integer('total_credentials')->default(0);
            $table->integer('processed_credentials')->default(0); // Para progreso chunked
            $table->string('pdf_path')->nullable();
            
            // Timestamps de proceso
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            
            // Control de errores y reintentos
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            
            $table->timestamps();
            
            // Índices optimizados
            $table->index(['status', 'created_at']);
            $table->index(['event_id', 'status']);
            $table->index(['generated_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_batches');
    }
};
