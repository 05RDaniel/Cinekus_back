<?php

namespace App\Http\Requests\Movies;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'string', 'min:1'],
            'sinopsis' => ['sometimes', 'string', 'min:1'],
            'duracion' => ['sometimes', 'integer', 'min:1'],
            'genero' => ['sometimes', 'string', 'min:1'],
            'imagen' => ['sometimes', 'string', 'min:1'],
            'fecha_estreno' => ['sometimes', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $allowed = ['titulo', 'sinopsis', 'duracion', 'genero', 'imagen', 'fecha_estreno'];
            $present = array_intersect($allowed, array_keys($this->all()));
            if (count($present) === 0) {
                $validator->errors()->add('body', 'Debes enviar campos para actualizar');
            }
        });
    }
}
