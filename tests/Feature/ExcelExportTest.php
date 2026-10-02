<?php

namespace Tests\Feature;

use App\Models\Apu;
use App\Models\CargoMo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Material;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\ApuCalculator;
use App\Services\PresupuestoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ExcelExportTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::query()->create([
            'nombre' => 'Constructora Test S.A.S.',
            'nit' => '901.234.567-8',
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

    /** APU de Replanteo idéntico a la hoja "1.1": CD = 14.482,5659 */
    private function crearApuReplanteo(): Apu
    {
        $oficial = CargoMo::query()->create(['nombre' => 'Oficial', 'jornal' => 60568]);
        $ayudante = CargoMo::query()->create(['nombre' => 'Ayudante de obra', 'jornal' => 30284]);

        $apu = Apu::query()->create([
            'codigo' => '1.1',
            'descripcion' => 'Replanteo general de obra',
            'unidad' => 'M2',
            'cuadrilla' => 'OFICIAL + 2 AYUDANTES',
            'dias_por_unidad' => 0.05875,
        ]);

        foreach ([[$oficial->id], [$ayudante->id], [$ayudante->id]] as $i => [$cargoId]) {
            $apu->manoObra()->create(['cargo_id' => $cargoId, 'dias_por_unidad' => 0.05875, 'orden' => $i + 1]);
        }

        return $apu;
    }

    /** APU con materiales y equipos para ejercitar las secciones A y C */
    private function crearApuConcreto(Material $material, Equipo $equipo): Apu
    {
        $ayudante = CargoMo::query()->create(['nombre' => 'Ayudante de obra', 'jornal' => 30284]);

        $apu = Apu::query()->create([
            'codigo' => '2.1',
            'descripcion' => 'Concreto de 3000 PSI',
            'unidad' => 'M3',
            'cuadrilla' => 'AYUDANTE',
            'dias_por_unidad' => 2.5,
        ]);

        $apu->materiales()->create(['material_id' => $material->id, 'cantidad' => 6, 'desperdicio_pct' => 10, 'orden' => 1]);
        $apu->manoObra()->create(['cargo_id' => $ayudante->id, 'dias_por_unidad' => 2.5, 'orden' => 1]);
        $apu->equipos()->create(['equipo_id' => $equipo->id, 'horas_por_unidad' => 4, 'orden' => 1]);

        return $apu;
    }

    private function generarExcel(Proyecto $proyecto): string
    {
        return $this->get(route('proyectos.exportar', $proyecto))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->streamedContent();
    }

    private function abrir(string $contenido): Spreadsheet
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        file_put_contents($ruta, $contenido);

        return IOFactory::load($ruta);
    }

    public function test_exporta_libro_con_todas_las_hojas_y_contenido_dorado(): void
    {
        $apu = $this->crearApuReplanteo();

        $proyecto = Proyecto::query()->create(['nombre' => 'VIVIENDA UNIFAMILIAR', 'cliente' => 'Cliente Demo']);
        $proyecto->items()->create(['apu_id' => $apu->id, 'item' => '1.1', 'cantidad' => 50.8725, 'orden' => 1]);

        $libro = $this->abrir($this->generarExcel($proyecto));

        $this->assertEquals([
            'LISTA DE PRECIOS',
            'ALQUILER DE EQUIPOS',
            'RENDIMIENTO',
            'APU 1.1',
            'MEMORIA DE CALCULO',
            'PRESUPUESTO GENERAL',
            'TABLA DE TAREAS',
            'EJE UNIVERSAL',
        ], $libro->getSheetNames());

        // MEMORIA DE CALCULO: fila 3 = ítem 1.1 (los valores de la hoja original)
        $memoria = $libro->getSheetByName('MEMORIA DE CALCULO');
        $this->assertSame(14482.5659, round((float) $memoria->getCell('E3')->getValue(), 4));
        $this->assertSame(736764.33, (float) $memoria->getCell('F3')->getValue());
        $this->assertEqualsWithDelta(2.9888, (float) $memoria->getCell('G3')->getValue(), 0.0001);

        // PRESUPUESTO GENERAL: ítem + resumen AIU (10/5/10 -> total x1.25)
        $presupuesto = $libro->getSheetByName('PRESUPUESTO GENERAL');
        $this->assertSame(14482.5659, round((float) $presupuesto->getCell('E7')->getValue(), 4));
        $this->assertSame(736764.33, (float) $presupuesto->getCell('F7')->getValue());
        $this->assertSame(736764.33, (float) $presupuesto->getCell('F9')->getValue()); // subtotal
        $this->assertSame(73676.43, (float) $presupuesto->getCell('F10')->getValue()); // adm
        $this->assertSame(36838.22, (float) $presupuesto->getCell('F11')->getValue()); // imp
        $this->assertSame(73676.43, (float) $presupuesto->getCell('F12')->getValue()); // util
        $this->assertSame(920955.41, (float) $presupuesto->getCell('F13')->getValue()); // total
    }

    public function test_exporta_secciones_a_b_c_de_los_apus_y_catalogos(): void
    {
        $material = Material::query()->create([
            'proveedor_id' => null,
            'codigo' => 'M-001',
            'descripcion' => 'CEMENTO GRIS 3000 PSI',
            'unidad' => 'UND',
            'precio' => 530,
        ]);

        $equipo = Equipo::query()->create([
            'proveedor_id' => null,
            'codigo' => 'EQ-001',
            'descripcion' => 'TROMPO MEZCLADOR 320L',
            'tipo' => 'CONCRETO',
            'tarifa_dia' => 24995,
        ]);

        $apu = $this->crearApuConcreto($material, $equipo);
        $proyecto = Proyecto::query()->create(['nombre' => 'OBRA CON APUs COMPLETOS']);
        $proyecto->items()->create(['apu_id' => $apu->id, 'item' => '2.1', 'cantidad' => 3, 'orden' => 1]);

        $libro = $this->abrir($this->generarExcel($proyecto));

        // Catálogos: primero sucede el material y el equipo
        $precios = $libro->getSheetByName('LISTA DE PRECIOS');
        $this->assertSame('M-001', $precios->getCell('B3')->getValue());
        $this->assertSame('CEMENTO GRIS 3000 PSI', $precios->getCell('C3')->getValue());
        $this->assertSame(530.0, (float) $precios->getCell('F3')->getValue());

        $alquiler = $libro->getSheetByName('ALQUILER DE EQUIPOS');
        $this->assertSame('EQ-001', $alquiler->getCell('B3')->getValue());
        $this->assertSame(24995.0, (float) $alquiler->getCell('F3')->getValue());

        // RENDIMIENTO: 1/dias_por_unidad de la cuadrilla
        $rendimiento = $libro->getSheetByName('RENDIMIENTO');
        $this->assertSame(0.4, (float) $rendimiento->getCell('E3')->getValue());
        $this->assertSame(2.5, (float) $rendimiento->getCell('F3')->getValue());

        // Hoja APU: secciones A/B/C y totales
        $apuHoja = $libro->getSheetByName('APU 2.1');
        $calculo = ApuCalculator::calcular($apu->fresh(['materiales.material', 'manoObra.cargo', 'equipos.equipo', 'empresa']));
        $cd = $calculo['totales']['costo_directo'];

        $contenido = '';
        foreach ($apuHoja->toArray() as $filaExcel) {
            $contenido .= implode('|', $filaExcel)."\n";
        }

        $this->assertStringContainsString('A. MATERIALES', $contenido);
        $this->assertStringContainsString('B. MANO DE OBRA', $contenido);
        $this->assertStringContainsString('C. EQUIPOS', $contenido);
        $this->assertStringContainsString('COSTO DIRECTO', $contenido);

        $filaCd = null;
        foreach ($apuHoja->toArray() as $i => $filaExcel) {
            if (isset($filaExcel[0]) && str_starts_with($filaExcel[0], 'COSTO DIRECTO')) {
                $filaCd = $i + 1;
                break;
            }
        }
        $this->assertNotNull($filaCd, 'Debe existir la celda COSTO DIRECTO');
        $this->assertSame(round($cd, 4), round((float) $apuHoja->getCell("G{$filaCd}")->getValue(), 4));

        // PRESUPUESTO GENERAL: vr. unitario = CD del APU (sin AIU)
        $presupuesto = $libro->getSheetByName('PRESUPUESTO GENERAL');
        $this->assertSame(round($cd, 4), round((float) $presupuesto->getCell('E7')->getValue(), 4));

        $r = PresupuestoService::calcular($proyecto->fresh(['items.apu.materiales.material', 'items.apu.manoObra.cargo', 'items.apu.equipos.equipo', 'items.apu.empresa']))['resumen'];
        $this->assertSame($r['subtotal'], (float) $presupuesto->getCell('F9')->getValue());
        $this->assertSame($r['total'], (float) $presupuesto->getCell('F13')->getValue());
    }

    public function test_ruta_de_exportacion_requiere_autenticacion(): void
    {
        $proyecto = Proyecto::query()->create(['nombre' => 'P']);

        Auth::logout();

        $this->get(route('proyectos.exportar', $proyecto))
            ->assertRedirect(route('login'));
    }

    public function test_no_permite_exportar_el_proyecto_de_otra_empresa(): void
    {
        $otraEmpresa = Empresa::query()->create(['nombre' => 'Otra Constructora']);
        $otroUser = User::query()->create([
            'name' => 'Ajeno',
            'email' => 'ajeno@otras.com',
            'password' => Hash::make('password123'),
            'empresa_id' => $otraEmpresa->id,
            'role' => 'ingeniero',
        ]);

        $proyecto = Proyecto::query()->create(['nombre' => 'MI PROYECTO PRIVADO']);

        $this->actingAs($otroUser)
            ->get(route('proyectos.exportar', $proyecto))
            ->assertNotFound();
    }
}
