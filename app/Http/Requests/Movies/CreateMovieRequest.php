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
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'sinopsis' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'release_year' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'image' => ['nullable', 'string', 'max:500'],
            'trailer_url' => ['nullable', 'string', 'url', 'max:500'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
            'cast' => ['nullable', 'array'],
            'cast.*.name' => ['required_with:cast', 'string', 'max:255'],
            'cast.*.department' => ['required_with:cast', 'string', 'in:acting,directing,production'],
            'translation_en' => ['nullable', 'array'],
            'translation_en.title' => ['nullable', 'string', 'max:255'],
            'translation_en.sinopsis' => ['nullable', 'string'],
            'translation_en.is_available' => ['nullable', 'boolean'],
        ];
    }
}
