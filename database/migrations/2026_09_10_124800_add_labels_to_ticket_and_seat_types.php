<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->string('name', 80)->default('')->after('code');
        });

        $ticketNames = [
            'adult' => 'Adulto',
            'child' => 'Niño',
            'senior' => 'Jubilado',
        ];
        foreach ($ticketNames as $code => $name) {
            DB::table('ticket_types')->where('code', $code)->update(['name' => $name]);
        }

        Schema::table('seat_types', function (Blueprint $table) {
            $table->string('label', 80)->default('')->after('name');
        });

        $seatLabels = [
            'standard' => 'Regular',
            'vip' => 'VIP',
            'accessible' => 'Accesible',
        ];
        foreach ($seatLabels as $name => $label) {
            DB::table('seat_types')->where('name', $name)->update(['label' => $label]);
        }
        DB::table('seat_types')->where('label', '')->update(['label' => DB::raw('name')]);
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn('name');
        });
        Schema::table('seat_types', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
