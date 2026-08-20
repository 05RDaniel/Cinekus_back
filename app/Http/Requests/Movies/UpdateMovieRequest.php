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
            'title' => ['sometimes', 'string', 'min:1', 'max:255'],
            'sinopsis' => ['sometimes', 'nullable', 'string'],
            'duration' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'release_year' => ['sometimes', 'nullable', 'integer', 'min:1800', 'max:2100'],
            'image' => ['sometimes', 'nullable', 'string', 'max:500'],
            'trailer_url' => ['sometimes', 'nullable', 'string', 'url', 'max:500'],
            'rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10'],
            'genre_ids' => ['sometimes', 'nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
            'cast' => ['sometimes', 'nullable', 'array'],
            'cast.*.name' => ['required_with:cast', 'string', 'max:255'],
            'cast.*.department' => ['required_with:cast', 'string', 'in:acting,directing,production'],
            'translation_en' => ['sometimes', 'nullable', 'array'],
            'translation_en.title' => ['nullable', 'string', 'max:255'],
            'translation_en.sinopsis' => ['nullable', 'string'],
            'translation_en.is_available' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $allowed = ['title', 'sinopsis', 'duration', 'release_year', 'image', 'trailer_url', 'rating', 'genre_ids', 'cast', 'translation_en'];
            $present = array_intersect($allowed, array_keys($this->all()));
            if (count($present) === 0) {
                $validator->errors()->add('body', 'Debes enviar campos para actualizar');
            }
        });
    }
}
