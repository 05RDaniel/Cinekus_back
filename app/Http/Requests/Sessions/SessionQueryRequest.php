<?php

namespace App\Http\Requests\Sessions;

use Illuminate\Foundation\Http\FormRequest;

class SessionQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movieId' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
