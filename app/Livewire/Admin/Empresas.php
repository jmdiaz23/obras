<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Empresas')]
class Empresas extends Component
{
    use WithPagination;

    public string $buscar = '';

    public bool $mostrarModal = false;

    public ?int $editandoId = null;

    public array $form = [];

    public function mount(): void
    {
        $this->form = $this->formularioVacio();
    }

    protected function rules(): array
    {
        $creando = $this->editandoId === null;

        return [
            'form.nombre' => ['required', 'string', 'min:3', 'max:120'],
            'form.nit' => ['nullable', 'string', 'max:20'],
            'form.telefono' => ['nullable', 'string', 'max:30'],
            'form.direccion' => ['nullable', 'string', 'max:255'],
            'form.pct_administracion' => ['required', 'numeric', 'between:0,100'],
            'form.pct_imprevistos' => ['required', 'numeric', 'between:0,100'],
            'form.pct_utilidad' => ['required', 'numeric', 'between:0,100'],
            'form.pct_prestaciones' => ['required', 'numeric', 'between:0,100'],
            'form.owner_name' => $creando ? ['required', 'string', 'min:3', 'max:120'] : ['nullable'],
            'form.owner_email' => $creando
                ? ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')]
                : ['nullable'],
            'form.owner_password' => $creando ? ['required', 'string', 'min:8', 'confirmed'] : ['nullable'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.nombre.required' => 'El nombre de la empresa es obligatorio.',
            'form.nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'form.owner_name.required' => 'El nombre del propietario es obligatorio.',
            'form.owner_email.required' => 'El correo del propietario es obligatorio.',
            'form.owner_email.email' => 'Ingresa un correo válido.',
            'form.owner_email.unique' => 'Este correo ya está registrado.',
            'form.owner_password.required' => 'La contraseña inicial es obligatoria.',
            'form.owner_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'form.owner_password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function abrirModal(): void
    {
        $this->resetValidation();
        $this->editandoId = null;
        $this->form = $this->formularioVacio();
        $this->mostrarModal = true;
    }

    public function editar(int $id): void
    {
        $empresa = Empresa::query()->findOrFail($id);

        $this->resetValidation();
        $this->editandoId = $empresa->id;
        $this->form = [
            'nombre' => $empresa->nombre,
            'nit' => $empresa->nit ?? '',
            'telefono' => $empresa->telefono ?? '',
            'direccion' => $empresa->direccion ?? '',
            'pct_administracion' => $empresa->pct_administracion,
            'pct_imprevistos' => $empresa->pct_imprevistos,
            'pct_utilidad' => $empresa->pct_utilidad,
            'pct_prestaciones' => $empresa->pct_prestaciones,
            'owner_name' => '',
            'owner_email' => '',
            'owner_password' => '',
            'owner_password_confirmation' => '',
        ];
        $this->mostrarModal = true;
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->editandoId = null;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $datos = $this->validate()['form'];
        $editando = $this->editandoId !== null;

        DB::transaction(function () use ($datos, $editando) {
            $datosEmpresa = [
                'nombre' => $datos['nombre'],
                'nit' => $datos['nit'] ?: null,
                'telefono' => $datos['telefono'] ?: null,
                'direccion' => $datos['direccion'] ?: null,
                'pct_administracion' => $datos['pct_administracion'],
                'pct_imprevistos' => $datos['pct_imprevistos'],
                'pct_utilidad' => $datos['pct_utilidad'],
                'pct_prestaciones' => $datos['pct_prestaciones'],
            ];

            if ($editando) {
                Empresa::query()->findOrFail($this->editandoId)->update($datosEmpresa);

                return;
            }

            $empresa = Empresa::query()->create($datosEmpresa);

            $empresa->users()->create([
                'name' => $datos['owner_name'],
                'email' => $datos['owner_email'],
                'password' => $datos['owner_password'],
                'role' => User::ROLE_PROPIETARIO,
                'es_super_admin' => false,
            ]);
        });

        $this->mostrarModal = false;
        $this->editandoId = null;
        $this->form = $this->formularioVacio();

        session()->flash(
            'exito',
            $editando
                ? 'Empresa actualizada correctamente.'
                : 'Empresa creada. El propietario ya puede iniciar sesión.'
        );
    }

    public function render()
    {
        return view('livewire.admin.empresas', [
            'empresas' => Empresa::query()
                ->withCount(['users', 'proyectos'])
                ->when(trim($this->buscar) !== '', fn ($q) => $q->where(
                    fn ($w) => $w
                        ->where('nombre', 'like', '%'.$this->buscar.'%')
                        ->orWhere('nit', 'like', '%'.$this->buscar.'%')
                ))
                ->latest()
                ->paginate(10),
        ]);
    }

    private function formularioVacio(): array
    {
        return [
            'nombre' => '',
            'nit' => '',
            'telefono' => '',
            'direccion' => '',
            'pct_administracion' => 10,
            'pct_imprevistos' => 5,
            'pct_utilidad' => 10,
            'pct_prestaciones' => 85,
            'owner_name' => '',
            'owner_email' => '',
            'owner_password' => '',
            'owner_password_confirmation' => '',
        ];
    }
}
