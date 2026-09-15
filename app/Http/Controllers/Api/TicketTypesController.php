<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prices\StoreTicketTypeRequest;
use App\Models\TicketType;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;

class TicketTypesController extends Controller
{
    public function index()
    {
        return response()->json(
            TicketType::query()->orderBy('id')->get(['id', 'code', 'name', 'price'])
        );
    }

    public function store(StoreTicketTypeRequest $request)
    {
        $data = $request->validated();
        $name = trim($data['name']);

        $type = TicketType::query()->create([
            'code' => UniqueSlug::make('ticket_types', 'code', $name, 'entrada'),
            'name' => $name,
            'price' => round((float) $data['price'], 2),
        ]);

        return response()->json($type->only(['id', 'code', 'name', 'price']), 201);
    }

    public function destroy(int $id)
    {
        $type = TicketType::query()->find($id);
        if (!$type) {
            return response()->json(['message' => 'Tipo de entrada no encontrado', 'details' => null], 404);
        }

        if (TicketType::query()->count() <= 1) {
            return response()->json(['message' => 'Debe quedar al menos un tipo de entrada', 'details' => null], 409);
        }

        if (DB::table('booking_ticket')->where('ticket_type_id', $id)->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: hay reservas con este tipo de entrada',
                'details' => null,
            ], 409);
        }

        $type->delete();

        return response()->noContent();
    }
}
