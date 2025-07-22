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
            // Eliminar el constraint anterior (que incluía provider_id)
            $table->dropUnique(['provider_id', 'document_type', 'document_number']);
            
            // Crear el nuevo constraint global (sin provider_id)
            $table->unique(['document_type', 'document_number'], 'employees_document_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Restaurar el constraint anterior
            $table->dropUnique('employees_document_unique');
            $table->unique(['provider_id', 'document_type', 'document_number']);
        });
    }
};
