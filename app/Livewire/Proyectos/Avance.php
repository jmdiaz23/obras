<?php

namespace App\Livewire\Proyectos;

use App\Models\Proyecto;
use App\Services\AvanceService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Avance de obra')]
class Avance extends Component
{
    public Proyecto $proyecto;

    public function mount(Proyecto $proyecto): void
    {
        $this->proyecto = $proyecto;
    }

    public function render()
    {
        $proyecto = $this->proyecto->fresh([
            'items.apu.materiales.material',
            'items.apu.manoObra.cargo',
            'items.apu.equipos.equipo',
            'items.apu.empresa',
            'etapas',
        ]);

        return view('livewire.proyectos.avance', [
            'proyecto' => $proyecto,
            'avance' => AvanceService::calcular($proyecto),
        ]);
    }
}
