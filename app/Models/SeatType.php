<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatType extends Model
{
    protected $table = 'seat_types';

    protected $fillable = [
        'name',
        'label',
        'price',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'price' => 'float',
        ];
    }

    public const STANDARD = 'standard';
    public const VIP = 'vip';
    public const ACCESSIBLE = 'accessible';

    public const BOOKABLE = [
        self::STANDARD,
        self::VIP,
        self::ACCESSIBLE,
    ];

    public function asientos(): HasMany
    {
        return $this->hasMany(Asiento::class, 'seat_type_id');
    }
}
