<?php

namespace App\Livewire\Apus;

use App\Models\Apu;
use App\Models\ApuEquipo;
use App\Models\ApuManodeObra;
use App\Models\ApuMaterial;
use App\Models\CargoMo;
use App\Models\Equipo;
use App\Models\Material;
use App\Services\ApuCalculator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Editor de APU')]
class ApuEditor extends Component
{
    public Apu $apu;

    public array $cab = [];

    /** @var array<int, array{cantidad: string, desperdicio: string}> */
    public array $linM = [];

    /** @var array<int, array{dias: string}> */
    public array $linMO = [];

    /** @var array<int, array{horas: string}> */
    public array $linE = [];

    public ?int $selMaterial = null;

    public ?int $selCargo = null;

    public ?int $selEquipo = null;

    public function mount(Apu $apu): void
    {
        $this->apu = $apu;
        $this->sincronizarCabecera();
        $this->sincronizarLineas();
    }

    private function sincronizarCabecera(): void
    {
        $this->cab = [
            'codigo' => (string) ($this->apu->codigo ?? ''),
            'descripcion' => (string) $this->apu->descripcion,
            'unidad' => (string) $this->apu->unidad,
            'cuadrilla' => (string) ($this->apu->cuadrilla ?? ''),
            'dias_por_unidad' => $this->apu->dias_por_unidad !== null
                ? rtrim(rtrim(number_format($this->apu->dias_por_unidad, 6, '.', ''), '0'), '.')
                : '',
            'pct_herramientas' => rtrim(rtrim(number_format($this->apu->pct_herramientas, 2, '.', ''), '0'), '.'),
            'notas' => (string) ($this->apu->notas ?? ''),
        ];
    }

    private function sincronizarLineas(): void
    {
        $this->apu->load(['materiales', 'manoObra', 'equipos']);

        $this->linM = $this->apu->materiales->mapWithKeys(fn ($l) => [
            $l->id => ['cantidad' => rtrim(rtrim(number_format($l->cantidad, 4, '.', ''), '0'), '.'), 'desperdicio' => rtrim(rtrim(number_format($l->desperdicio_pct, 2, '.', ''), '0'), '.')],
        ])->toArray();

        $this->linMO = $this->apu->manoObra->mapWithKeys(fn ($l) => [
            $l->id => ['dias' => rtrim(rtrim(number_format($l->dias_por_unidad, 4, '.', ''), '0'), '.')],
        ])->toArray();

        $this->linE = $this->apu->equipos->mapWithKeys(fn ($l) => [
            $l->id => ['horas' => rtrim(rtrim(number_format($l->horas_por_unidad, 4, '.', ''), '0'), '.')],
        ])->toArray();
    }

    private function apuFresco(): Apu
    {
        return $this->apu->fresh(['materiales.material', 'manoObra.cargo', 'equipos.equipo', 'empresa']);
    }

    protected function rules(): array
    {
        return [
            'cab.codigo' => [
                'nullable', 'string', 'max:30',
                Rule::unique('apus', 'codigo')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($this->apu->id),
            ],
            'cab.descripcion' => ['required', 'string', 'min:3', 'max:255'],
            'cab.unidad' => ['required', 'string', 'max:10', Rule::in(array_keys(config('obras.unidades')))],
            'cab.cuadrilla' => ['nullable', 'string', 'max:30'],
            'cab.dias_por_unidad' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'cab.pct_herramientas' => ['required', 'numeric', 'min:0', 'max:100'],
            'cab.notas' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'cab.descripcion.required' => 'La actividad es obligatoria.',
            'cab.descripcion.min' => 'La descripción debe tener al menos 3 caracteres.',
            'cab.codigo.unique' => 'Ya existe otro APU con ese código.',
            'cab.pct_herramientas.required' => 'El % de herramientas es obligatorio.',
        ];
    }

