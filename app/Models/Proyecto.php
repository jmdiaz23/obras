<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    use BelongsToEmpresa;

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'en_ejecucion' => 'En ejecución',
        'cerrado' => 'Cerrado',
    ];

    protected $fillable = [
        'empresa_id',
        'nombre',
        'cliente',
        'ubicacion',
        'estado',
        'pct_administracion',
        'pct_imprevistos',
        'pct_utilidad',
    ];

    protected $attributes = [
        'estado' => 'borrador',
        'pct_administracion' => 10,
        'pct_imprevistos' => 5,
        'pct_utilidad' => 10,
    ];

    protected function casts(): array
    {
        return [
            'pct_administracion' => 'float',
            'pct_imprevistos' => 'float',
            'pct_utilidad' => 'float',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProyectoApu::class)->orderBy('orden');
    }
}
