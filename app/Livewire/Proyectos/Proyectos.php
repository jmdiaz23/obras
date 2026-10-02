<?php

namespace App\Livewire\Proyectos;

use App\Models\Proyecto;
use App\Services\PresupuestoService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Proyectos')]
class Proyectos extends Component
{
    use WithPagination;

    public string $buscar = '';

    public bool $mostrarModal = false;

    public ?int $confirmarEliminarId = null;

    public array $form = [
        'nombre' => '',
        'cliente' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.nombre' => ['required', 'string', 'min:3', 'max:255'],
            'form.cliente' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.nombre.required' => 'El nombre del proyecto es obligatorio.',
            'form.nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function abrirModal(): void
    {
        $this->resetValidation();
        $this->form = ['nombre' => '', 'cliente' => ''];
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

        $proyecto = Proyecto::create([
            ...$datos,
            'cliente' => $datos['cliente'] ?: null,
        ]);

        $this->mostrarModal = false;

        $this->redirect(route('proyectos.editar', $proyecto), navigate: true);
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
        Proyecto::query()->findOrFail($this->confirmarEliminarId)->delete();

        $this->confirmarEliminarId = null;

        session()->flash('exito', 'Proyecto eliminado.');
    }

    public function render()
    {
        return view('livewire.proyectos.index', [
            'estados' => Proyecto::ESTADOS,
            'proyectos' => Proyecto::query()
                ->withCount('items')
                ->when(
                    trim($this->buscar) !== '',
                    fn ($q) => $q->where(fn ($w) => $w
                        ->where('nombre', 'like', '%'.$this->buscar.'%')
                        ->orWhere('cliente', 'like', '%'.$this->buscar.'%'))
                )
                ->latest()
                ->paginate(8)
                ->through(fn (Proyecto $proyecto) => [
                    'modelo' => $proyecto,
                    'total' => PresupuestoService::calcular($proyecto)['resumen']['total'],
                ]),
        ]);
    }
}
