<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rooms\SaveRoomRequest;
use App\Models\Asiento;
use App\Models\Sala;
use App\Models\SeatType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomsController extends Controller
{
    private function serialize(Sala $room): array
    {
        $seats = $room->asientos()
            ->with('tipo')
            ->orderBy('seat_row')
            ->orderBy('number')
            ->get();

        return [
            'id' => $room->id,
            'name' => $room->name,
            'rows' => (int) $room->seat_rows,
            'columns' => (int) $room->seat_cols,
            'seat_count' => $seats->count(),
            'seats' => $seats->map(fn (Asiento $seat) => [
                'id' => $seat->id,
                'row' => (int) $seat->seat_row,
                'number' => (int) $seat->number,
                'seat_type_id' => (int) $seat->seat_type_id,
                'type' => $seat->tipo?->name ?? SeatType::STANDARD,
            ])->values(),
        ];
    }

    public function index()
    {
        $rooms = Sala::query()
            ->withCount('asientos')
            ->orderBy('id')
            ->get(['id', 'name', 'seat_rows', 'seat_cols']);

        return response()->json($rooms->map(fn (Sala $room) => [
            'id' => $room->id,
            'name' => $room->name,
            'rows' => (int) $room->seat_rows,
            'columns' => (int) $room->seat_cols,
            'seat_count' => (int) $room->asientos_count,
        ]));
    }

    public function show(int $id)
    {
        $room = Sala::query()->find($id);
        if (!$room) {
            return response()->json(['message' => 'Sala no encontrada', 'details' => null], 404);
        }

        return response()->json($this->serialize($room));
    }

    public function store(SaveRoomRequest $request)
    {
        $room = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $room = Sala::query()->create([
                'name' => $data['name'],
                'seat_rows' => $data['rows'],
                'seat_cols' => $data['columns'],
            ]);
            $this->syncSeats($room, $data['seats']);

            return $room->refresh();
        });

        return response()->json($this->serialize($room), 201);
    }

    public function update(SaveRoomRequest $request, int $id)
    {
        $room = Sala::query()->find($id);
        if (!$room) {
            return response()->json(['message' => 'Sala no encontrada', 'details' => null], 404);
        }

        DB::transaction(function () use ($room, $request) {
            $data = $request->validated();
            $room->fill([
                'name' => $data['name'],
                'seat_rows' => $data['rows'],
                'seat_cols' => $data['columns'],
            ])->save();
            $this->syncSeats($room, $data['seats']);
        });

        return response()->json($this->serialize($room->refresh()));
    }

    public function destroy(int $id)
    {
        $room = Sala::query()->find($id);
        if (!$room) {
            return response()->json(['message' => 'Sala no encontrada', 'details' => null], 404);
        }

        $room->delete();

        return response()->noContent();
    }

    /**
     * @param  array<int, array{row:int, number:int, type:string}>  $seats
     */
    private function syncSeats(Sala $room, array $seats): void
    {
        $typeIds = SeatType::query()->pluck('id', 'name');
        $existing = Asiento::query()
            ->where('room_id', $room->id)
            ->get()
            ->keyBy(fn (Asiento $seat) => $seat->seat_row.'-'.$seat->number);

        $keepIds = [];

        foreach ($seats as $cell) {
            $typeId = $typeIds[$cell['type']] ?? null;
            if (!$typeId) {
                throw ValidationException::withMessages([
                    'seats' => "Tipo de asiento no válido: {$cell['type']}",
                ]);
            }

            $key = $cell['row'].'-'.$cell['number'];
            $current = $existing->get($key);

            if ($current) {
                if ((int) $current->seat_type_id !== (int) $typeId) {
                    $current->seat_type_id = $typeId;
                    $current->save();
                }
                $keepIds[] = $current->id;
                continue;
            }

            $created = Asiento::query()->create([
                'room_id' => $room->id,
                'seat_row' => $cell['row'],
                'number' => $cell['number'],
                'seat_type_id' => $typeId,
            ]);
            $keepIds[] = $created->id;
        }

        $toRemove = Asiento::query()
            ->where('room_id', $room->id)
            ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
            ->when($keepIds === [], fn ($query) => $query)
            ->get();

        if ($toRemove->isEmpty()) {
            return;
        }

        $blockedIds = DB::table('booking_seat')
            ->whereIn('seat_id', $toRemove->pluck('id'))
            ->pluck('seat_id')
            ->unique()
            ->values();

        if ($blockedIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'seats' => 'No se pueden quitar asientos que ya tienen reservas.',
            ]);
        }

        Asiento::query()->whereIn('id', $toRemove->pluck('id'))->delete();
    }
}
