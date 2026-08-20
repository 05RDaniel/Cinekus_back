<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('languages')->restrictOnDelete();
            $table->date('start_date')->nullable();
            $table->time('start_time')->nullable();
            $table->string('session_type', 3)->default('2d');
            $table->string('subtitles', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
