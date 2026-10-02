<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class CargoMo extends Model
{
    use BelongsToEmpresa;

    protected $table = 'cargos_mo';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'jornal',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'jornal' => 'float',
        ];
    }

    public function jornalConPrestaciones(): float
    {
        return $this->jornal * (1 + auth()->user()->empresa->pct_prestaciones / 100);
    }
}
