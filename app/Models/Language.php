<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    protected $table = 'languages';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
    ];

    public function genreTranslations(): HasMany
    {
        return $this->hasMany(GenreTranslation::class, 'language_id');
    }

    public function movieTranslations(): HasMany
    {
        return $this->hasMany(MovieTranslation::class, 'language_id');
    }
}
