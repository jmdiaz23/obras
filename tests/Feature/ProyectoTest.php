<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\ProyectoEditor;
use App\Livewire\Proyectos\Proyectos;
use App\Models\Apu;
use App\Models\CargoMo;
use App\Models\Empresa;
use App\Models\Proyecto;
use App\Models\ProyectoApu;
use App\Models\ProyectoEtapa;
use App\Models\User;
use App\Services\ApuCalculator;
use App\Services\AvanceService;
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

    public function test_calcula_subtotal_por_capitulo(): void
    {
        $apu = $this->crearApuReplanteo();
        $cd = ApuCalculator::calcular($apu->fresh(['manoObra.cargo', 'empresa']))['totales']['costo_directo'];

        $proyecto = Proyecto::create(['nombre' => 'PROYECTO CON CAPITULOS']);

        $capitulo1 = $proyecto->items()->create([
            'apu_id' => null, 'item' => '1', 'titulo' => 'OBRAS PRELIMINARES',
            'es_capitulo' => true, 'cantidad' => null, 'orden' => 1,
        ]);
        $proyecto->items()->create(['apu_id' => $apu->id, 'item' => '1.1', 'cantidad' => 10, 'orden' => 2]);
        $proyecto->items()->create(['apu_id' => $apu->id, 'item' => '1.2', 'cantidad' => 5, 'orden' => 3]);

        $capitulo2 = $proyecto->items()->create([
            'apu_id' => null, 'item' => '2', 'titulo' => 'ESTRUCTURA',
            'es_capitulo' => true, 'cantidad' => null, 'orden' => 4,
        ]);
        $proyecto->items()->create(['apu_id' => $apu->id, 'item' => '2.1', 'cantidad' => 1, 'orden' => 5]);

        $resultado = PresupuestoService::calcular(
            $proyecto->fresh(['items.apu.materiales.material', 'items.apu.manoObra.cargo', 'items.apu.equipos.equipo', 'items.apu.empresa'])
        );

        $lineaCap1 = $resultado['lineas']->firstWhere('id', $capitulo1->id);
        $lineaCap2 = $resultado['lineas']->firstWhere('id', $capitulo2->id);

        $this->assertEqualsWithDelta(15 * $cd, $lineaCap1['subtotal_capitulo'], 0.02);
        $this->assertEqualsWithDelta(1 * $cd, $lineaCap2['subtotal_capitulo'], 0.02);
        $this->assertEqualsWithDelta(16 * $cd, $resultado['resumen']['subtotal'], 0.02);
    }

    public function test_calcula_iva_solo_sobre_la_utilidad(): void
    {
        $apu = $this->crearApuReplanteo();

        $proyecto = Proyecto::create([
            'nombre' => 'OBRA CON IVA SOBRE UTILIDAD',
            'pct_utilidad' => 10,
            'pct_iva_utilidad' => 19,
        ]);

        $proyecto->items()->create([
            'apu_id' => $apu->id, 'item' => '1.1', 'cantidad' => 10, 'orden' => 1,
        ]);

        $r = PresupuestoService::calcular(
            $proyecto->fresh(['items.apu.materiales.material', 'items.apu.manoObra.cargo', 'items.apu.equipos.equipo', 'items.apu.empresa'])
        )['resumen'];

        $ivaEsperado = round($r['utilidad'] * 0.19, 2);

        $this->assertEqualsWithDelta($ivaEsperado, $r['iva_utilidad'], 0.01);
        $this->assertEqualsWithDelta(
            $r['subtotal'] + $r['administracion'] + $r['imprevistos'] + $r['utilidad'] + $ivaEsperado,
            $r['total'],
            0.01,
        );
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
            ->set('cab.pct_iva_utilidad', '19')
            ->call('guardarDatos')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('proyectos', [
            'id' => $proyecto->id,
            'pct_administracion' => 15,
            'pct_imprevistos' => 5,
            'pct_utilidad' => 12,
            'pct_iva_utilidad' => 19,
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

        $this->actingAs($otroUser)
            ->get(route('proyectos.avance', $proyecto))
            ->assertNotFound();
    }

    public function test_lista_de_proyectos_carga_correctamente(): void
    {
        Proyecto::create(['nombre' => 'VIVIENDA UNIFAMILIAR']);

        Livewire::test(Proyectos::class)->assertOk();
    }

    public function test_crea_etapa_asigna_capitulo_y_propaga_a_los_items(): void
    {
        $apu = $this->crearApuReplanteo();
        $proyecto = Proyecto::create(['nombre' => 'OBRA POR ETAPAS']);

        $capitulo = $proyecto->items()->create([
            'apu_id' => null,
            'item' => '1',
            'titulo' => 'OBRAS PRELIMINARES',
            'es_capitulo' => true,
            'cantidad' => null,
            'orden' => 1,
        ]);

        $item = $proyecto->items()->create([
            'apu_id' => $apu->id,
            'item' => '1.1',
            'cantidad' => 10,
            'cantidad_ejecutada' => 2,
            'orden' => 2,
        ]);

        Livewire::test(ProyectoEditor::class, ['proyecto' => $proyecto])
            ->call('abrirEtapa')
            ->set('etapa.nombre', 'Etapa 1 - Obra gris')
            ->set('etapa.fecha_inicio', '2026-08-01')
            ->set('etapa.fecha_fin', '2026-10-31')
            ->call('agregarEtapa')
            ->assertHasNoErrors();

        $etapa = ProyectoEtapa::where('proyecto_id', $proyecto->id)->firstOrFail();

        $this->assertSame('Etapa 1 - Obra gris', $etapa->nombre);
        $this->assertSame('2026-08-01', $etapa->fecha_inicio->toDateString());
        $this->assertSame('2026-10-31', $etapa->fecha_fin->toDateString());

        Livewire::test(ProyectoEditor::class, ['proyecto' => $proyecto])
            ->call('asignarEtapa', $capitulo->id, $etapa->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('proyecto_apu', [
            'id' => $capitulo->id,
            'etapa_id' => $etapa->id,
        ]);
        $this->assertDatabaseHas('proyecto_apu', [
            'id' => $item->id,
            'etapa_id' => $etapa->id,
        ]);

        // Un APU nuevo bajo el capítulo hereda su etapa.
        Livewire::test(ProyectoEditor::class, ['proyecto' => $proyecto])
            ->set('selApu', $apu->id)
            ->call('agregarApu')
            ->assertHasNoErrors();

        $nuevoItem = ProyectoApu::where('proyecto_id', $proyecto->id)
            ->where('item', '1.2')
            ->firstOrFail();

        $this->assertSame($etapa->id, $nuevoItem->etapa_id);
    }

    public function test_calcula_avance_financiero_y_fisico_con_cantidades_ejecutadas(): void
    {
        $apu = $this->crearApuReplanteo();
        $proyecto = Proyecto::create(['nombre' => 'OBRA CON AVANCE']);

        $etapa = ProyectoEtapa::create([
            'proyecto_id' => $proyecto->id,
            'nombre' => 'Etapa única',
            'orden' => 1,
        ]);

        $proyecto->items()->create([
            'apu_id' => null,
            'etapa_id' => $etapa->id,
            'item' => '1',
            'titulo' => 'REPLANTEO',
            'es_capitulo' => true,
            'cantidad' => null,
            'orden' => 1,
        ]);

        $item = $proyecto->items()->create([
            'apu_id' => $apu->id,
            'etapa_id' => $etapa->id,
            'item' => '1.1',
            'cantidad' => 10,
            'cantidad_ejecutada' => 0,
            'orden' => 2,
        ]);

        Livewire::test(ProyectoEditor::class, ['proyecto' => $proyecto])
            ->set("ejecutadas.{$item->id}", '5')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('proyecto_apu', [
            'id' => $item->id,
            'cantidad_ejecutada' => 5,
        ]);

        $avance = AvanceService::calcular($proyecto->fresh());

        $this->assertSame(50.0, $avance['resumen']['avance_financiero']);
        $this->assertSame(50.0, $avance['resumen']['avance_fisico']);
        $this->assertSame('M2', $avance['resumen']['unidad_fisica']);
        $this->assertSame(1, $avance['resumen']['items']);
        $this->assertSame(0, $avance['resumen']['items_terminados']);
        $this->assertSame(50.0, $avance['capitulos'][0]['avance_financiero']);
        $this->assertSame(50.0, $avance['capitulos'][0]['avance_fisico']);
        $this->assertSame(50.0, $avance['etapas'][0]['avance_financiero']);
        $this->assertSame(50.0, $avance['etapas'][0]['avance_fisico']);
        $this->assertSame('Etapa única', $avance['etapas'][0]['nombre']);
    }

    public function test_eliminar_etapa_deja_los_capitulos_sin_etapa(): void
    {
        $proyecto = Proyecto::create(['nombre' => 'OBRA CON ETAPA TEMPORAL']);
        $etapa = ProyectoEtapa::create([
            'proyecto_id' => $proyecto->id,
            'nombre' => 'Temporal',
            'orden' => 1,
        ]);
        $capitulo = $proyecto->items()->create([
            'apu_id' => null,
            'etapa_id' => $etapa->id,
            'item' => '1',
            'titulo' => 'CAPITULO',
            'es_capitulo' => true,
            'cantidad' => null,
            'orden' => 1,
        ]);

        Livewire::test(ProyectoEditor::class, ['proyecto' => $proyecto])
            ->call('eliminarEtapa', $etapa->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('proyecto_etapas', ['id' => $etapa->id]);
        $this->assertDatabaseHas('proyecto_apu', [
            'id' => $capitulo->id,
            'etapa_id' => null,
        ]);
    }

    public function test_vista_de_avance_carga_para_la_empresa_del_proyecto(): void
    {
        $proyecto = Proyecto::create(['nombre' => 'OBRA VISIBLE']);

        $this->get(route('proyectos.avance', $proyecto))
            ->assertOk()
            ->assertSee('Avance de obra')
            ->assertSee('OBRA VISIBLE');
    }

    public function test_dashboard_muestra_la_tarjeta_de_avance(): void
    {
        Proyecto::create(['nombre' => 'OBRA EN DASHBOARD']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Avance de obra')
            ->assertSee('OBRA EN DASHBOARD')
            ->assertSee('Abrir avance');
    }
}
