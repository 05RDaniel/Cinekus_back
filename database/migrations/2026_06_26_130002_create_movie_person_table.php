<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_person', function (Blueprint $table) {
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->primary(['movie_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_person');
    }
};
