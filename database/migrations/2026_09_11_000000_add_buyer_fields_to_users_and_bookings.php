<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('second_last_name')->nullable()->after('last_name');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('buyer_first_name')->nullable()->after('total_price');
            $table->string('buyer_last_name')->nullable()->after('buyer_first_name');
            $table->string('buyer_second_last_name')->nullable()->after('buyer_last_name');
            $table->string('buyer_email')->nullable()->after('buyer_second_last_name');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_first_name',
                'buyer_last_name',
                'buyer_second_last_name',
                'buyer_email',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('second_last_name');
        });
    }
};
