<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sesion extends Model
{
    protected $table = 'sesiones';

    protected $fillable = [
        'pelicula_id',
        'sala_id',
        'fecha',
        'hora',
    ];

    public $timestamps = false;

    public function pelicula(): BelongsTo
    {
        return $this->belongsTo(Pelicula::class, 'pelicula_id');
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'sala_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'sesion_id');
    }
}
