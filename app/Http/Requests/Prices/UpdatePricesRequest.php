<?php

namespace App\Http\Requests\Prices;

use App\Support\PriceCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mode = ['required', 'string', Rule::in([PriceCalculator::MODE_AMOUNT, PriceCalculator::MODE_PERCENT])];
        $price = ['required', 'numeric', 'min:-999999.99', 'max:999999.99'];

        return [
            'ticket_types' => ['required', 'array', 'min:1'],
            'ticket_types.*.id' => ['required', 'integer', 'exists:ticket_types,id'],
            'ticket_types.*.price' => $price,
            'ticket_types.*.price_mode' => $mode,
            'seat_types' => ['required', 'array', 'min:1'],
            'seat_types.*.id' => ['required', 'integer', 'exists:seat_types,id'],
            'seat_types.*.price' => $price,
            'seat_types.*.price_mode' => $mode,
        ];
    }
}
