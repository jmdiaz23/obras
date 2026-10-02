<?php

namespace Tests\Feature;

use App\Livewire\Apus\ApuEditor;
use App\Livewire\Apus\Apus;
use App\Models\Apu;
use App\Models\CargoMo;
use App\Models\Equipo;
use App\Models\Empresa;
use App\Models\Material;
use App\Models\User;
use App\Services\ApuCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ApuTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::query()->create([
            'nombre' => 'Constructora Test S.A.S.',
        ]);

        $this->user = User::query()->create([
            'name' => 'Tester',
            'email' => 'tester@obras.com',
            'password' => Hash::make('password123'),
            'empresa_id' => $this->empresa->id,
            'role' => 'propietario',
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Reproduce EXACTAMENTE la hoja "CONCRETO DE 3000 PSI" del Excel del usuario:
     *
     * A. Materiales : Cemento 350 KG x 53.000 | Agua 0,18 M3 x 1.840
     *                 Grueso 0,84 M3 x 82.048 | Fino 0,56 M3 x 64.191   => 290.698,48
     * B. Mano obra  : Ayudante jornal 30.284 (+85% prest) x 0,89 D/M3 => 49.862,61
     * C. Herram.    : 10% de la MO                                    => 4.986,26
     *    Trompo     : (49.990/8 h) x 0,5 H                            => 3.124,38
     * COSTO DIRECTO                                                   => 348.671,72
     */
    public function test_reproduce_el_apu_de_concreto_3000_psi_del_excel(): void
    {
        $cemento = Material::create(['codigo' => 'M1', 'descripcion' => 'Cemento gris 350 kg', 'unidad' => 'KG', 'precio' => 530]);
        $agua = Material::create(['codigo' => 'M2', 'descripcion' => 'Agua', 'unidad' => 'M3', 'precio' => 1840]);
        $grueso = Material::create(['codigo' => 'M3', 'descripcion' => 'Grueso de 3/4', 'unidad' => 'M3', 'precio' => 82048]);
        $fino = Material::create(['codigo' => 'M4', 'descripcion' => 'Fino', 'unidad' => 'M3', 'precio' => 64191]);

        $ayudante = CargoMo::create(['nombre' => 'Ayudante de obra', 'jornal' => 30284]);
        $trompo = Equipo::create(['codigo' => 'E6', 'descripcion' => 'Trompo de concreto', 'tarifa_dia' => 49990]);

        $apu = Apu::create(['descripcion' => 'CONCRETO DE 3000 PSI EN ZAPATAS', 'unidad' => 'M3']);

        foreach ([[$cemento->id, 350], [$agua->id, 0.18], [$grueso->id, 0.84], [$fino->id, 0.56]] as $i => [$materialId, $cantidad]) {
            $apu->materiales()->create(['material_id' => $materialId, 'cantidad' => $cantidad, 'orden' => $i + 1]);
        }
        $apu->manoObra()->create(['cargo_id' => $ayudante->id, 'dias_por_unidad' => 0.89, 'orden' => 1]);
        $apu->equipos()->create(['equipo_id' => $trompo->id, 'horas_por_unidad' => 0.5, 'orden' => 1]);

        $totales = ApuCalculator::calcular($apu->fresh(['materiales.material', 'manoObra.cargo', 'equipos.equipo', 'empresa']))['totales'];

        // Precios de referencia tomados de la hoja original
        $this->assertEqualsWithDelta(290698.48, $totales['materiales'], 0.01);
        $this->assertEqualsWithDelta(49862.61, $totales['mano_obra'], 0.01);
        $this->assertEqualsWithDelta(4986.26, $totales['herramientas'], 0.01);
        $this->assertEqualsWithDelta(8110.64, $totales['equipos'], 0.02);

        // El costo directo de la hoja es 348.671,72 (diferencia máxima: redondeo a centavos)
        $this->assertEqualsWithDelta(348671.72, $totales['costo_directo'], 0.02);
    }

    public function test_crea_apu_desde_la_lista_y_redirige_al_editor(): void
    {
        Livewire::test(Apus::class)
            ->call('abrirModal')
            ->assertSet('form.codigo', 'APU-001')
            ->set('form.descripcion', 'VIGA DE CONCRETO DE 3000 PSI')
            ->call('crear')
            ->assertHasNoErrors()
            ->assertRedirect(route('apus.editar', Apu::first()));

        $this->assertDatabaseHas('apus', [
            'empresa_id' => $this->empresa->id,
            'descripcion' => 'VIGA DE CONCRETO DE 3000 PSI',
            'unidad' => 'M3',
        ]);
    }

    public function test_editor_agrega_actualiza_y_quita_lineas(): void
    {
        $material = Material::create(['codigo' => 'M1', 'descripcion' => 'Cemento gris 350 kg', 'unidad' => 'KG', 'precio' => 53000]);
        $cargo = CargoMo::create(['nombre' => 'Oficial', 'jornal' => 45000]);
        $equipo = Equipo::create(['codigo' => 'E1', 'descripcion' => 'Andamio', 'tarifa_dia' => 20000]);

        $apu = Apu::create(['descripcion' => 'PRUEBA DE LINEAS', 'unidad' => 'M3']);

        $componente = Livewire::test(ApuEditor::class, ['apu' => $apu])
            ->set('selMaterial', $material->id)->call('agregarMaterial')
            ->set('selCargo', $cargo->id)->call('agregarCargo')
            ->set('selEquipo', $equipo->id)->call('agregarEquipo');

        $this->assertDatabaseCount('apu_material', 1);
        $this->assertDatabaseCount('apu_manodeobra', 1);
        $this->assertDatabaseCount('apu_equipos', 1);

        // Editar cantidad en vivo persiste en BD
        $lineaMaterialId = $apu->materiales()->first()->id;

        $componente
            ->set("linM.$lineaMaterialId.cantidad", '400')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('apu_material', ['id' => $lineaMaterialId, 'cantidad' => 400.0]);

        // Quitar las tres líneas
        $componente
            ->call('quitarLinea', 'material', $lineaMaterialId)
            ->call('quitarLinea', 'mano_obra', $apu->manoObra()->first()->id)
            ->call('quitarLinea', 'equipo', $apu->equipos()->first()->id);

        $this->assertDatabaseCount('apu_material', 0);
        $this->assertDatabaseCount('apu_manodeobra', 0);
        $this->assertDatabaseCount('apu_equipos', 0);
    }

    public function test_guarda_cabecera_con_codigo_unico_por_empresa(): void
    {
        $apu = Apu::create(['descripcion' => 'APU ORIGINAL', 'unidad' => 'M2']);

        Livewire::test(ApuEditor::class, ['apu' => $apu])
            ->set('cab.descripcion', 'REPLANTEO Y TRAZADO')
            ->set('cab.pct_herramientas', '15')
            ->call('guardarCabecera')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('apus', [
            'id' => $apu->id,
            'descripcion' => 'REPLANTEO Y TRAZADO',
            'pct_herramientas' => 15,
        ]);
    }

    public function test_no_permite_acceder_al_apu_de_otra_empresa(): void
    {
        $otraEmpresa = Empresa::query()->create(['nombre' => 'Otra Constructora']);
        $otroUser = User::query()->create([
            'name' => 'Ajeno',
            'email' => 'ajeno@otras.com',
            'password' => Hash::make('password123'),
            'empresa_id' => $otraEmpresa->id,
            'role' => 'ingeniero',
        ]);

        $apu = Apu::create(['descripcion' => 'MI APU PRIVADO', 'unidad' => 'M3']);

        $this->actingAs($otroUser)
            ->get(route('apus.editar', $apu))
            ->assertNotFound();
    }

    public function test_lista_de_apus_carga_correctamente(): void
    {
        Apu::create(['descripcion' => 'CONCRETO DE 3000 PSI', 'unidad' => 'M3']);

        Livewire::test(Apus::class)->assertOk();
    }
}
