<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sala extends Model
{
    protected $table = 'salas';

    protected $fillable = [
        'nombre',
        'filas',
        'columnas',
    ];

    public $timestamps = false;

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'sala_id');
    }

    public function asientos(): HasMany
    {
        return $this->hasMany(Asiento::class, 'sala_id');
    }
}
