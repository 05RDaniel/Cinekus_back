<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sala extends Model
{
    protected $table = 'rooms';

    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'room_id');
    }

    public function asientos(): HasMany
    {
        return $this->hasMany(Asiento::class, 'room_id');
    }
}
