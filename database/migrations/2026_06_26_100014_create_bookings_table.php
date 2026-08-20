<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('sessions')->cascadeOnDelete();
            $table->dateTime('created_at')->useCurrent();
            $table->foreignId('status_id')->constrained('booking_statuses')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
