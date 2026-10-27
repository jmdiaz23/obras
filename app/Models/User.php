<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_PROPIETARIO = 'propietario';

    public const ROLE_INGENIERO = 'ingeniero';

    public const ROLE_AUXILIAR = 'auxiliar';

    public const ROLES_EMPRESA = [
        self::ROLE_PROPIETARIO => 'Propietario',
        self::ROLE_INGENIERO => 'Ingeniero',
        self::ROLE_AUXILIAR => 'Auxiliar',
    ];

    protected $fillable = [
        'empresa_id',
        'name',
        'email',
        'password',
        'role',
        'es_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'es_super_admin' => 'bool',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function esSuperAdmin(): bool
    {
        return $this->es_super_admin === true;
    }

    public function esPropietario(): bool
    {
        return $this->role === self::ROLE_PROPIETARIO;
    }
}
