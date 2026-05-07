<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\CreateReservationRequest;
use App\Models\Asiento;
use App\Models\Reserva;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReservationsController extends Controller
{
    public function store(CreateReservationRequest $request)
    {
        $data = $request->validated();

        if (!User::query()->whereKey($data['usuario_id'])->exists()) {
            return response()->json(['message' => 'Usuario no encontrado', 'details' => null], 404);
        }
        $session = Sesion::query()->find($data['sesion_id']);
        if (!$session) {
            return response()->json(['message' => 'Sesion no encontrada', 'details' => null], 404);
        }

        $seatIds = array_values(array_unique($data['asientos']));
        $sessionSeatIds = Asiento::query()->where('sala_id', $session->sala_id)->pluck('id')->toArray();
        foreach ($seatIds as $seatId) {
            if (!in_array($seatId, $sessionSeatIds, true)) {
                return response()->json(['message' => "El asiento {$seatId} no esta disponible en esta sesion", 'details' => null], 409);
            }
        }

        $alreadyReserved = DB::table('reserva_asientos as ra')
            ->join('reservas as r', 'r.id', '=', 'ra.reserva_id')
            ->where('r.sesion_id', $session->id)
            ->whereIn('ra.asiento_id', $seatIds)
            ->pluck('ra.asiento_id')
            ->toArray();

        if (!empty($alreadyReserved)) {
            $seatId = (int) $alreadyReserved[0];
            return response()->json(['message' => "El asiento {$seatId} no esta disponible en esta sesion", 'details' => null], 409);
        }

        $reservation = DB::transaction(function () use ($data, $seatIds) {
            $reservation = Reserva::query()->create([
                'usuario_id' => $data['usuario_id'],
                'sesion_id' => $data['sesion_id'],
                'fecha_reserva' => now(),
            ]);
            foreach ($seatIds as $seatId) {
                DB::table('reserva_asientos')->insert([
                    'reserva_id' => $reservation->id,
                    'asiento_id' => $seatId,
                ]);
            }
            return $reservation;
        });

        return response()->json($reservation, 201);
    }

    public function byUser(int $usuarioId)
    {
        if (!User::query()->whereKey($usuarioId)->exists()) {
            return response()->json(['message' => 'Usuario no encontrado', 'details' => null], 404);
        }

        $reservations = Reserva::query()
            ->join('sesiones', 'sesiones.id', '=', 'reservas.sesion_id')
            ->join('peliculas', 'peliculas.id', '=', 'sesiones.pelicula_id')
            ->where('reservas.usuario_id', $usuarioId)
            ->orderByDesc('reservas.id')
            ->get([
                'reservas.id',
                'reservas.usuario_id',
                'reservas.sesion_id',
                'reservas.fecha_reserva',
                'sesiones.fecha as sesion_fecha',
                'sesiones.hora as sesion_hora',
                'peliculas.titulo as pelicula_titulo',
            ]);

        return response()->json($reservations);
    }
}
