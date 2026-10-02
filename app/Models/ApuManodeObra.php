<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApuManodeObra extends Model
{
    protected $table = 'apu_manodeobra';

    public $timestamps = false;

    protected $fillable = [
        'apu_id',
        'cargo_id',
        'dias_por_unidad',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'dias_por_unidad' => 'float',
            'orden' => 'integer',
        ];
    }

    public function apu(): BelongsTo
    {
        return $this->belongsTo(Apu::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(CargoMo::class, 'cargo_id');
    }
}
