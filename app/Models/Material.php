<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    use BelongsToEmpresa;

    protected $table = 'materiales';

    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'codigo',
        'descripcion',
        'unidad',
        'precio',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'float',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }
}
