<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) $this->route('id');

        return [
            'username' => ['sometimes', 'string', 'min:1', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'string', 'min:6'],
            'rol' => ['sometimes', 'string', Rule::in(['ADMIN', 'USER'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $allowed = ['username', 'email', 'password', 'rol'];
            $present = array_intersect($allowed, array_keys($this->all()));
            if (count($present) === 0) {
                $validator->errors()->add('body', 'Debes enviar campos para actualizar');
            }
        });
    }
}
