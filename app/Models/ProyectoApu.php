<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyectoApu extends Model
{
    protected $table = 'proyecto_apu';

    public $timestamps = false;

    protected $fillable = [
        'proyecto_id',
        'apu_id',
        'etapa_id',
        'item',
        'titulo',
        'es_capitulo',
        'cantidad',
        'cantidad_ejecutada',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'es_capitulo' => 'boolean',
            'cantidad' => 'float',
            'cantidad_ejecutada' => 'float',
            'orden' => 'integer',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function apu(): BelongsTo
    {
        return $this->belongsTo(Apu::class);
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(ProyectoEtapa::class, 'etapa_id');
    }
}
