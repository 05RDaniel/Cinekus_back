<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('sinopsis')->nullable();
            $table->integer('duration')->nullable();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->string('image', 500)->nullable();
            $table->string('trailer_url', 500)->nullable();
            $table->decimal('rating', 3, 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
