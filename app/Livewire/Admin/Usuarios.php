<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Usuarios')]
class Usuarios extends Component
{
    use WithPagination;

    public string $buscar = '';

    public bool $mostrarModal = false;

    public array $form = [];

    public function mount(): void
    {
        $this->form = $this->formularioVacio();
    }

    protected function rules(): array
    {
        return [
            'form.empresa_id' => ['required', 'integer', Rule::exists('empresas', 'id')],
            'form.name' => ['required', 'string', 'min:3', 'max:120'],
            'form.email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'form.role' => ['required', Rule::in(array_keys(User::ROLES_EMPRESA))],
            'form.password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.empresa_id.required' => 'Selecciona una empresa.',
            'form.empresa_id.exists' => 'La empresa seleccionada no existe.',
            'form.name.required' => 'El nombre es obligatorio.',
            'form.name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'form.email.required' => 'El correo es obligatorio.',
            'form.email.email' => 'Ingresa un correo válido.',
            'form.email.unique' => 'Este correo ya está registrado.',
            'form.role.required' => 'Selecciona un rol.',
            'form.role.in' => 'El rol seleccionado no es válido.',
            'form.password.required' => 'La contraseña es obligatoria.',
            'form.password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'form.password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function abrirModal(): void
    {
        $this->resetValidation();
        $this->form = $this->formularioVacio();
        $this->mostrarModal = true;
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->resetValidation();
    }

    public function crear(): void
    {
        $datos = $this->validate()['form'];

        User::query()->create([
            'empresa_id' => $datos['empresa_id'],
            'name' => $datos['name'],
            'email' => $datos['email'],
            'role' => $datos['role'],
            'password' => $datos['password'],
            'es_super_admin' => false,
        ]);

        $this->mostrarModal = false;
        $this->form = $this->formularioVacio();

        session()->flash('exito', 'Usuario creado. Ya puede iniciar sesión.');
    }

    public function render()
    {
        return view('livewire.admin.usuarios', [
            'usuarios' => User::query()
                ->with('empresa')
                ->when(trim($this->buscar) !== '', fn ($q) => $q->where(
                    fn ($w) => $w
                        ->where('name', 'like', '%'.$this->buscar.'%')
                        ->orWhere('email', 'like', '%'.$this->buscar.'%')
                        ->orWhereHas('empresa', fn ($e) => $e->where('nombre', 'like', '%'.$this->buscar.'%'))
                ))
                ->latest()
                ->paginate(12),
            'empresas' => Empresa::query()->orderBy('nombre')->get(),
            'roles' => User::ROLES_EMPRESA,
        ]);
    }

    private function formularioVacio(): array
    {
        return [
            'empresa_id' => '',
            'name' => '',
            'email' => '',
            'role' => User::ROLE_INGENIERO,
            'password' => '',
            'password_confirmation' => '',
        ];
    }
}
