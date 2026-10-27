<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProyectoEtapa extends Model
{
    protected $table = 'proyecto_etapas';

    protected $fillable = [
        'proyecto_id',
        'nombre',
        'orden',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProyectoApu::class, 'etapa_id');
    }

    public function capitulos(): HasMany
    {
        return $this->hasMany(ProyectoApu::class, 'etapa_id')->where('es_capitulo', true);
    }
}
