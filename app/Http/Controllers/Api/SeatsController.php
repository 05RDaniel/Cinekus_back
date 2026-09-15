<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asiento;
use App\Models\ReservaAsiento;
use App\Models\Sesion;
use Illuminate\Support\Facades\DB;

class SeatsController extends Controller
{
    public function bySession(int $id)
    {
        $session = Sesion::query()->with('sala')->find($id);
        if (!$session) {
            return response()->json(['message' => 'Sesión no encontrada', 'details' => null], 404);
        }

        $occupiedSeatIds = ReservaAsiento::query()
            ->join('bookings', 'bookings.id', '=', 'booking_seat.booking_id')
            ->where('bookings.session_id', $id)
            ->whereNotIn('bookings.status_id', function ($query) {
                $query->select('id')->from('booking_statuses')->where('name', 'cancelled');
            })
            ->pluck('booking_seat.seat_id')
            ->toArray();

        $room = $session->sala;
        $typeNames = DB::table('seat_types')->pluck('name', 'id');

        $seats = Asiento::query()
            ->where('room_id', $session->room_id)
            ->orderBy('seat_row')
            ->orderBy('number')
            ->get()
            ->map(function (Asiento $seat) use ($occupiedSeatIds, $typeNames) {
                return [
                    'id' => $seat->id,
                    'room_id' => $seat->room_id,
                    'seat_row' => $seat->seat_row,
                    'number' => $seat->number,
                    'seat_type_id' => $seat->seat_type_id,
                    'seat_type' => $typeNames[$seat->seat_type_id] ?? 'standard',
                    'occupied' => in_array($seat->id, $occupiedSeatIds, true),
                ];
            });

        if ($seats->isEmpty()) {
            return response()->json(['message' => 'No hay asientos para la sesión indicada', 'details' => null], 404);
        }

        $rows = (int) ($room?->seat_rows ?: $seats->max('seat_row'));
        $columns = (int) ($room?->seat_cols ?: $seats->max('number'));

        return response()->json([
            'rows' => $rows,
            'columns' => $columns,
            'seats' => $seats->values(),
        ]);
    }
}
