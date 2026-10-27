<?php

namespace App\Livewire\Admin;

use App\Models\Apu;
use App\Models\Empresa;
use App\Models\Proyecto;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Panel de plataforma')]
class Panel extends Component
{
    public function render()
    {
        return view('livewire.admin.panel', [
            'kpis' => [
                ['etiqueta' => 'Empresas', 'valor' => Empresa::query()->count(), 'detalle' => 'Organizaciones activas'],
                ['etiqueta' => 'Usuarios', 'valor' => User::query()->where('es_super_admin', false)->count(), 'detalle' => 'Cuentas de empresa'],
                ['etiqueta' => 'Proyectos', 'valor' => Proyecto::withoutGlobalScopes()->count(), 'detalle' => 'Presupuestos creados'],
                ['etiqueta' => 'APUs', 'valor' => Apu::withoutGlobalScopes()->count(), 'detalle' => 'Análisis de precios'],
            ],
            'empresas' => Empresa::query()
                ->withCount(['users', 'proyectos'])
                ->latest()
                ->take(6)
                ->get(),
            'proyectos' => Proyecto::withoutGlobalScopes()
                ->with('empresa')
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }
}
