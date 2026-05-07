<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaAsiento extends Model
{
    protected $table = 'reserva_asientos';

    protected $fillable = [
        'reserva_id',
        'asiento_id',
    ];

    public $timestamps = false;

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function asiento(): BelongsTo
    {
        return $this->belongsTo(Asiento::class, 'asiento_id');
    }
}
