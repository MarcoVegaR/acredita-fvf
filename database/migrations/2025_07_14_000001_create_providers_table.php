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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('area_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // Usar un índice único condicional (solo aplica cuando user_id no es NULL)
            $table->unique('user_id', 'providers_user_id_unique');
            $table->string('name', 150);
            $table->string('rif', 20)->unique();
            $table->string('phone', 30)->nullable();
            $table->enum('type', ['internal', 'external']);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimizar consultas
            $table->index(['area_id', 'active']);
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
