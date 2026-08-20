<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->unsignedInteger('seat_row');
            $table->unsignedInteger('number');
            $table->foreignId('seat_type_id')->constrained('seat_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
