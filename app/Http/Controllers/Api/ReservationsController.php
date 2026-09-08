<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\CreateReservationRequest;
use App\Http\Requests\Reservations\UpdateReservationRequest;
use App\Models\Asiento;
use App\Models\Reserva;
use App\Models\Sesion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationsController extends Controller
{
    private const DEFAULT_STATUS_ID = 1;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'userId' => ['sometimes', 'integer', 'min:1'],
            'sessionId' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = DB::table('bookings as b')
            ->join('sessions as s', 's.id', '=', 'b.session_id')
            ->join('movies as m', 'm.id', '=', 's.movie_id')
            ->join('users as u', 'u.id', '=', 'b.user_id')
            ->select([
                'b.id',
                'b.user_id',
                'b.session_id',
                'b.created_at',
                'b.status_id',
                's.start_date as session_start_date',
                's.start_time as session_start_time',
                'm.title as movie_title',
                'u.username as user_username',
            ])
            ->orderByDesc('b.id');

        if (!empty($filters['userId'])) {
            $query->where('b.user_id', $filters['userId']);
        }
        if (!empty($filters['sessionId'])) {
            $query->where('b.session_id', $filters['sessionId']);
        }

        return response()->json($query->get());
    }

    public function store(CreateReservationRequest $request)
    {
        $data = $request->validated();

        $authUser = $request->attributes->get('auth_user') ?? auth('api')->user();
        if (!$authUser instanceof User) {
            return response()->json(['message' => 'Debes iniciar sesión para reservar', 'details' => null], 401);
        }
        $data['user_id'] = $authUser->id;
        $session = Sesion::query()->find($data['session_id']);
        if (!$session) {
            return response()->json(['message' => 'Sesión no encontrada', 'details' => null], 404);
        }

        $seatIds = array_values(array_unique($data['seat_ids']));
        $sessionSeatIds = Asiento::query()->where('room_id', $session->room_id)->pluck('id')->toArray();
        foreach ($seatIds as $seatId) {
            if (!in_array($seatId, $sessionSeatIds, true)) {
                return response()->json(['message' => "El asiento {$seatId} no está disponible en esta sesión", 'details' => null], 409);
            }
        }

        $alreadyReserved = DB::table('booking_seat as bs')
            ->join('bookings as b', 'b.id', '=', 'bs.booking_id')
            ->where('b.session_id', $session->id)
            ->whereIn('bs.seat_id', $seatIds)
            ->pluck('bs.seat_id')
            ->toArray();

        if (!empty($alreadyReserved)) {
            $seatId = (int) $alreadyReserved[0];
            return response()->json(['message' => "El asiento {$seatId} no está disponible en esta sesión", 'details' => null], 409);
        }

        $statusId = $data['status_id'] ?? self::DEFAULT_STATUS_ID;

        $reservation = DB::transaction(function () use ($data, $seatIds, $statusId) {
            $userUpdates = array_filter([
                'first_name' => isset($data['first_name']) ? trim((string) $data['first_name']) : null,
                'last_name' => isset($data['last_name']) ? trim((string) $data['last_name']) : null,
            ], fn ($value) => $value !== null && $value !== '');
            if ($userUpdates !== []) {
                User::query()->whereKey($data['user_id'])->update($userUpdates);
            }

            $reservation = Reserva::query()->create([
                'user_id' => $data['user_id'],
                'session_id' => $data['session_id'],
                'created_at' => now(),
                'status_id' => $statusId,
            ]);
            foreach ($seatIds as $seatId) {
                DB::table('booking_seat')->insert([
                    'booking_id' => $reservation->id,
                    'seat_id' => $seatId,
                ]);
            }

            return $reservation;
        });

        return response()->json($reservation, 201);
    }

    public function byUser(int $userId)
    {
        if (!User::query()->whereKey($userId)->exists()) {
            return response()->json(['message' => 'Usuario no encontrado', 'details' => null], 404);
        }

        $reservations = DB::table('bookings as b')
            ->join('sessions as s', 's.id', '=', 'b.session_id')
            ->join('movies as m', 'm.id', '=', 's.movie_id')
            ->where('b.user_id', $userId)
            ->orderByDesc('b.id')
            ->get([
                'b.id',
                'b.user_id',
                'b.session_id',
                'b.created_at',
                'b.status_id',
                's.start_date as session_start_date',
                's.start_time as session_start_time',
                'm.title as movie_title',
            ]);

        return response()->json($reservations);
    }

    public function update(UpdateReservationRequest $request, int $id)
    {
        $reservation = Reserva::query()->find($id);
        if (!$reservation) {
            return response()->json(['message' => 'Reserva no encontrada', 'details' => null], 404);
        }

        $reservation->fill($request->validated())->save();

        return response()->json($reservation->only(['id', 'user_id', 'session_id', 'created_at', 'status_id']));
    }

    public function destroy(int $id)
    {
        $reservation = Reserva::query()->find($id);
        if (!$reservation) {
            return response()->json(['message' => 'Reserva no encontrada', 'details' => null], 404);
        }

        DB::transaction(function () use ($reservation) {
            DB::table('booking_seat')->where('booking_id', $reservation->id)->delete();
            $reservation->delete();
        });

        return response()->noContent();
    }
}
