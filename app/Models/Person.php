<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    public const DEPARTMENT_ACTING = 'acting';

    public const DEPARTMENT_DIRECTING = 'directing';

    public const DEPARTMENT_PRODUCTION = 'production';

    public const DEPARTMENTS = [
        self::DEPARTMENT_ACTING,
        self::DEPARTMENT_DIRECTING,
        self::DEPARTMENT_PRODUCTION,
    ];

    protected $table = 'people';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'department',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Pelicula::class, 'movie_person', 'person_id', 'movie_id');
    }
}
