<?php

namespace App\Http\Requests\Movies;

use Illuminate\Foundation\Http\FormRequest;

class CreateMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'min:1'],
            'sinopsis' => ['required', 'string', 'min:1'],
            'duracion' => ['required', 'integer', 'min:1'],
            'genero' => ['required', 'string', 'min:1'],
            'imagen' => ['required', 'string', 'min:1'],
            'fecha_estreno' => ['required', 'date'],
        ];
    }
}
