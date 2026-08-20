<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenreTranslation extends Model
{
    protected $table = 'genre_translations';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'genre_id',
        'language_id',
    ];

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
