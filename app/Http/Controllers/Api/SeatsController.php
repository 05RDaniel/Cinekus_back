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
            return response()->json(['message' => 'Sesion no encontrada', 'details' => null], 404);
        }

        $occupiedSeatIds = ReservaAsiento::query()
            ->join('reservas', 'reservas.id', '=', 'reserva_asientos.reserva_id')
            ->where('reservas.sesion_id', $id)
            ->pluck('reserva_asientos.asiento_id')
            ->toArray();

        $seats = Asiento::query()
            ->where('sala_id', $session->sala_id)
            ->orderBy('fila')
            ->orderBy('numero')
            ->get()
            ->map(function (Asiento $seat) use ($occupiedSeatIds) {
                return [
                    'id' => $seat->id,
                    'sala_id' => $seat->sala_id,
                    'fila' => $seat->fila,
                    'numero' => $seat->numero,
                    'ocupado' => in_array($seat->id, $occupiedSeatIds, true),
                ];
            });

        if ($seats->isEmpty()) {
            return response()->json(['message' => 'No hay asientos para la sesion indicada', 'details' => null], 404);
        }

        return response()->json($seats);
    }
}
