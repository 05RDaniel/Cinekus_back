<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelicula extends Model
{
    protected $table = 'movies';

    protected $fillable = [
        'title',
        'sinopsis',
        'duration',
        'release_year',
        'image',
        'trailer_url',
        'rating',
    ];

    protected $casts = [
        'rating' => 'float',
        'release_year' => 'integer',
    ];

    public $timestamps = false;

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class, 'movie_id');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'movie_genre', 'movie_id', 'genre_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MovieTranslation::class, 'movie_id');
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'movie_person', 'movie_id', 'person_id');
    }
}
