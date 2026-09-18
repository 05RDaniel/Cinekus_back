<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->string('price_mode', 16)->default('amount')->after('price');
        });

        Schema::table('seat_types', function (Blueprint $table) {
            $table->string('price_mode', 16)->default('amount')->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn('price_mode');
        });

        Schema::table('seat_types', function (Blueprint $table) {
            $table->dropColumn('price_mode');
        });
    }
};
