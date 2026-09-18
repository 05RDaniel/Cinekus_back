<?php

namespace App\Http\Requests\Sessions;

use App\Models\Language;
use App\Models\Sesion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateSessionRequest extends FormRequest
{
    use ValidatesRoomAvailability;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['required', 'integer', 'min:1'],
            'room_id' => ['required', 'integer', 'min:1'],
            'language_id' => ['required', 'integer', 'exists:languages,id'],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'session_type' => ['required', 'string', 'in:'.implode(',', Sesion::SESSION_TYPES)],
            'subtitles' => ['nullable', 'string', 'in:'.implode(',', Sesion::SUBTITLE_OPTIONS)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $languageId = $this->input('language_id');
            if (!$languageId) {
                return;
            }

            $language = Language::query()->find($languageId);
            if (!$language) {
                return;
            }

            if ($language->code === Sesion::PRIMARY_LANGUAGE_CODE) {
                return;
            }

            if (!$this->filled('subtitles')) {
                $validator->errors()->add('subtitles', 'Los subtítulos son obligatorios para sesiones que no son en español');
            }
        });

        $validator->after(function (Validator $validator): void {
            $this->validateRoomAvailability($validator);
        });
    }
}
