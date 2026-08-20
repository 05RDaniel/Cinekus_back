<?php

namespace App\Http\Requests\Sessions;

use App\Models\Language;
use App\Models\Sesion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['sometimes', 'integer', 'min:1'],
            'room_id' => ['sometimes', 'integer', 'min:1'],
            'language_id' => ['sometimes', 'integer', 'exists:languages,id'],
            'start_date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'session_type' => ['sometimes', 'string', 'in:'.implode(',', Sesion::SESSION_TYPES)],
            'subtitles' => ['sometimes', 'nullable', 'string', 'in:'.implode(',', Sesion::SUBTITLE_OPTIONS)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $allowed = ['movie_id', 'room_id', 'language_id', 'start_date', 'start_time', 'session_type', 'subtitles'];
            $present = array_intersect($allowed, array_keys($this->all()));
            if (count($present) === 0) {
                $validator->errors()->add('body', 'Debes enviar campos para actualizar');

                return;
            }

            $session = Sesion::query()->find($this->route('id'));
            $languageId = $this->input('language_id', $session?->language_id);
            if (!$languageId) {
                return;
            }

            $language = Language::query()->find($languageId);
            if (!$language || $language->code === Sesion::PRIMARY_LANGUAGE_CODE) {
                return;
            }

            $subtitles = $this->has('subtitles') ? $this->input('subtitles') : $session?->subtitles;
            if ($subtitles === null || $subtitles === '') {
                $validator->errors()->add('subtitles', 'Los subtítulos son obligatorios para sesiones que no son en español');
            }
        });
    }
}
