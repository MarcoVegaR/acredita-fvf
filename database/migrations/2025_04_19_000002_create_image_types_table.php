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
        Schema::create('image_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index();
            $table->string('label');
            $table->string('module')->index();
            $table->timestamps();
            
            // Unique constraint to prevent duplicates
            $table->unique(['code', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_types');
    }
};
