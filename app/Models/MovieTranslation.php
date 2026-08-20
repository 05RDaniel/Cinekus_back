<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieTranslation extends Model
{
    protected $table = 'movie_translations';

    public $timestamps = false;

    protected $fillable = [
        'movie_id',
        'language_id',
        'title',
        'sinopsis',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Pelicula::class, 'movie_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
