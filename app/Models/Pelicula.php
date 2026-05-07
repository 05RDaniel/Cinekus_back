<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelicula extends Model
{
    protected $table = 'peliculas';

    protected $fillable = [
        'titulo',
        'sinopsis',
        'duracion',
        'genero',
        'imagen',
        'fecha_estreno',
    ];

    public $timestamps = false;

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'pelicula_id');
    }
}
