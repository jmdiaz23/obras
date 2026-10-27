<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $fillable = [
        'nombre',
        'nit',
        'telefono',
        'direccion',
        'logo',
        'pct_administracion',
        'pct_imprevistos',
        'pct_utilidad',
        'pct_prestaciones',
    ];

    protected function casts(): array
    {
        return [
            'pct_administracion' => 'float',
            'pct_imprevistos' => 'float',
            'pct_utilidad' => 'float',
            'pct_prestaciones' => 'float',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }
}
