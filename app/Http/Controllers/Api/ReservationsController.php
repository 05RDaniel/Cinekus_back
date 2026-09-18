<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\CreateReservationRequest;
use App\Http\Requests\Reservations\UpdateReservationRequest;
use App\Models\Asiento;
use App\Models\Reserva;
use App\Models\SeatType;
use App\Models\Sesion;
use App\Models\TicketType;
use App\Models\User;
use App\Support\PriceCalculator;
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

        $query = $this->bookingListQuery()->orderByDesc('b.id');

        if (!empty($filters['userId'])) {
            $query->where('b.user_id', $filters['userId']);
        }
        if (!empty($filters['sessionId'])) {
            $query->where('b.session_id', $filters['sessionId']);
        }

        return response()->json($this->attachBookingExtras($query->get()));
    }

    public function show(int $id)
    {
        $row = $this->bookingListQuery()->where('b.id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Reserva no encontrada', 'details' => null], 404);
        }

        return response()->json($this->attachBookingExtras(collect([$row]))->first());
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

        $seatIds = array_values(array_unique(array_map('intval', $data['seat_ids'])));
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
            ->whereNotIn('b.status_id', function ($query) {
                $query->select('id')->from('booking_statuses')->where('name', 'cancelled');
            })
            ->pluck('bs.seat_id')
            ->toArray();

        if (!empty($alreadyReserved)) {
            $seatId = (int) $alreadyReserved[0];
            return response()->json(['message' => "El asiento {$seatId} no está disponible en esta sesión", 'details' => null], 409);
        }

        $ticketLines = [];
        $ticketCount = 0;
        foreach ($data['tickets'] as $line) {
            $typeId = (int) $line['ticket_type_id'];
            $quantity = (int) $line['quantity'];
            if (isset($ticketLines[$typeId])) {
                $ticketLines[$typeId] += $quantity;
            } else {
                $ticketLines[$typeId] = $quantity;
            }
            $ticketCount += $quantity;
        }

        if ($ticketCount !== count($seatIds)) {
            return response()->json([
                'message' => 'El número de entradas debe coincidir con el de asientos',
                'details' => null,
            ], 422);
        }

        $allTicketTypes = TicketType::query()->orderBy('id')->get();
        $ticketTypes = $allTicketTypes->keyBy('id');
        foreach (array_keys($ticketLines) as $typeId) {
            if (!$ticketTypes->has($typeId)) {
                return response()->json(['message' => 'Tipo de entrada no válido', 'details' => null], 422);
            }
        }

        $seats = Asiento::query()->whereIn('id', $seatIds)->get(['id', 'seat_type_id']);
        $seatTypeIds = $seats->pluck('seat_type_id')->unique()->all();
        $seatTypes = SeatType::query()->whereIn('id', $seatTypeIds)->get()->keyBy('id');

        $reference = PriceCalculator::ticketReferenceAmount($allTicketTypes);
        $ticketsTotal = 0.0;
        $ticketUnitPrices = [];
        foreach ($ticketLines as $typeId => $quantity) {
            $unit = PriceCalculator::ticketUnit($ticketTypes->get($typeId), $reference);
            $ticketUnitPrices[$typeId] = $unit;
            $ticketsTotal += $unit * $quantity;
        }

        $averageTicket = $ticketCount > 0 ? $ticketsTotal / $ticketCount : 0.0;
        $seatsTotal = 0.0;
        $seatPrices = [];
        foreach ($seats as $seat) {
            $unit = PriceCalculator::seatUnit($seatTypes->get($seat->seat_type_id), $averageTicket);
            $seatPrices[(int) $seat->id] = $unit;
            $seatsTotal += $unit;
        }

        $totalPrice = PriceCalculator::total($ticketsTotal, $seatsTotal);

        $firstName = trim((string) $data['first_name']);
        $lastName = trim((string) $data['last_name']);
        $secondLastName = isset($data['second_last_name']) ? trim((string) $data['second_last_name']) : '';
        $buyerEmail = strtolower(trim((string) $data['email']));

        $emailTaken = User::query()
            ->where('email', $buyerEmail)
            ->where('id', '!=', $authUser->id)
            ->exists();
        if ($emailTaken) {
            return response()->json(['message' => 'Ese e-mail ya está en uso', 'details' => null], 422);
        }

        $statusId = $data['status_id'] ?? self::DEFAULT_STATUS_ID;

        $reservation = DB::transaction(function () use (
            $data,
            $seatIds,
            $statusId,
            $ticketLines,
            $ticketUnitPrices,
            $seatPrices,
            $totalPrice,
            $firstName,
            $lastName,
            $secondLastName,
            $buyerEmail
        ) {
            User::query()->whereKey($data['user_id'])->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'second_last_name' => $secondLastName !== '' ? $secondLastName : null,
                'email' => $buyerEmail,
            ]);

            $reservation = Reserva::query()->create([
                'user_id' => $data['user_id'],
                'session_id' => $data['session_id'],
                'created_at' => now(),
                'status_id' => $statusId,
                'total_price' => $totalPrice,
                'buyer_first_name' => $firstName,
                'buyer_last_name' => $lastName,
                'buyer_second_last_name' => $secondLastName !== '' ? $secondLastName : null,
                'buyer_email' => $buyerEmail,
            ]);
            foreach ($seatIds as $seatId) {
                DB::table('booking_seat')->insert([
                    'booking_id' => $reservation->id,
                    'seat_id' => $seatId,
                    'unit_price' => $seatPrices[$seatId] ?? 0,
                ]);
            }
            foreach ($ticketLines as $typeId => $quantity) {
                DB::table('booking_ticket')->insert([
                    'booking_id' => $reservation->id,
                    'ticket_type_id' => $typeId,
                    'quantity' => $quantity,
                    'unit_price' => $ticketUnitPrices[$typeId] ?? 0,
                ]);
            }

            return $reservation;
        });

        return response()->json($this->bookingPayload($reservation->id), 201);
    }

    public function byUser(Request $request, int $userId)
    {
        $authUser = $this->authUser($request);
        if (!$authUser) {
            return response()->json(['message' => 'Debes iniciar sesión', 'details' => null], 401);
        }
        if ((int) $authUser->id !== $userId && $authUser->rol !== 'ADMIN') {
            return response()->json(['message' => 'No puedes ver las reservas de otro usuario', 'details' => null], 403);
        }
        if (!User::query()->whereKey($userId)->exists()) {
            return response()->json(['message' => 'Usuario no encontrado', 'details' => null], 404);
        }

        $reservations = DB::table('bookings as b')
            ->join('sessions as s', 's.id', '=', 'b.session_id')
            ->join('movies as m', 'm.id', '=', 's.movie_id')
            ->join('booking_statuses as st', 'st.id', '=', 'b.status_id')
            ->where('b.user_id', $userId)
            ->orderByDesc('b.id')
            ->get([
                'b.id',
                'b.user_id',
                'b.session_id',
                'b.created_at',
                'b.status_id',
                'st.name as status',
                'b.total_price',
                'b.buyer_first_name',
                'b.buyer_last_name',
                'b.buyer_second_last_name',
                'b.buyer_email',
                's.start_date as session_start_date',
                's.start_time as session_start_time',
                'm.title as movie_title',
            ]);

        return response()->json($this->withSeats($reservations));
    }

    public function cancel(Request $request, int $id)
    {
        $authUser = $this->authUser($request);
        if (!$authUser) {
            return response()->json(['message' => 'Debes iniciar sesión', 'details' => null], 401);
        }

        $reservation = Reserva::query()->find($id);
        if (!$reservation) {
            return response()->json(['message' => 'Reserva no encontrada', 'details' => null], 404);
        }
        if ((int) $reservation->user_id !== (int) $authUser->id && $authUser->rol !== 'ADMIN') {
            return response()->json(['message' => 'No puedes cancelar esta reserva', 'details' => null], 403);
        }

        $cancelledId = (int) DB::table('booking_statuses')->where('name', 'cancelled')->value('id');
        if (!$cancelledId) {
            return response()->json(['message' => 'Estado cancelled no configurado', 'details' => null], 500);
        }
        if ((int) $reservation->status_id === $cancelledId) {
            return response()->json(['message' => 'La reserva ya está cancelada', 'details' => null], 409);
        }

        $reservation->status_id = $cancelledId;
        $reservation->save();

        return response()->json($this->bookingPayload($reservation->id));
    }

    public function update(UpdateReservationRequest $request, int $id)
    {
        $reservation = Reserva::query()->find($id);
        if (!$reservation) {
            return response()->json(['message' => 'Reserva no encontrada', 'details' => null], 404);
        }

        $data = $request->validated();
        if (!empty($data['status'])) {
            $statusId = (int) DB::table('booking_statuses')->where('name', $data['status'])->value('id');
        } else {
            $statusId = (int) $data['status_id'];
        }
        if (!$statusId) {
            return response()->json(['message' => 'Estado no válido', 'details' => null], 422);
        }

        $statusName = (string) DB::table('booking_statuses')->where('id', $statusId)->value('name');
        if ($statusName === 'confirmed' && (int) $reservation->status_id !== $statusId) {
            $seatIds = DB::table('booking_seat')->where('booking_id', $reservation->id)->pluck('seat_id')->map(fn ($seatId) => (int) $seatId)->all();
            if ($seatIds !== []) {
                $alreadyReserved = DB::table('booking_seat as bs')
                    ->join('bookings as b', 'b.id', '=', 'bs.booking_id')
                    ->where('b.session_id', $reservation->session_id)
                    ->where('b.id', '!=', $reservation->id)
                    ->whereIn('bs.seat_id', $seatIds)
                    ->whereNotIn('b.status_id', function ($query) {
                        $query->select('id')->from('booking_statuses')->where('name', 'cancelled');
                    })
                    ->pluck('bs.seat_id')
                    ->toArray();
                if (!empty($alreadyReserved)) {
                    $seatId = (int) $alreadyReserved[0];
                    return response()->json([
                        'message' => "No se puede confirmar: el asiento {$seatId} ya está reservado en esta sesión",
                        'details' => null,
                    ], 409);
                }
            }
        }

        $reservation->status_id = $statusId;
        $reservation->save();

        return response()->json($this->bookingPayload($reservation->id));
    }

    public function destroy(int $id)
    {
        $reservation = Reserva::query()->find($id);
        if (!$reservation) {
            return response()->json(['message' => 'Reserva no encontrada', 'details' => null], 404);
        }

        DB::transaction(function () use ($reservation) {
            DB::table('booking_ticket')->where('booking_id', $reservation->id)->delete();
            DB::table('booking_seat')->where('booking_id', $reservation->id)->delete();
            $reservation->delete();
        });

        return response()->noContent();
    }

    private function bookingListQuery()
    {
        return DB::table('bookings as b')
            ->join('sessions as s', 's.id', '=', 'b.session_id')
            ->join('movies as m', 'm.id', '=', 's.movie_id')
            ->leftJoin('users as u', 'u.id', '=', 'b.user_id')
            ->join('booking_statuses as st', 'st.id', '=', 'b.status_id')
            ->select([
                'b.id',
                'b.user_id',
                'b.session_id',
                'b.created_at',
                'b.status_id',
                'st.name as status',
                'b.total_price',
                'b.buyer_first_name',
                'b.buyer_last_name',
                'b.buyer_second_last_name',
                'b.buyer_email',
                's.start_date as session_start_date',
                's.start_time as session_start_time',
                'm.title as movie_title',
                'u.username as user_username',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(int $id): array
    {
        $row = $this->bookingListQuery()->where('b.id', $id)->first();
        if (!$row) {
            return ['id' => $id];
        }

        return $this->attachBookingExtras(collect([$row]))->first();
    }

    private function authUser(Request $request): ?User
    {
        $user = $request->attributes->get('auth_user') ?? auth('api')->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $reservations
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function attachBookingExtras($reservations)
    {
        $ids = $reservations->pluck('id')->all();
        $seatsByBooking = [];
        $ticketsByBooking = [];
        if ($ids !== []) {
            $seatRows = DB::table('booking_seat as bs')
                ->join('seats as st', 'st.id', '=', 'bs.seat_id')
                ->whereIn('bs.booking_id', $ids)
                ->orderBy('st.seat_row')
                ->orderBy('st.number')
                ->get(['bs.booking_id', 'st.seat_row', 'st.number']);
            foreach ($seatRows as $row) {
                $seatsByBooking[(int) $row->booking_id][] = $row->seat_row.'-'.$row->number;
            }

            $ticketRows = DB::table('booking_ticket as bt')
                ->join('ticket_types as tt', 'tt.id', '=', 'bt.ticket_type_id')
                ->whereIn('bt.booking_id', $ids)
                ->orderBy('tt.id')
                ->get([
                    'bt.booking_id',
                    'bt.ticket_type_id',
                    'tt.code',
                    'tt.name',
                    'bt.quantity',
                    'bt.unit_price',
                ]);
            foreach ($ticketRows as $row) {
                $ticketsByBooking[(int) $row->booking_id][] = [
                    'ticket_type_id' => (int) $row->ticket_type_id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'quantity' => (int) $row->quantity,
                    'unit_price' => (float) $row->unit_price,
                ];
            }
        }

        return $reservations->map(function ($reservation) use ($seatsByBooking, $ticketsByBooking) {
            $payload = (array) $reservation;
            $payload['seats'] = $seatsByBooking[(int) $reservation->id] ?? [];
            $payload['tickets'] = $ticketsByBooking[(int) $reservation->id] ?? [];

            return $payload;
        })->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $reservations
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function withSeats($reservations)
    {
        return $this->attachBookingExtras($reservations);
    }
}
