<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prices\UpdatePricesRequest;
use App\Models\SeatType;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;

class PricesController extends Controller
{
    public function index()
    {
        return response()->json($this->payload());
    }

    public function update(UpdatePricesRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            foreach ($data['ticket_types'] as $row) {
                TicketType::query()->whereKey($row['id'])->update([
                    'price' => round((float) $row['price'], 2),
                    'price_mode' => $row['price_mode'],
                ]);
            }

            foreach ($data['seat_types'] as $row) {
                SeatType::query()->whereKey($row['id'])->update([
                    'price' => round((float) $row['price'], 2),
                    'price_mode' => $row['price_mode'],
                ]);
            }
        });

        return response()->json($this->payload());
    }

    /**
     * @return array{ticket_types: \Illuminate\Support\Collection, seat_types: \Illuminate\Support\Collection}
     */
    private function payload(): array
    {
        return [
            'ticket_types' => TicketType::query()->orderBy('id')->get(['id', 'code', 'name', 'price', 'price_mode']),
            'seat_types' => SeatType::query()->orderBy('id')->get(['id', 'name', 'label', 'price', 'price_mode']),
        ];
    }
}
