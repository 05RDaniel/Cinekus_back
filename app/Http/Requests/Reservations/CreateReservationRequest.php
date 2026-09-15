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
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'session_id' => ['required', 'integer', 'min:1'],
            'seat_ids' => ['required', 'array', 'min:1'],
            'seat_ids.*' => ['integer', 'min:1'],
            'tickets' => ['required', 'array', 'min:1'],
            'tickets.*.ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'tickets.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'status_id' => ['sometimes', 'integer', 'min:1'],
            'first_name' => ['required', 'string', 'min:1', 'max:255'],
            'last_name' => ['required', 'string', 'min:1', 'max:255'],
            'second_last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
