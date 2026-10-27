<?php

namespace Tests\Feature;

use App\Livewire\Admin\Empresas;
use App\Livewire\Admin\Usuarios;
use App\Models\Empresa;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::query()->create([
            'empresa_id' => null,
            'name' => 'Super Administrador',
            'email' => 'super@test.com',
            'password' => 'password123',
            'role' => User::ROLE_SUPER_ADMIN,
            'es_super_admin' => true,
        ]);
    }

    private function crearEmpresa(string $nombre = 'Constructora Norte'): Empresa
    {
        $empresa = Empresa::query()->create(['nombre' => $nombre]);

        $empresa->users()->create([
            'name' => 'Propietario '.$nombre,
            'email' => strtolower(str_replace(' ', '', $nombre)).'@test.com',
            'password' => 'password123',
            'role' => User::ROLE_PROPIETARIO,
        ]);

        return $empresa;
    }

    public function test_un_usuario_de_empresa_no_puede_entrar_al_panel_de_plataforma(): void
    {
        $empresa = $this->crearEmpresa();
        $user = $empresa->users()->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_el_super_admin_ve_las_empresas_de_toda_la_plataforma(): void
    {
        $norte = $this->crearEmpresa('Constructora Norte');
        $sur = $this->crearEmpresa('Constructora Sur');

        Proyecto::withoutGlobalScopes()->create(['empresa_id' => $norte->id, 'nombre' => 'Proyecto Norte']);
        Proyecto::withoutGlobalScopes()->create(['empresa_id' => $sur->id, 'nombre' => 'Proyecto Sur']);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Panel de plataforma')
            ->assertSee('Constructora Norte')
            ->assertSee('Constructora Sur')
            ->assertSee('Proyecto Norte')
            ->assertSee('Proyecto Sur');
    }

    public function test_el_super_admin_es_redirigido_del_dashboard_de_empresa_al_panel(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_el_super_admin_crea_una_empresa_con_su_propietario(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(Empresas::class)
            ->set('form.nombre', 'Constructora Nueva')
            ->set('form.nit', '901000111-2')
            ->set('form.telefono', '300 000 0000')
            ->set('form.direccion', 'Bogotá')
            ->set('form.pct_administracion', 12)
            ->set('form.pct_imprevistos', 4)
            ->set('form.pct_utilidad', 8)
            ->set('form.pct_prestaciones', 85)
            ->set('form.owner_name', 'Ana Gómez')
            ->set('form.owner_email', 'ana@nueva.com')
            ->set('form.owner_password', 'password123')
            ->set('form.owner_password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasNoErrors();

        $empresa = Empresa::query()->where('nombre', 'Constructora Nueva')->firstOrFail();
        $propietario = User::query()->where('email', 'ana@nueva.com')->firstOrFail();

        $this->assertSame($empresa->id, $propietario->empresa_id);
        $this->assertSame(User::ROLE_PROPIETARIO, $propietario->role);
        $this->assertFalse($propietario->esSuperAdmin());
        $this->assertEquals(12, $empresa->pct_administracion);
    }

    public function test_el_super_admin_crea_usuarios_para_una_empresa(): void
    {
        $empresa = $this->crearEmpresa();

        $this->actingAs($this->superAdmin);

        Livewire::test(Usuarios::class)
            ->set('form.empresa_id', $empresa->id)
            ->set('form.name', 'Ingeniero de Obra')
            ->set('form.email', 'ingeniero@norte.com')
            ->set('form.role', User::ROLE_INGENIERO)
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('crear')
            ->assertHasNoErrors();

        $usuario = User::query()->where('email', 'ingeniero@norte.com')->firstOrFail();

        $this->assertSame($empresa->id, $usuario->empresa_id);
        $this->assertSame(User::ROLE_INGENIERO, $usuario->role);
        $this->assertFalse($usuario->esSuperAdmin());
    }
}
