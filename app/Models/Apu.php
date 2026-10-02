<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apu extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'codigo',
        'descripcion',
        'unidad',
        'dias_por_unidad',
        'cuadrilla',
        'pct_herramientas',
        'notas',
    ];

    protected $attributes = [
        'pct_herramientas' => 10,
    ];

    protected function casts(): array
    {
        return [
            'pct_herramientas' => 'float',
            'dias_por_unidad' => 'float',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(ApuMaterial::class)->orderBy('orden');
    }

    public function manoObra(): HasMany
    {
        return $this->hasMany(ApuManodeObra::class)->orderBy('orden');
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(ApuEquipo::class)->orderBy('orden');
    }
}
