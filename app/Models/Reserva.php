<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reserva extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'user_id',
        'session_id',
        'created_at',
        'status_id',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(Sesion::class, 'session_id');
    }

    public function asientos(): BelongsToMany
    {
        return $this->belongsToMany(Asiento::class, 'booking_seat', 'booking_id', 'seat_id');
    }
}
