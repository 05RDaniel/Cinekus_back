<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prices\StoreSeatTypeRequest;
use App\Models\SeatType;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;

class SeatTypesController extends Controller
{
    public function index()
    {
        return response()->json(
            SeatType::query()->orderBy('id')->get(['id', 'name', 'label', 'price', 'price_mode'])
        );
    }

    public function store(StoreSeatTypeRequest $request)
    {
        $data = $request->validated();
        $label = trim($data['name']);

        $type = SeatType::query()->create([
            'name' => UniqueSlug::make('seat_types', 'name', $label, 'asiento'),
            'label' => $label,
            'price' => round((float) $data['price'], 2),
            'price_mode' => $data['price_mode'],
        ]);

        return response()->json($type->only(['id', 'name', 'label', 'price', 'price_mode']), 201);
    }

    public function destroy(int $id)
    {
        $type = SeatType::query()->find($id);
        if (!$type) {
            return response()->json(['message' => 'Tipo de asiento no encontrado', 'details' => null], 404);
        }

        if (SeatType::query()->count() <= 1) {
            return response()->json(['message' => 'Debe quedar al menos un tipo de asiento', 'details' => null], 409);
        }

        if (DB::table('seats')->where('seat_type_id', $id)->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: hay asientos de este tipo en alguna sala',
                'details' => null,
            ], 409);
        }

        $type->delete();

        return response()->noContent();
    }
}
