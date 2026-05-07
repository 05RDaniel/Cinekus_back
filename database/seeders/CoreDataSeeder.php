<?php

namespace Database\Seeders;

use App\Models\Asiento;
use App\Models\Pelicula;
use App\Models\Sala;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoreDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@proyectocine.local'],
            [
                'nombre' => 'admin',
                'password' => Hash::make('admin123'),
                'rol' => 'ADMIN',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'user@proyectocine.local'],
            [
                'nombre' => 'Usuario',
                'password' => Hash::make('user123'),
                'rol' => 'USER',
            ]
        );

        $sala1 = Sala::query()->updateOrCreate(['id' => 1], ['nombre' => 'Sala 1', 'filas' => 5, 'columnas' => 8]);
        $sala2 = Sala::query()->updateOrCreate(['id' => 2], ['nombre' => 'Sala 2', 'filas' => 6, 'columnas' => 10]);

        foreach ([$sala1, $sala2] as $sala) {
            for ($fila = 1; $fila <= $sala->filas; $fila++) {
                for ($numero = 1; $numero <= $sala->columnas; $numero++) {
                    Asiento::query()->updateOrCreate(
                        ['sala_id' => $sala->id, 'fila' => $fila, 'numero' => $numero],
                        []
                    );
                }
            }
        }

        $interstellar = Pelicula::query()->updateOrCreate(
            ['id' => 1],
            [
                'titulo' => 'Interstellar',
                'sinopsis' => 'Un grupo de exploradores viaja a traves de un agujero de gusano.',
                'duracion' => 169,
                'genero' => 'Ciencia Ficcion',
                'imagen' => 'https://picsum.photos/seed/interstellar/400/600',
                'fecha_estreno' => '2014-11-07',
            ]
        );

        $dune = Pelicula::query()->updateOrCreate(
            ['id' => 2],
            [
                'titulo' => 'Dune',
                'sinopsis' => 'El heredero de una familia noble lucha por el control de Arrakis.',
                'duracion' => 155,
                'genero' => 'Aventura',
                'imagen' => 'https://picsum.photos/seed/dune/400/600',
                'fecha_estreno' => '2021-10-22',
            ]
        );

        Sesion::query()->updateOrCreate(['id' => 1], [
            'pelicula_id' => $interstellar->id,
            'sala_id' => $sala1->id,
            'fecha' => '2026-04-10',
            'hora' => '18:00',
        ]);
        Sesion::query()->updateOrCreate(['id' => 2], [
            'pelicula_id' => $interstellar->id,
            'sala_id' => $sala1->id,
            'fecha' => '2026-04-10',
            'hora' => '21:30',
        ]);
        Sesion::query()->updateOrCreate(['id' => 3], [
            'pelicula_id' => $dune->id,
            'sala_id' => $sala2->id,
            'fecha' => '2026-04-11',
            'hora' => '20:00',
        ]);
    }
}
