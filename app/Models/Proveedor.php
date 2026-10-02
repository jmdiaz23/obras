<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use BelongsToEmpresa;

    protected $table = 'proveedores';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'contacto',
        'telefono',
        'email',
        'notas',
    ];

    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
