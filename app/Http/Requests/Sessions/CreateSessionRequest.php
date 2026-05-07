<?php

namespace App\Http\Requests\Sessions;

use Illuminate\Foundation\Http\FormRequest;

class CreateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pelicula_id' => ['required', 'integer', 'min:1'],
            'sala_id' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
        ];
    }
}
