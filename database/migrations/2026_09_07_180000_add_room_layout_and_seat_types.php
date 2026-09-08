<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedTinyInteger('seat_rows')->default(0)->after('name');
            $table->unsignedTinyInteger('seat_cols')->default(0)->after('seat_rows');
        });

        Schema::table('seats', function (Blueprint $table) {
            $table->unique(['room_id', 'seat_row', 'number'], 'seats_room_position_unique');
        });

        foreach (['vip', 'accessible'] as $name) {
            DB::table('seat_types')->updateOrInsert(['name' => $name], ['name' => $name]);
        }

        $rooms = DB::table('seats')
            ->selectRaw('room_id, MAX(seat_row) as max_row, MAX(number) as max_col')
            ->groupBy('room_id')
            ->get();

        foreach ($rooms as $room) {
            DB::table('rooms')->where('id', $room->room_id)->update([
                'seat_rows' => (int) $room->max_row,
                'seat_cols' => (int) $room->max_col,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->dropUnique('seats_room_position_unique');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['seat_rows', 'seat_cols']);
        });
    }
};
