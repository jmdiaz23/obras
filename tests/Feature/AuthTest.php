<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioDePrueba(string $empresaNombre = 'Constructora Base'): User
    {
        $empresa = Empresa::create(['nombre' => $empresaNombre]);

        return $empresa->users()->create([
            'name' => 'Ingeniero Base',
            'email' => strtolower(str_replace(' ', '', $empresaNombre)).'@test.com',
            'password' => 'password123',
            'role' => User::ROLE_PROPIETARIO,
        ]);
    }

    public function test_registra_una_empresa_con_su_propietario_e_inicia_la_sesion(): void
    {
        Livewire::test(Register::class)
            ->set('nombre_empresa', 'Constructora Jesu S.A.S.')
            ->set('nit', '900123456-7')
            ->set('name', 'Jesu Ingeniero')
            ->set('email', 'jesu@obras.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertSame(1, Empresa::count());
        $this->assertSame(1, User::count());

        $user = User::first();

        $this->assertSame(User::ROLE_PROPIETARIO, $user->role);
        $this->assertSame('Constructora Jesu S.A.S.', $user->empresa->nombre);
        $this->assertTrue(Auth::check());
    }

    public function test_valida_los_datos_del_registro(): void
    {
        Livewire::test(Register::class)
            ->set('nombre_empresa', '')
            ->set('name', '')
            ->set('email', 'correo-invalido')
            ->set('password', '123')
            ->call('register')
            ->assertHasErrors(['nombre_empresa', 'name', 'email', 'password']);

        $this->assertSame(0, Empresa::count());
    }

    public function test_rechaza_correos_ya_registrados(): void
    {
        $existente = $this->crearUsuarioDePrueba();

        Livewire::test(Register::class)
            ->set('nombre_empresa', 'Otra Empresa')
            ->set('name', 'Otro Usuario')
            ->set('email', $existente->email)
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);

        $this->assertSame(1, Empresa::count());
    }

    public function test_inicia_sesion_con_credenciales_correctas(): void
    {
        $user = $this->crearUsuarioDePrueba();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password123')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertTrue(Auth::check());
    }

    public function test_rechaza_credenciales_incorrectas(): void
    {
        $user = $this->crearUsuarioDePrueba();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'clave-equivocada')
            ->call('authenticate')
            ->assertHasErrors(['email']);

        $this->assertFalse(Auth::check());
    }

    public function test_muestra_el_dashboard_al_usuario_autenticado_con_datos_de_su_empresa(): void
    {
        $user = $this->crearUsuarioDePrueba('Obra Maestra Ltda.');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Obra Maestra Ltda.')
            ->assertSee('propietario');
    }

    public function test_redirige_a_invitados_hacia_el_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
