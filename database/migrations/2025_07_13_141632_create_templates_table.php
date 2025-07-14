<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('event_id');
            $table->string('name', 100);
            $table->string('file_path', 255);
            $table->json('layout_meta')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            // Índice para deleted_at para mejorar rendimiento en consultas
            $table->index('deleted_at');
            
            // Clave foránea hacia eventos (onDelete cascade)
            $table->foreign('event_id')
                  ->references('id')
                  ->on('events')
                  ->onDelete('cascade');
        });
        
        // Índice parcial para garantizar una sola plantilla predeterminada por evento
        DB::statement('CREATE UNIQUE INDEX event_default_unique ON templates (event_id) WHERE is_default = true AND deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el índice parcial primero
        DB::statement('DROP INDEX IF EXISTS event_default_unique');
        
        Schema::dropIfExists('templates');
    }
};
