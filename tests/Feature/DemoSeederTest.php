<?php

namespace Tests\Feature;

use App\Models\Apu;
use App\Models\CargoMo;
use App\Models\Empresa;
use App\Models\Material;
use App\Models\Proyecto;
use App\Models\ProyectoApu;
use App\Services\ApuCalculator;
use App\Services\PresupuestoService;
use Database\Seeders\DemoObraSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoObraSeeder::class);
    }

    private function empresa(): Empresa
    {
        return Empresa::where('nit', '901456789-2')->firstOrFail();
    }

    private function apu(string $codigo): Apu
    {
        return Apu::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa()->id)
            ->where('codigo', $codigo)
            ->firstOrFail();
    }

    private function proyecto(): Proyecto
    {
        return Proyecto::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa()->id)
            ->where('nombre', 'Casa lote 12, Altos de la Sabana')
            ->firstOrFail();
    }

    public function test_crea_tres_apus_y_un_proyecto_con_capitulos(): void
    {
        $empresa = $this->empresa();

        $this->assertSame(3, Apu::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count());
        $this->assertSame(1, Proyecto::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count());

        $proyecto = $this->proyecto();

        $this->assertSame(5, ProyectoApu::where('proyecto_id', $proyecto->id)->count());
        $this->assertSame(2, ProyectoApu::where('proyecto_id', $proyecto->id)->where('es_capitulo', true)->count());
    }

    public function test_el_apu_de_concreto_reproduce_la_hoja_del_ingeniero(): void
    {
        $apu = $this->apu('APU-001');
        $apu->load(['materiales.material', 'manoObra.cargo', 'equipos.equipo', 'empresa']);

        $totales = ApuCalculator::calcular($apu)['totales'];

        $this->assertEqualsWithDelta(290698.48, $totales['materiales'], 0.01);
        $this->assertEqualsWithDelta(49862.61, $totales['mano_obra'], 0.01);
        $this->assertEqualsWithDelta(8110.64, $totales['equipos'], 0.02);
        $this->assertEqualsWithDelta(348671.72, $totales['costo_directo'], 0.02);
    }

    public function test_el_proyecto_calcula_subtotal_y_aiu_sobre_el_subtotal(): void
    {
        $calculo = PresupuestoService::calcular($this->proyecto());

        // 85 M3 de concreto + 620 M2 de muro + 310 M2 de piso.
        $subtotalEsperado = round((348671.7216 * 85) + (25289.0192 * 620) + (39276.0 * 310), 2);

        $this->assertEqualsWithDelta($subtotalEsperado, $calculo['resumen']['subtotal'], 0.05);

        // AIU 10 % + 5 % + 10 % = 25 % del subtotal.
        $aiuEsperado = round($subtotalEsperado * 0.25, 2);
        $this->assertEqualsWithDelta($aiuEsperado, $calculo['resumen']['total'] - $calculo['resumen']['subtotal'], 0.05);
    }

    public function test_los_capitulos_del_proyecto_tienen_titulo(): void
    {
        $capitulos = collect(PresupuestoService::calcular($this->proyecto())['lineas'])
            ->where('es_capitulo', true)
            ->pluck('descripcion')
            ->values()
            ->all();

        $this->assertSame(['OBRA GRUESA', 'ACABADOS'], $capitulos);
    }

    public function test_es_idempotente_y_no_duplica_datos(): void
    {
        $this->seed(DemoObraSeeder::class);

        $empresa = $this->empresa();

        $this->assertSame(1, Empresa::where('nit', '901456789-2')->count());
        $this->assertSame(3, Apu::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count());
        $this->assertSame(1, Proyecto::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count());
        $this->assertSame(5, ProyectoApu::where('proyecto_id', $this->proyecto()->id)->count());
        $this->assertSame(47, Material::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count());
        $this->assertSame(2, CargoMo::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count());
    }
}
