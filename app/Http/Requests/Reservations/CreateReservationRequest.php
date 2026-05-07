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
            'usuario_id' => ['required', 'integer', 'min:1'],
            'sesion_id' => ['required', 'integer', 'min:1'],
            'asientos' => ['required', 'array', 'min:1'],
            'asientos.*' => ['integer', 'min:1'],
        ];
    }
}
