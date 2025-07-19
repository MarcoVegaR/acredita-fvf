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
        Schema::create('accreditation_request_zone', function (Blueprint $table) {
            $table->foreignId('request_id')->constrained('accreditation_requests')->onDelete('cascade');
            $table->foreignId('zone_id')->constrained();
            $table->primary(['request_id', 'zone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accreditation_request_zone');
    }
};
