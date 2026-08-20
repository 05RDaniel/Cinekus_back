<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asiento;
use App\Models\ReservaAsiento;
use App\Models\Sesion;

class SeatsController extends Controller
{
    public function bySession(int $id)
    {
        $session = Sesion::query()->find($id);
        if (!$session) {
            return response()->json(['message' => 'Sesión no encontrada', 'details' => null], 404);
        }

        $occupiedSeatIds = ReservaAsiento::query()
            ->join('bookings', 'bookings.id', '=', 'booking_seat.booking_id')
            ->where('bookings.session_id', $id)
            ->pluck('booking_seat.seat_id')
            ->toArray();

        $seats = Asiento::query()
            ->where('room_id', $session->room_id)
            ->orderBy('seat_row')
            ->orderBy('number')
            ->get()
            ->map(function (Asiento $seat) use ($occupiedSeatIds) {
                return [
                    'id' => $seat->id,
                    'room_id' => $seat->room_id,
                    'seat_row' => $seat->seat_row,
                    'number' => $seat->number,
                    'seat_type_id' => $seat->seat_type_id,
                    'occupied' => in_array($seat->id, $occupiedSeatIds, true),
                ];
            });

        if ($seats->isEmpty()) {
            return response()->json(['message' => 'No hay asientos para la sesión indicada', 'details' => null], 404);
        }

        return response()->json($seats);
    }
}
