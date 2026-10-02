<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApuMaterial extends Model
{
    protected $table = 'apu_material';

    public $timestamps = false;

    protected $fillable = [
        'apu_id',
        'material_id',
        'cantidad',
        'desperdicio_pct',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'float',
            'desperdicio_pct' => 'float',
            'orden' => 'integer',
        ];
    }

    public function apu(): BelongsTo
    {
        return $this->belongsTo(Apu::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
