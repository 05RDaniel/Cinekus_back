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

        DB::table('ticket_types')->updateOrInsert(['code' => 'adult'], ['code' => 'adult', 'name' => 'Adulto', 'price' => 8]);
        DB::table('ticket_types')->updateOrInsert(['code' => 'child'], ['code' => 'child', 'name' => 'Niño', 'price' => 5.5]);
        DB::table('ticket_types')->updateOrInsert(['code' => 'senior'], ['code' => 'senior', 'name' => 'Jubilado', 'price' => 6.5]);

        DB::table('seat_types')->updateOrInsert(['name' => 'standard'], ['name' => 'standard', 'label' => 'Regular', 'price' => 0]);
        DB::table('seat_types')->updateOrInsert(['name' => 'vip'], ['name' => 'vip', 'label' => 'VIP', 'price' => 2]);
        DB::table('seat_types')->updateOrInsert(['name' => 'accessible'], ['name' => 'accessible', 'label' => 'Accesible', 'price' => 0]);
        $standardSeatTypeId = DB::table('seat_types')->where('name', 'standard')->value('id');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@proyectocine.local'],
            [
                'username' => 'admin',
                'first_name' => 'Ana',
                'last_name' => 'García',
                'password' => 'admin123',
            ]
        );

        $regularUser = User::query()->updateOrCreate(
            ['email' => 'user@proyectocine.local'],
            [
                'username' => 'user',
                'first_name' => 'Luis',
                'last_name' => 'Martínez',
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

        $roomLayouts = [
            ['id' => 1, 'name' => 'Sala 1', 'seat_rows' => 5, 'seat_cols' => 8],
            ['id' => 2, 'name' => 'Sala 2', 'seat_rows' => 6, 'seat_cols' => 10],
            ['id' => 3, 'name' => 'Sala 3', 'seat_rows' => 5, 'seat_cols' => 10],
        ];

        $rooms = [];
        foreach ($roomLayouts as $layout) {
            $room = Sala::query()->updateOrCreate(
                ['id' => $layout['id']],
                [
                    'name' => $layout['name'],
                    'seat_rows' => $layout['seat_rows'],
                    'seat_cols' => $layout['seat_cols'],
                ]
            );

            for ($row = 1; $row <= $layout['seat_rows']; $row++) {
                for ($number = 1; $number <= $layout['seat_cols']; $number++) {
                    Asiento::query()->updateOrCreate(
                        ['room_id' => $room->id, 'seat_row' => $row, 'number' => $number],
                        ['seat_type_id' => $standardSeatTypeId]
                    );
                }
            }

            $rooms[] = $room;
        }

        $this->seedUpcomingSessions($rooms);
    }

    /**
     * @param  list<Sala>  $rooms
     */
    private function seedUpcomingSessions(array $rooms): void
    {
        $movies = Pelicula::query()
            ->orderByDesc('rating')
            ->orderBy('id')
            ->limit(4)
            ->get();

        if ($movies->isEmpty() || $rooms === []) {
            return;
        }

        DB::table('booking_ticket')->delete();
        DB::table('booking_seat')->delete();
        DB::table('bookings')->delete();
        Sesion::query()->delete();

        $spanishId = DB::table('languages')->where('code', 'es')->value('id');
        $sessionTypes = ['2d', '3d', '4d'];
        $startTimes = ['17:00:00', '18:15:00', '19:30:00', '20:45:00'];

        foreach ($movies->values() as $movieIndex => $movie) {
            for ($slot = 0; $slot < 3; $slot++) {
                Sesion::query()->create([
                    'movie_id' => $movie->id,
                    'room_id' => $rooms[$slot % count($rooms)]->id,
                    'language_id' => $spanishId,
                    'start_date' => now()->addDays($slot + 1)->toDateString(),
                    'start_time' => $startTimes[$movieIndex % count($startTimes)],
                    'session_type' => $sessionTypes[$slot],
                ]);
            }
        }
    }
}
