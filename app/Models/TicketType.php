<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    public const ADULT = 'adult';
    public const CHILD = 'child';
    public const SENIOR = 'senior';

    public const CODES = [
        self::ADULT,
        self::CHILD,
        self::SENIOR,
    ];

    protected $table = 'ticket_types';

    protected $fillable = [
        'code',
        'name',
        'price',
        'price_mode',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'price' => 'float',
        ];
    }
}
