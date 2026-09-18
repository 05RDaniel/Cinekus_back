<?php

namespace App\Http\Requests\Prices;

use App\Support\PriceCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:80'],
            'price' => ['required', 'numeric', 'min:-999999.99', 'max:999999.99'],
            'price_mode' => ['required', 'string', Rule::in([PriceCalculator::MODE_AMOUNT, PriceCalculator::MODE_PERCENT])],
        ];
    }
}
