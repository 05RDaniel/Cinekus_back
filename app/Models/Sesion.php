<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sesion extends Model
{
    protected $table = 'sessions';

    protected $fillable = [
        'movie_id',
        'room_id',
        'language_id',
        'start_date',
        'start_time',
        'session_type',
        'subtitles',
    ];

    public const PRIMARY_LANGUAGE_CODE = 'es';

    public const SESSION_TYPES = ['2d', '3d', '4d'];

    public const SUBTITLE_OPTIONS = ['none', 'es', 'en'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
        ];
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        return $query->where(function (Builder $upcoming) use ($today, $nowTime) {
            $upcoming
                ->whereDate('start_date', '>', $today)
                ->orWhere(function (Builder $sameDay) use ($today, $nowTime) {
                    $sameDay
                        ->whereDate('start_date', $today)
                        ->whereTime('start_time', '>=', $nowTime);
                });
        });
    }

    public function pelicula(): BelongsTo
    {
        return $this->belongsTo(Pelicula::class, 'movie_id');
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'room_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'session_id');
    }
}
