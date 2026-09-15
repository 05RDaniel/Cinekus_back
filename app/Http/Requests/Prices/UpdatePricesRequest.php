<?php

namespace App\Http\Requests\Prices;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_types' => ['required', 'array', 'min:1'],
            'ticket_types.*.id' => ['required', 'integer', 'exists:ticket_types,id'],
            'ticket_types.*.price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'seat_types' => ['required', 'array', 'min:1'],
            'seat_types.*.id' => ['required', 'integer', 'exists:seat_types,id'],
            'seat_types.*.price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
