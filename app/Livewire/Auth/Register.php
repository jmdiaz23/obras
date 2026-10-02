<?php

namespace App\Livewire\Auth;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Crear cuenta')]
class Register extends Component
{
    public string $nombre_empresa = '';

    public string $nit = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'nombre_empresa' => ['required', 'string', 'min:3', 'max:120'],
            'nit' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre_empresa.required' => 'El nombre de la empresa es obligatorio.',
            'nombre_empresa.min' => 'El nombre de la empresa debe tener al menos 3 caracteres.',
            'name.required' => 'Tu nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function register(): void
    {
        $validated = $this->validate();

        [$empresa, $user] = DB::transaction(function () use ($validated) {
            $empresa = Empresa::create([
                'nombre' => $validated['nombre_empresa'],
                'nit' => $validated['nit'] ?: null,
            ]);

            $user = $empresa->users()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => User::ROLE_PROPIETARIO,
            ]);

            return [$empresa, $user];
        });

        Auth::login($user);

        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
