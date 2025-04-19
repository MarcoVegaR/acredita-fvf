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
        Schema::create('imageables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')->constrained()->cascadeOnDelete();
            $table->morphs('imageable');
            $table->foreignId('image_type_id')->constrained('image_types')->cascadeOnDelete();
            $table->timestamps();
            
            // Prevent duplicate associations
            $table->unique(['image_id', 'imageable_id', 'imageable_type', 'image_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imageables');
    }
};
