<?php

namespace App\Livewire\Apus;

use App\Models\Apu;
use App\Services\ApuCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('APUs')]
class Apus extends Component
{
    use WithPagination;

    public string $buscar = '';

    public bool $mostrarModal = false;

    public ?int $confirmarEliminarId = null;

    public array $form = [
        'codigo' => '',
        'descripcion' => '',
        'unidad' => 'M3',
    ];

    protected function rules(): array
    {
        return [
            'form.codigo' => [
                'nullable', 'string', 'max:30',
                Rule::unique('apus', 'codigo')
                    ->where('empresa_id', auth()->user()->empresa_id),
            ],
            'form.descripcion' => ['required', 'string', 'min:3', 'max:255'],
            'form.unidad' => ['required', 'string', 'max:10', Rule::in(array_keys(config('obras.unidades')))],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.descripcion.required' => 'La actividad del APU es obligatoria.',
            'form.descripcion.min' => 'La descripción debe tener al menos 3 caracteres.',
            'form.codigo.unique' => 'Ya existe un APU con ese código.',
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function abrirModal(): void
    {
        $this->resetValidation();
        $siguiente = Apu::count() + 1;
        $this->form = [
            'codigo' => 'APU-'.str_pad((string) $siguiente, 3, '0', STR_PAD_LEFT),
            'descripcion' => '',
            'unidad' => 'M3',
        ];
        $this->mostrarModal = true;
    }

    #[On('cerrar-modal')]
    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
    }

    public function crear(): void
    {
        $datos = $this->validate()['form'];

        $apu = DB::transaction(fn () => Apu::create([
            ...$datos,
            'codigo' => $datos['codigo'] ?: null,
        ]));

        $this->mostrarModal = false;

        $this->redirect(route('apus.editar', $apu), navigate: true);
    }

    public function pedirConfirmarEliminar(int $id): void
    {
        $this->confirmarEliminarId = $id;
    }

    public function cancelarEliminar(): void
    {
        $this->confirmarEliminarId = null;
    }

    public function eliminar(): void
    {
        Apu::query()->findOrFail($this->confirmarEliminarId)->delete();

        $this->confirmarEliminarId = null;

        session()->flash('exito', 'APU eliminado.');
    }

    public function render()
    {
        return view('livewire.apus.index', [
            'apus' => Apu::query()
                ->with(['empresa', 'materiales.material', 'manoObra.cargo', 'equipos.equipo'])
                ->when(
                    trim($this->buscar) !== '',
                    fn ($q) => $q->where(fn ($w) => $w
                        ->where('descripcion', 'like', '%'.$this->buscar.'%')
                        ->orWhere('codigo', 'like', '%'.$this->buscar.'%'))
                )
                ->orderBy('codigo')
                ->paginate(8)
                ->through(function (Apu $apu) {
                    return [
                        'modelo' => $apu,
                        'costo_directo' => ApuCalculator::calcular($apu)['totales']['costo_directo'],
                    ];
                }),
        ]);
    }
}
