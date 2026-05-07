<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Asiento extends Model
{
    protected $table = 'asientos';

    protected $fillable = [
        'sala_id',
        'fila',
        'numero',
    ];

    public $timestamps = false;

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'sala_id');
    }

    public function reservas(): BelongsToMany
    {
        return $this->belongsToMany(Reserva::class, 'reserva_asientos', 'asiento_id', 'reserva_id');
    }
}
