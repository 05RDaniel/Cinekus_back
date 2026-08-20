<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Genre extends Model
{
    protected $table = 'genres';

    public $timestamps = false;

    protected $fillable = [
        'parent_id',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(GenreTranslation::class, 'genre_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Pelicula::class, 'movie_genre', 'genre_id', 'movie_id');
    }
}
