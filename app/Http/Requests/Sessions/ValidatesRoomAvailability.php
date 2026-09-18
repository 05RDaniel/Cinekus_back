<?php

namespace App\Http\Requests\Sessions;

use App\Models\Pelicula;
use App\Models\Sesion;
use Illuminate\Validation\Validator;

trait ValidatesRoomAvailability
{
    protected function validateRoomAvailability(Validator $validator, ?Sesion $current = null): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $roomId = $this->input('room_id', $current?->room_id);
        $movieId = $this->input('movie_id', $current?->movie_id);
        $startDate = $this->input('start_date', $current?->start_date?->format('Y-m-d'));
        $startTime = $this->input('start_time', $current?->start_time);

        if (!$roomId || !$movieId || !$startDate || !$startTime) {
            return;
        }

        $movie = Pelicula::query()->find($movieId);
        if (!$movie) {
            return;
        }

        $occupied = Sesion::roomIsOccupied(
            (int) $roomId,
            (string) $startDate,
            (string) $startTime,
            Sesion::durationMinutes($movie->duration),
            $current?->id
        );

        if ($occupied) {
            $validator->errors()->add('room_id', 'Esta sala ya tiene una película a esa hora');
        }
    }
}
