<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->timestamp('printed_at')->nullable()->after('is_active');
            $table->foreignId('print_batch_id')->nullable()->constrained()->onDelete('set null')->after('printed_at');
            
            // Índices para optimizar consultas de filtrado
            $table->index(['printed_at']);
            $table->index(['print_batch_id']);
            $table->index(['status', 'printed_at']); // Para filtro "solo no impresas"
        });
    }

    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropForeign(['print_batch_id']);
            $table->dropIndex(['printed_at']);
            $table->dropIndex(['print_batch_id']);
            $table->dropIndex(['status', 'printed_at']);
            $table->dropColumn(['printed_at', 'print_batch_id']);
        });
    }
};
