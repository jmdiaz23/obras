<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApuEquipo extends Model
{
    protected $table = 'apu_equipos';

    public $timestamps = false;

    protected $fillable = [
        'apu_id',
        'equipo_id',
        'horas_por_unidad',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'horas_por_unidad' => 'float',
            'orden' => 'integer',
        ];
    }

    public function apu(): BelongsTo
    {
        return $this->belongsTo(Apu::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}
