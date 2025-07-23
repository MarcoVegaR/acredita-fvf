<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Corrige la restricción única en accreditation_requests para considerar deleted_at
     */
    public function up(): void
    {
        Schema::table('accreditation_requests', function (Blueprint $table) {
            // Eliminar la restricción única existente que no tiene en cuenta deleted_at
            $table->dropUnique('unique_active_request');
            
            // En PostgreSQL no se puede crear una restricción única condicional directamente con Laravel
            // Por lo tanto, creamos un índice único parcial usando un nombre diferente
            DB::statement('CREATE UNIQUE INDEX unique_active_request_with_deleted_check ON accreditation_requests (employee_id, event_id, status) WHERE deleted_at IS NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accreditation_requests', function (Blueprint $table) {
            // Eliminar el índice único parcial
            DB::statement('DROP INDEX IF EXISTS unique_active_request_with_deleted_check');
            
            // Restaurar la restricción única original
            $table->unique(['employee_id', 'event_id', 'status'], 'unique_active_request');
        });
    }
};
