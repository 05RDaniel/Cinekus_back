<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'session_id' => ['required', 'integer', 'min:1'],
            'seat_ids' => ['required', 'array', 'min:1'],
            'seat_ids.*' => ['integer', 'min:1'],
            'status_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