    public function guardarCabecera(): void
    {
        $this->validateOnly('cab.descripcion');
        $this->validateOnly('cab.unidad');
        $this->validateOnly('cab.codigo');
        $this->validateOnly('cab.cuadrilla');
        $this->validateOnly('cab.dias_por_unidad');
        $this->validateOnly('cab.pct_herramientas');

        $this->apu->update([
            'codigo' => $this->cab['codigo'] ?: null,
            'descripcion' => $this->cab['descripcion'],
            'unidad' => $this->cab['unidad'],
            'cuadrilla' => $this->cab['cuadrilla'] ?: null,
            'dias_por_unidad' => $this->cab['dias_por_unidad'] !== '' ? (float) $this->cab['dias_por_unidad'] : null,
            'pct_herramientas' => (float) $this->cab['pct_herramientas'],
            'notas' => $this->cab['notas'] ?: null,
        ]);
    }

    public function updatedLinM(string $valor, string $ruta): void
    {
        [$id, $campo] = explode('.', $ruta);

        $linea = ApuMaterial::query()->where('apu_id', $this->apu->id)->findOrFail((int) $id);

        if ($campo === 'cantidad') {
            $linea->update(['cantidad' => max(0, (float) $valor)]);
        } else {
            $linea->update(['desperdicio_pct' => min(100, max(0, (float) $valor))]);
        }
    }

    public function updatedLinMO(string $valor, string $ruta): void
    {
        [$id] = explode('.', $ruta);

        ApuManodeObra::query()->where('apu_id', $this->apu->id)
            ->findOrFail((int) $id)
            ->update(['dias_por_unidad' => max(0, (float) $valor)]);
    }

    public function updatedLinE(string $valor, string $ruta): void
    {
        [$id] = explode('.', $ruta);

        ApuEquipo::query()->where('apu_id', $this->apu->id)
            ->findOrFail((int) $id)
            ->update(['horas_por_unidad' => max(0, (float) $valor)]);
    }

    public function agregarMaterial(): void
    {
        if (! $this->selMaterial) {
            return;
        }

        Material::query()->findOrFail($this->selMaterial); // valida que sea de la empresa

        ApuMaterial::create([
            'apu_id' => $this->apu->id,
            'material_id' => $this->selMaterial,
            'cantidad' => 1,
            'desperdicio_pct' => 0,
            'orden' => ApuMaterial::where('apu_id', $this->apu->id)->max('orden') + 1,
        ]);

        $this->reset('selMaterial');
        $this->sincronizarLineas();
    }

    public function agregarCargo(): void
    {
        if (! $this->selCargo) {
            return;
        }

        CargoMo::query()->findOrFail($this->selCargo);

        ApuManodeObra::create([
            'apu_id' => $this->apu->id,
            'cargo_id' => $this->selCargo,
            'dias_por_unidad' => 1,
            'orden' => ApuManodeObra::where('apu_id', $this->apu->id)->max('orden') + 1,
        ]);

        $this->reset('selCargo');
        $this->sincronizarLineas();
    }

    public function agregarEquipo(): void
    {
        if (! $this->selEquipo) {
            return;
        }

        Equipo::query()->findOrFail($this->selEquipo);

        ApuEquipo::create([
            'apu_id' => $this->apu->id,
            'equipo_id' => $this->selEquipo,
            'horas_por_unidad' => 1,
            'orden' => ApuEquipo::where('apu_id', $this->apu->id)->max('orden') + 1,
        ]);

        $this->reset('selEquipo');
        $this->sincronizarLineas();
    }

    public function quitarLinea(string $tipo, int $id): void
    {
        match ($tipo) {
            'material' => ApuMaterial::query()->where('apu_id', $this->apu->id)->find($id)?->delete(),
            'mano_obra' => ApuManodeObra::query()->where('apu_id', $this->apu->id)->find($id)?->delete(),
            'equipo' => ApuEquipo::query()->where('apu_id', $this->apu->id)->find($id)?->delete(),
        };

        $this->sincronizarLineas();
    }

    public function render()
    {
        $calculo = ApuCalculator::calcular($this->apuFresco());

        return view('livewire.apus.editor', [
            'apu' => $this->apuFresco(),
            'calculo' => $calculo,
            'materialesDisponibles' => Material::orderBy('codigo')->get(['id', 'codigo', 'descripcion', 'unidad']),
            'cargosDisponibles' => CargoMo::orderBy('nombre')->get(['id', 'nombre']),
            'equiposDisponibles' => Equipo::orderBy('codigo')->get(['id', 'codigo', 'descripcion']),
        ]);
    }
}
