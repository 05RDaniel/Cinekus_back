<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required_without:status_id', 'string', Rule::in(['confirmed', 'cancelled'])],
            'status_id' => ['required_without:status', 'integer', 'min:1', 'exists:booking_statuses,id'],
        ];
    }
}
