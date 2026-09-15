<?php

namespace App\Http\Requests\Prices;

use Illuminate\Foundation\Http\FormRequest;

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
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
