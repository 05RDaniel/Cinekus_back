<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'user_id');
    }

    public function getRolAttribute(): string
    {
        if (array_key_exists('rol', $this->attributes)) {
            return (string) $this->attributes['rol'];
        }

        $role = DB::table('users_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.rol_id')
            ->where('ur.user_id', $this->id)
            ->orderBy('r.id')
            ->value('r.name');

        return $role ?? 'USER';
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'rol' => $this->rol,
            'email' => $this->email,
        ];
    }
}
