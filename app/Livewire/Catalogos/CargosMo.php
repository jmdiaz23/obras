<?php

namespace App\Livewire\Catalogos;

use App\Livewire\Catalogos\Concerns\ImportaExcel;
use App\Models\CargoMo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Mano de obra')]
class CargosMo extends Component
{
    use ImportaExcel;
    use WithPagination;

    public string $buscar = '';

    public bool $mostrarModal = false;

    public bool $editando = false;

    public ?int $registroId = null;

    public ?int $confirmarEliminarId = null;

    public array $form = [
        'nombre' => '',
        'jornal' => '',
        'descripcion' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.nombre' => [
                'required', 'string', 'min:3', 'max:120',
                Rule::unique('cargos_mo', 'nombre')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($this->registroId),
            ],
            'form.jornal' => ['required', 'numeric', 'min:0'],
            'form.descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.nombre.required' => 'El nombre del cargo es obligatorio.',
            'form.nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'form.nombre.unique' => 'Ya existe un cargo con ese nombre.',
            'form.jornal.required' => 'El jornal es obligatorio.',
            'form.jornal.numeric' => 'El jornal debe ser un número.',
            'form.jornal.min' => 'El jornal no puede ser negativo.',
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
        $cargo = CargoMo::query()->findOrFail($id);

        $this->registroId = $cargo->id;
        $this->form = [
            'nombre' => $cargo->nombre,
            'descripcion' => (string) $cargo->descripcion,
            'jornal' => rtrim(rtrim(number_format($cargo->jornal, 2, '.', ''), '0'), '.'),
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
                CargoMo::query()->findOrFail($this->registroId)->update($datos);
            } else {
                CargoMo::create($datos);
            }
        });

        $this->mostrarModal = false;
        session()->flash('exito', $this->editando ? 'Cargo actualizado.' : 'Cargo creado.');
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

        CargoMo::query()->findOrFail($this->confirmarEliminarId)->delete();

        $this->confirmarEliminarId = null;
        session()->flash('exito', 'Cargo eliminado.');
    }

    private function resetFormulario(): void
    {
        $this->reset(['registroId']);
        $this->form = [
            'nombre' => '',
            'jornal' => '',
            'descripcion' => '',
        ];
    }

    protected function moduloImportacion(): string
    {
        return 'cargos_mo';
    }

    public function render()
    {
        $pctPrestaciones = auth()->user()->empresa->pct_prestaciones;

        return view('livewire.catalogos.cargos-mo', [
            'pctPrestaciones' => $pctPrestaciones,
            'jornalConPrestaciones' => is_numeric($this->form['jornal'])
                ? (float) $this->form['jornal'] * (1 + $pctPrestaciones / 100)
                : null,
            'cargos' => CargoMo::query()
                ->when(
                    trim($this->buscar) !== '',
                    fn ($q) => $q->where(fn ($w) => $w
                        ->where('nombre', 'like', '%'.$this->buscar.'%')
                        ->orWhere('descripcion', 'like', '%'.$this->buscar.'%'))
                )
                ->orderBy('nombre')
                ->paginate(10),
        ]);
    }
}
