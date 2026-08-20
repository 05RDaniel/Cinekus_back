<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rooms\CreateRoomRequest;
use App\Http\Requests\Rooms\UpdateRoomRequest;
use App\Models\Sala;

class RoomsController extends Controller
{
    public function index()
    {
        return response()->json(Sala::query()->orderBy('id')->get(['id', 'name']));
    }

    public function store(CreateRoomRequest $request)
    {
        $room = Sala::query()->create($request->validated());

        return response()->json($room->only(['id', 'name']), 201);
    }

    public function update(UpdateRoomRequest $request, int $id)
    {
        $room = Sala::query()->find($id);
        if (!$room) {
            return response()->json(['message' => 'Sala no encontrada', 'details' => null], 404);
        }

        $room->fill($request->validated())->save();

        return response()->json($room->only(['id', 'name']));
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
}
