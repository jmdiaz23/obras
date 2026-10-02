<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipo extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'codigo',
        'descripcion',
        'tipo',
        'tarifa_dia',
    ];

    protected function casts(): array
    {
        return [
            'tarifa_dia' => 'float',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }
}
