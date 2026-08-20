<?php

namespace Database\Seeders;

use App\Models\Asiento;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoreDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LanguagesAndGenresSeeder::class);

        DB::table('roles')->updateOrInsert(['name' => 'ADMIN'], ['name' => 'ADMIN']);
        DB::table('roles')->updateOrInsert(['name' => 'USER'], ['name' => 'USER']);

        $adminRoleId = DB::table('roles')->where('name', 'ADMIN')->value('id');
        $userRoleId = DB::table('roles')->where('name', 'USER')->value('id');

        DB::table('booking_statuses')->updateOrInsert(['name' => 'confirmed'], ['name' => 'confirmed']);
        DB::table('booking_statuses')->updateOrInsert(['name' => 'cancelled'], ['name' => 'cancelled']);

        DB::table('seat_types')->updateOrInsert(['name' => 'standard'], ['name' => 'standard']);
        $standardSeatTypeId = DB::table('seat_types')->where('name', 'standard')->value('id');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@proyectocine.local'],
            [
                'username' => 'admin',
                'password' => 'admin123',
            ]
        );

        $regularUser = User::query()->updateOrCreate(
            ['email' => 'user@proyectocine.local'],
            [
                'username' => 'user',
                'password' => 'user123',
            ]
        );

        DB::table('users_roles')->updateOrInsert(
            ['user_id' => $admin->id, 'rol_id' => $adminRoleId],
            ['user_id' => $admin->id, 'rol_id' => $adminRoleId]
        );
        DB::table('users_roles')->updateOrInsert(
            ['user_id' => $regularUser->id, 'rol_id' => $userRoleId],
            ['user_id' => $regularUser->id, 'rol_id' => $userRoleId]
        );

        $room1 = Sala::query()->updateOrCreate(['id' => 1], ['name' => 'Sala 1']);
        $room2 = Sala::query()->updateOrCreate(['id' => 2], ['name' => 'Sala 2']);

        foreach ([$room1, $room2] as $room) {
            $rows = $room->id === 1 ? 5 : 6;
            $columns = $room->id === 1 ? 8 : 10;

            for ($row = 1; $row <= $rows; $row++) {
                for ($number = 1; $number <= $columns; $number++) {
                    Asiento::query()->updateOrCreate(
                        ['room_id' => $room->id, 'seat_row' => $row, 'number' => $number],
                        ['seat_type_id' => $standardSeatTypeId]
                    );
                }
            }
        }

        $interstellar = Pelicula::query()->updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Interstellar',
                'sinopsis' => 'Un grupo de exploradores viaja a través de un agujero de gusano.',
                'duration' => 169,
                'release_year' => 2014,
                'image' => 'https://picsum.photos/seed/interstellar/400/600',
            ]
        );

        $dune = Pelicula::query()->updateOrCreate(
            ['id' => 2],
            [
                'title' => 'Dune',
                'sinopsis' => 'El heredero de una familia noble lucha por el control de Arrakis.',
                'duration' => 155,
                'release_year' => 2021,
                'image' => 'https://picsum.photos/seed/dune/400/600',
            ]
        );

        $spanishId = DB::table('languages')->where('code', 'es')->value('id');

        Sesion::query()->updateOrCreate(['id' => 1], [
            'movie_id' => $interstellar->id,
            'room_id' => $room1->id,
            'language_id' => $spanishId,
            'start_date' => '2026-04-10',
            'start_time' => '18:00:00',
            'session_type' => '2d',
        ]);
        Sesion::query()->updateOrCreate(['id' => 2], [
            'movie_id' => $interstellar->id,
            'room_id' => $room1->id,
            'language_id' => $spanishId,
            'start_date' => '2026-04-10',
            'start_time' => '21:30:00',
            'session_type' => '2d',
        ]);
        Sesion::query()->updateOrCreate(['id' => 3], [
            'movie_id' => $dune->id,
            'room_id' => $room2->id,
            'language_id' => $spanishId,
            'start_date' => '2026-04-11',
            'start_time' => '20:00:00',
            'session_type' => '2d',
        ]);
    }
}
