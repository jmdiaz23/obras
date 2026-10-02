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
        'item',
        'es_capitulo',
        'cantidad',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'es_capitulo' => 'boolean',
            'cantidad' => 'float',
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
}
