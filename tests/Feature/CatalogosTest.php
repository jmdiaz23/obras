<?php

namespace Tests\Feature;

use App\Livewire\Catalogos\CargosMo;
use App\Livewire\Catalogos\Equipos;
use App\Livewire\Catalogos\Materiales;
use App\Livewire\Catalogos\Proveedores;
use App\Models\CargoMo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogosTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create(['nombre' => 'Empresa Uno']);
        $this->user = $this->empresa->users()->create([
            'name' => 'Usuario Uno',
            'email' => 'uno@test.com',
            'password' => 'password123',
            'role' => User::ROLE_PROPIETARIO,
        ]);

        $this->actingAs($this->user);
    }

    private function crearEmpresaAjena(): Empresa
    {
        $ajena = Empresa::create(['nombre' => 'Empresa Ajena']);
        $ajena->users()->create([
            'name' => 'Usuario Ajeno',
            'email' => 'ajeno@test.com',
            'password' => 'password123',
            'role' => User::ROLE_PROPIETARIO,
        ]);

        return $ajena;
    }

    public function test_crea_material_con_empresa_del_usuario_automaticamente(): void
    {
        Livewire::test(Materiales::class)
            ->set('form.codigo', 'M99')
            ->set('form.descripcion', 'Cemento especial')
            ->set('form.unidad', 'KG')
            ->set('form.precio', '530')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('materiales', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'M99',
            'precio' => 530,
        ]);
    }

    public function test_rechaza_codigo_de_material_duplicado_en_la_misma_empresa(): void
    {
        Material::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'M1',
            'descripcion' => 'Cemento',
            'unidad' => 'KG',
            'precio' => 530,
        ]);

        Livewire::test(Materiales::class)
            ->set('form.codigo', 'M1')
            ->set('form.descripcion', 'Otro cemento')
            ->set('form.unidad', 'KG')
            ->set('form.precio', '100')
            ->call('guardar')
            ->assertHasErrors(['form.codigo']);

        $this->assertSame(1, Material::count());
    }

    public function test_permite_el_mismo_codigo_de_material_en_empresas_distintas(): void
    {
        $ajena = $this->crearEmpresaAjena();
        Material::create([
            'empresa_id' => $ajena->id,
            'codigo' => 'M1',
            'descripcion' => 'Material de otra empresa',
            'unidad' => 'KG',
            'precio' => 1,
        ]);

        Livewire::test(Materiales::class)
            ->set('form.codigo', 'M1')
            ->set('form.descripcion', 'Mi material')
            ->set('form.unidad', 'KG')
            ->set('form.precio', '500')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('materiales', 2);
        $this->assertDatabaseHas('materiales', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'M1',
            'descripcion' => 'Mi material',
        ]);
    }

    public function test_edita_y_elimina_material_propio(): void
    {
        $material = Material::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'M1',
            'descripcion' => 'Cemento',
            'unidad' => 'KG',
            'precio' => 530,
        ]);

        Livewire::test(Materiales::class)
            ->call('editar', $material->id)
            ->set('form.precio', '600')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(600.0, $material->fresh()->precio);

        Livewire::test(Materiales::class)
            ->call('pedirConfirmarEliminar', $material->id)
            ->call('eliminar');

        $this->assertDatabaseMissing('materiales', ['id' => $material->id]);
    }

    public function test_aislamiento_no_puede_editar_ni_ver_materiales_ajenos(): void
    {
        $ajena = $this->crearEmpresaAjena();
        $materialAjeno = Material::create([
            'empresa_id' => $ajena->id,
            'codigo' => 'X9',
            'descripcion' => 'Secreto ajeno',
            'unidad' => 'UND',
            'precio' => 999999,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Materiales::class)->call('editar', $materialAjeno->id);
    }

    public function test_ciclo_completo_de_proveedor(): void
    {
        Livewire::test(Proveedores::class)
            ->set('form.nombre', 'Homecenter')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('proveedores', [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Homecenter',
        ]);

        $proveedor = Proveedor::first();

        Livewire::test(Proveedores::class)
            ->call('editar', $proveedor->id)
            ->set('form.contacto', 'Juan Vendedor')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('Juan Vendedor', $proveedor->fresh()->contacto);
    }

    public function test_crea_equipo_con_tarifa_diaria(): void
    {
        Livewire::test(Equipos::class)
            ->set('form.codigo', 'E1')
            ->set('form.descripcion', 'Vibrocompactador manual')
            ->set('form.tipo', 'Rana')
            ->set('form.tarifa_dia', '39900')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('equipos', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'E1',
            'tarifa_dia' => 39900,
        ]);
    }

    public function test_crea_cargo_de_mano_de_obra(): void
    {
        Livewire::test(CargosMo::class)
            ->set('form.nombre', 'Ayudante')
            ->set('form.jornal', '30284')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cargos_mo', [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Ayudante',
            'jornal' => 30284,
        ]);
    }

    public function test_las_paginas_de_catalogos_cargan_para_usuarios_autenticados(): void
    {
        $this->get(route('catalogos.proveedores'))->assertOk();
        $this->get(route('catalogos.materiales'))->assertOk();
        $this->get(route('catalogos.equipos'))->assertOk();
        $this->get(route('catalogos.mano-de-obra'))->assertOk();
    }
}
