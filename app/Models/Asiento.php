<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Asiento extends Model
{
    protected $table = 'seats';

    protected $fillable = [
        'room_id',
        'seat_row',
        'number',
        'seat_type_id',
    ];

    public $timestamps = false;

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'room_id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(SeatType::class, 'seat_type_id');
    }

    public function reservas(): BelongsToMany
    {
        return $this->belongsToMany(Reserva::class, 'booking_seat', 'seat_id', 'booking_id');
    }
}
