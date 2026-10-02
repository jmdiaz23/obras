<?php

namespace App\Livewire\Catalogos;

use App\Models\Material;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Materiales')]
class Materiales extends Component
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
        'unidad' => 'UND',
        'precio' => '',
        'proveedor_id' => null,
    ];

    protected function rules(): array
    {
        return [
            'form.codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('materiales', 'codigo')
                    ->where('empresa_id', auth()->user()->empresa_id)
                    ->ignore($this->registroId),
            ],
            'form.descripcion' => ['required', 'string', 'min:3', 'max:255'],
            'form.unidad' => ['required', 'string', 'max:10', Rule::in(array_keys(config('obras.unidades')))],
            'form.precio' => ['required', 'numeric', 'min:0'],
            'form.proveedor_id' => ['nullable', Rule::exists('proveedores', 'id')->where('empresa_id', auth()->user()->empresa_id)],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.codigo.required' => 'El código es obligatorio.',
            'form.codigo.unique' => 'Ya existe un material con ese código.',
            'form.descripcion.required' => 'La descripción es obligatoria.',
            'form.descripcion.min' => 'La descripción debe tener al menos 3 caracteres.',
            'form.precio.required' => 'El precio es obligatorio.',
            'form.precio.numeric' => 'El precio debe ser un número.',
            'form.precio.min' => 'El precio no puede ser negativo.',
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
        $material = Material::query()->findOrFail($id);

        $this->registroId = $material->id;
        $this->form = [
            ...$material->only(['codigo', 'descripcion', 'unidad', 'proveedor_id']),
            'precio' => rtrim(rtrim(number_format($material->precio, 2, '.', ''), '0'), '.'),
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
                Material::query()->findOrFail($this->registroId)->update($datos);
            } else {
                Material::create($datos);
            }
        });

        $this->mostrarModal = false;
        session()->flash('exito', $this->editando ? 'Material actualizado.' : 'Material creado.');
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

        Material::query()->findOrFail($this->confirmarEliminarId)->delete();

        $this->confirmarEliminarId = null;
        session()->flash('exito', 'Material eliminado.');
    }

    private function resetFormulario(): void
    {
        $this->reset(['registroId']);
        $this->form = [
            'codigo' => '',
            'descripcion' => '',
            'unidad' => 'UND',
            'precio' => '',
            'proveedor_id' => null,
        ];
    }

    public function render()
    {
        return view('livewire.catalogos.materiales', [
            'materiales' => Material::query()
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
