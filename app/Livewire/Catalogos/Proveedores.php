<?php

namespace App\Livewire\Catalogos;

use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Proveedores')]
class Proveedores extends Component
{
    use WithPagination;

    public string $buscar = '';

    public bool $mostrarModal = false;

    public bool $editando = false;

    public ?int $registroId = null;

    public ?int $confirmarEliminarId = null;

    public array $form = [
        'nombre' => '',
        'contacto' => '',
        'telefono' => '',
        'email' => '',
        'notas' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.nombre' => [
                'required', 'string', 'min:3', 'max:120',
                Rule::unique('proveedores', 'nombre')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($this->registroId),
            ],
            'form.contacto' => ['nullable', 'string', 'max:120'],
            'form.telefono' => ['nullable', 'string', 'max:30'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.nombre.required' => 'El nombre del proveedor es obligatorio.',
            'form.nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'form.nombre.unique' => 'Ya existe un proveedor con ese nombre.',
            'form.email.email' => 'Ingresa un correo válido.',
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
        $proveedor = Proveedor::query()->withCount(['materiales', 'equipos'])->findOrFail($id);

        $this->registroId = $proveedor->id;
        $this->form = $proveedor->only(['nombre', 'contacto', 'telefono', 'email', 'notas']);
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
                Proveedor::query()->findOrFail($this->registroId)->update($datos);
            } else {
                Proveedor::create($datos);
            }
        });

        $this->mostrarModal = false;
        session()->flash('exito', $this->editando ? 'Proveedor actualizado.' : 'Proveedor creado.');
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

        Proveedor::query()->findOrFail($this->confirmarEliminarId)->delete();

        $this->confirmarEliminarId = null;
        session()->flash('exito', 'Proveedor eliminado.');
    }

    private function resetFormulario(): void
    {
        $this->reset(['registroId']);
        $this->form = [
            'nombre' => '',
            'contacto' => '',
            'telefono' => '',
            'email' => '',
            'notas' => '',
        ];
    }

    public function render()
    {
        return view('livewire.catalogos.proveedores', [
            'proveedores' => Proveedor::query()
                ->withCount(['materiales', 'equipos'])
                ->when(
                    trim($this->buscar) !== '',
                    fn ($q) => $q->where(fn ($w) => $w
                        ->where('nombre', 'like', '%'.$this->buscar.'%')
                        ->orWhere('contacto', 'like', '%'.$this->buscar.'%'))
                )
                ->orderBy('nombre')
                ->paginate(8),
        ]);
    }
}
