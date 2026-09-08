<?php

namespace App\Http\Requests\Rooms;

use App\Models\SeatType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRoomRequest extends FormRequest
{
    public const MAX_ROWS = 35;
    public const MAX_COLS = 50;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookable = SeatType::BOOKABLE;

        return [
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'rows' => ['required', 'integer', 'min:1', 'max:'.self::MAX_ROWS],
            'columns' => ['required', 'integer', 'min:1', 'max:'.self::MAX_COLS],
            'seats' => ['required', 'array', 'min:1'],
            'seats.*.row' => ['required', 'integer', 'min:1'],
            'seats.*.number' => ['required', 'integer', 'min:1'],
            'seats.*.type' => ['required', 'string', Rule::in($bookable)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $rows = (int) $this->input('rows');
            $columns = (int) $this->input('columns');
            $seats = $this->input('seats');
            if (!is_array($seats) || $rows < 1 || $columns < 1) {
                return;
            }

            $seen = [];
            foreach ($seats as $index => $seat) {
                $row = (int) ($seat['row'] ?? 0);
                $number = (int) ($seat['number'] ?? 0);
                if ($row > $rows || $number > $columns) {
                    $validator->errors()->add("seats.$index.row", 'El asiento queda fuera de la cuadrícula');
                }
                $key = "{$row}-{$number}";
                if (isset($seen[$key])) {
                    $validator->errors()->add("seats.$index.number", 'Hay asientos duplicados en la cuadrícula');
                }
                $seen[$key] = true;
            }
        });
    }
}
