<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:1', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (!array_key_exists('name', $this->all())) {
                $validator->errors()->add('body', 'Debes enviar campos para actualizar');
            }
        });
    }
}
