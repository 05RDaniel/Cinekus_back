<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('sinopsis')->nullable();
            $table->boolean('is_available')->default(false);
            $table->unique(['movie_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_translations');
    }
};
