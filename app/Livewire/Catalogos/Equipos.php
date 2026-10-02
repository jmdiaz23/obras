<?php

namespace App\Livewire\Catalogos;

use App\Models\Equipo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Equipos')]
class Equipos extends Component
{
    use WithPagination;

    public string $buscar = '';

    public bool $mostrarModal = false;

    public bool $editando = false;

    public ?int $registroId = null;

    public ?int $confirmarEliminarId = null;

    public array $form = [
        'codigo' => '',
        'descripcion' => '',
        'tipo' => '',
        'tarifa_dia' => '',
        'proveedor_id' => null,
    ];

    protected function rules(): array
    {
        return [
            'form.codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('equipos', 'codigo')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($this->registroId),
            ],
            'form.descripcion' => ['required', 'string', 'min:3', 'max:255'],
            'form.tipo' => ['nullable', 'string', 'max:60'],
            'form.tarifa_dia' => ['required', 'numeric', 'min:0'],
            'form.proveedor_id' => ['nullable', Rule::exists('proveedores', 'id')->where('empresa_id', auth()->user()->empresa_id)],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.codigo.required' => 'El código es obligatorio.',
            'form.codigo.unique' => 'Ya existe un equipo con ese código.',
            'form.descripcion.required' => 'La descripción es obligatoria.',
            'form.descripcion.min' => 'La descripción debe tener al menos 3 caracteres.',
            'form.tarifa_dia.required' => 'La tarifa por día es obligatoria.',
            'form.tarifa_dia.numeric' => 'La tarifa debe ser un número.',
            'form.tarifa_dia.min' => 'La tarifa no puede ser negativa.',
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function crear(): void
    {
        $this->resetFormulario();
        $this->resetValidation();
        $this->editando = false;
        $this->mostrarModal = true;
    }

    public function editar(int $id): void
    {
        $equipo = Equipo::query()->findOrFail($id);

        $this->registroId = $equipo->id;
        $this->form = [
            ...$equipo->only(['codigo', 'descripcion', 'tipo', 'proveedor_id']),
            'tarifa_dia' => rtrim(rtrim(number_format($equipo->tarifa_dia, 2, '.', ''), '0'), '.'),
        ];
        $this->editando = true;
        $this->mostrarModal = true;
        $this->resetValidation();
    }

    #[On('cerrar-modal')]
    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
    }

    public function guardar(): void
    {
        $datos = $this->validate()['form'];

        DB::transaction(function () use ($datos): void {
            if ($this->editando && $this->registroId) {
                Equipo::query()->findOrFail($this->registroId)->update($datos);
            } else {
                Equipo::create($datos);
            }
        });

        $this->mostrarModal = false;
        session()->flash('exito', $this->editando ? 'Equipo actualizado.' : 'Equipo creado.');
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
        if (! $this->confirmarEliminarId) {
            return;
        }

        Equipo::query()->findOrFail($this->confirmarEliminarId)->delete();

        $this->confirmarEliminarId = null;
        session()->flash('exito', 'Equipo eliminado.');
    }

    private function resetFormulario(): void
    {
        $this->reset(['registroId']);
        $this->form = [
            'codigo' => '',
            'descripcion' => '',
            'tipo' => '',
            'tarifa_dia' => '',
            'proveedor_id' => null,
        ];
    }

    public function render()
    {
        return view('livewire.catalogos.equipos', [
            'equipos' => Equipo::query()
                ->with('proveedor:id,nombre')
                ->when(
                    trim($this->buscar) !== '',
                    fn ($q) => $q->where(fn ($w) => $w
                        ->where('descripcion', 'like', '%'.$this->buscar.'%')
                        ->orWhere('codigo', 'like', '%'.$this->buscar.'%'))
                )
                ->orderBy('codigo')
                ->paginate(10),
        ]);
    }
}
