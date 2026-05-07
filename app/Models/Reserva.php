<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'usuario_id',
        'sesion_id',
        'fecha_reserva',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'fecha_reserva' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(Sesion::class, 'sesion_id');
    }

    public function asientos(): BelongsToMany
    {
        return $this->belongsToMany(Asiento::class, 'reserva_asientos', 'reserva_id', 'asiento_id');
    }
}
