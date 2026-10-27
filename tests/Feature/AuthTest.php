<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
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

    private function crearSuperAdmin(): User
    {
        return User::query()->create([
            'empresa_id' => null,
            'name' => 'Super Administrador',
            'email' => 'super@test.com',
            'password' => 'password123',
            'role' => User::ROLE_SUPER_ADMIN,
            'es_super_admin' => true,
        ]);
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

    public function test_el_super_admin_inicia_sesion_en_el_panel_de_plataforma(): void
    {
        $superAdmin = $this->crearSuperAdmin();

        Livewire::test(Login::class)
            ->set('email', $superAdmin->email)
            ->set('password', 'password123')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(Auth::check());
        $this->assertTrue(auth()->user()->esSuperAdmin());
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
