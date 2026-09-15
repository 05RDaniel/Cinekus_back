<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->decimal('price', 8, 2);
        });

        DB::table('ticket_types')->insert([
            ['code' => 'adult', 'price' => 8.00],
            ['code' => 'child', 'price' => 5.50],
            ['code' => 'senior', 'price' => 6.50],
        ]);

        Schema::table('seat_types', function (Blueprint $table) {
            $table->decimal('price', 8, 2)->default(0);
        });

        DB::table('seat_types')->where('name', 'vip')->update(['price' => 2.00]);

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('total_price', 8, 2)->default(0);
        });

        Schema::create('booking_ticket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained('ticket_types')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 8, 2);
        });

        Schema::table('booking_seat', function (Blueprint $table) {
            $table->decimal('unit_price', 8, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('booking_seat', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });

        Schema::dropIfExists('booking_ticket');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('total_price');
        });

        Schema::table('seat_types', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::dropIfExists('ticket_types');
    }
};
