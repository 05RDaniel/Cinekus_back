<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaAsiento extends Model
{
    protected $table = 'booking_seat';

    protected $fillable = [
        'booking_id',
        'seat_id',
        'unit_price',
    ];

    public $timestamps = false;

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'booking_id');
    }

    public function asiento(): BelongsTo
    {
        return $this->belongsTo(Asiento::class, 'seat_id');
    }
}
