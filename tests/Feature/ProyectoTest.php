<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\ProyectoEditor;
use App\Livewire\Proyectos\Proyectos;
use App\Models\Apu;
use App\Models\CargoMo;
use App\Models\Empresa;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\ApuCalculator;
use App\Services\PresupuestoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProyectoTest extends TestCase
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
     * APU de Replanteo EXACTO al del Excel del usuario (hoja "1.1"):
     * Cuadrilla O+2A = 3 líneas en paralelo, cada una con D/M2 = 0.05875.
     *
     * Oficial   : 60.568  x 1,85 = 112.050,8  x 0,05875 = 6.582,9845
     * Ayudante 1: 30.284  x 1,85 =  56.025,4  x 0,05875 = 3.291,49225
     * Ayudante 2: idem                                = 3.291,49225
     * MO total                                       = 13.165,969
     * Herramientas 10%                               =  1.316,5969
     * COSTO DIRECTO                                  = 14.482,5659
     */
    private function crearApuReplanteo(): Apu
    {
        $oficial = CargoMo::create(['nombre' => 'Oficial', 'jornal' => 60568]);
        $ayudante = CargoMo::create(['nombre' => 'Ayudante de obra', 'jornal' => 30284]);

        $apu = Apu::create([
            'descripcion' => 'Replanteo con cuadrilla tipíca.',
            'unidad' => 'M2',
            'cuadrilla' => 'O+2A',
            'dias_por_unidad' => 0.05875,
        ]);

        foreach ([[$oficial->id], [$ayudante->id], [$ayudante->id]] as $i => [$cargoId]) {
            $apu->manoObra()->create(['cargo_id' => $cargoId, 'dias_por_unidad' => 0.05875, 'orden' => $i + 1]);
        }

        return $apu;
    }

    public function test_reproduce_el_presupuesto_del_excel_replanteo_con_aiu(): void
    {
        $apu = $this->crearApuReplanteo();

        // CD del APU: el valor exacto de la hoja "1.1"
        $cd = ApuCalculator::calcular($apu->fresh(['manoObra.cargo', 'empresa']))['totales']['costo_directo'];
        $this->assertEqualsWithDelta(14482.5659, $cd, 0.001);

        // Proyecto con los porcentajes por defecto de la empresa (10/5/10)
        $proyecto = Proyecto::create(['nombre' => 'VIVIENDA UNIFAMILIAR']);
        $linea = $proyecto->items()->create([
            'apu_id' => $apu->id,
            'item' => '1.1',
            'cantidad' => 50.8725,
            'orden' => 1,
        ]);
        $linea->update(['item' => '1']); // capítulo implícito no requerido para el cálculo

        // Igual que en la hoja PRESUPUESTO GENERAL fila 1.1:
        // G=14482,5659 | H=736.764,3337 | I=2,98875937
        $resultado = PresupuestoService::calcular(
            $proyecto->fresh(['items.apu.materiales.material', 'items.apu.manoObra.cargo', 'items.apu.equipos.equipo', 'items.apu.empresa'])
        );

        $primera = $resultado['lineas']->first();
        $this->assertEqualsWithDelta(14482.5659, $primera['vr_unitario'], 0.001);
        $this->assertEqualsWithDelta(736764.33, $primera['vr_total'], 0.01);
        $this->assertEqualsWithDelta(2.9888, $primera['duracion'], 0.0001);

        // Resumen AIU sobre el subtotal (10% / 5% / 10% -> total = subtotal x 1,25)
        $r = $resultado['resumen'];
        $this->assertEqualsWithDelta(736764.33, $r['subtotal'], 0.01);
        $this->assertEqualsWithDelta(73676.43, $r['administracion'], 0.02);
        $this->assertEqualsWithDelta(36838.22, $r['imprevistos'], 0.02);
        $this->assertEqualsWithDelta(73676.43, $r['utilidad'], 0.02);
        $this->assertEqualsWithDelta(920955.42, $r['total'], 0.03);
    }

    public function test_crea_proyecto_y_redirige_al_editor(): void
    {
        Livewire::test(Proyectos::class)
            ->call('abrirModal')
            ->set('form.nombre', 'VIVIENDA UNIFAMILIAR ETAPA 2')
            ->call('crear')
            ->assertHasNoErrors()
            ->assertRedirect(route('proyectos.editar', Proyecto::first()));

        $this->assertDatabaseHas('proyectos', [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'VIVIENDA UNIFAMILIAR ETAPA 2',
            'estado' => 'borrador',
        ]);
    }

    public function test_editor_agrega_capitulo_apu_y_actualiza_cantidad(): void
    {
        $apu = $this->crearApuReplanteo();
        $proyecto = Proyecto::create(['nombre' => 'PROYECTO PRUEBA']);

        Livewire::test(ProyectoEditor::class, ['proyecto' => $proyecto])
            // capítulo con código autogenerado y manual
            ->call('abrirCapitulo')
            ->assertSet('capitulo.item', '1')
            ->set('capitulo.titulo', 'OBRAS PRELIMINARES')
            ->call('agregarCapitulo')
            ->assertHasNoErrors()
            // APU se numera automáticamente bajo el último capítulo
            ->set('selApu', $apu->id)
            ->call('agregarApu');

        $this->assertDatabaseHas('proyecto_apu', [
            'proyecto_id' => $proyecto->id,
            'es_capitulo' => true,
            'item' => '1',
        ]);
        $this->assertDatabaseHas('proyecto_apu', [
            'proyecto_id' => $proyecto->id,
            'apu_id' => $apu->id,
            'item' => '1.1',
            'cantidad' => 1,
        ]);

        // Actualizar cantidad en vivo persiste
        $lineaId = $proyecto->items()->where('es_capitulo', false)->first()->id;

        Livewire::test(ProyectoEditor::class, ['proyecto' => $proyecto])
            ->set("cantidades.$lineaId", '50.8725')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('proyecto_apu', ['id' => $lineaId, 'cantidad' => 50.8725]);
    }

    public function test_guarda_porcentajes_aiu_personalizados_del_proyecto(): void
    {
        $proyecto = Proyecto::create(['nombre' => 'OBRA CON AIU PROPIO']);

        Livewire::test(ProyectoEditor::class, ['proyecto' => $proyecto])
            ->set('cab.pct_administracion', '15')
            ->set('cab.pct_utilidad', '12')
            ->call('guardarDatos')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('proyectos', [
            'id' => $proyecto->id,
            'pct_administracion' => 15,
            'pct_imprevistos' => 5,
            'pct_utilidad' => 12,
        ]);
    }

    public function test_no_permite_acceder_al_proyecto_de_otra_empresa(): void
    {
        $otraEmpresa = Empresa::query()->create(['nombre' => 'Otra Constructora']);
        $otroUser = User::query()->create([
            'name' => 'Ajeno',
            'email' => 'ajeno@otras.com',
            'password' => Hash::make('password123'),
            'empresa_id' => $otraEmpresa->id,
            'role' => 'ingeniero',
        ]);

        $proyecto = Proyecto::create(['nombre' => 'MI PROYECTO PRIVADO']);

        $this->actingAs($otroUser)
            ->get(route('proyectos.editar', $proyecto))
            ->assertNotFound();
    }

    public function test_lista_de_proyectos_carga_correctamente(): void
    {
        Proyecto::create(['nombre' => 'VIVIENDA UNIFAMILIAR']);

        Livewire::test(Proyectos::class)->assertOk();
    }
}
