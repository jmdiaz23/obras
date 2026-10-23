<?php

namespace Tests\Feature;

use App\Livewire\Catalogos\Materiales;
use App\Models\Empresa;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\ImportacionExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportacionExcelTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create(['nombre' => 'Importadora Uno']);
        $this->user = $this->empresa->users()->create([
            'name' => 'Usuario Importador',
            'email' => 'importador@test.com',
            'password' => 'password123',
            'role' => User::ROLE_PROPIETARIO,
        ]);

        $this->actingAs($this->user);
    }

    private function rutaCsv(string $contenido): string
    {
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('import_', true).'.csv';
        file_put_contents($ruta, $contenido);

        return $ruta;
    }

    private function archivoXlsx(array $filas): UploadedFile
    {
        $libro = new Spreadsheet();
        $libro->getActiveSheet()->fromArray($filas, null, 'A1');

        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('import_', true).'.xlsx';
        (new Xlsx($libro))->save($ruta);
        $libro->disconnectWorksheets();

        $archivo = UploadedFile::fake()->createWithContent('datos.xlsx', file_get_contents($ruta));

        @unlink($ruta);

        return $archivo;
    }

    private function importar(string $modulo, string $contenido): array
    {
        return app(ImportacionExcelService::class)->importar(
            $modulo,
            $this->rutaCsv($contenido),
            $this->empresa->id,
        );
    }

    public function test_importa_materiales_crea_actualiza_y_reporta_errores_por_fila(): void
    {
        $proveedor = Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Ferretería Central',
        ]);

        $resultado = $this->importar('materiales', implode("\n", [
            'CODIGO;DESCRIPCION;UNIDAD;PRECIO;PROVEEDOR',
            'M001;Cemento gris;UND;28.500,50;Ferretería Central',
            'M002;Arena fina;M3;90000;',
            'M001;Cemento gris actualizado;UND;30000;',
            'M003;Sin precio;UND;abc;',
            'M004;Proveedor fantasma;UND;1000;No existe',
        ]));

        $this->assertSame(2, $resultado['creados']);
        $this->assertSame(1, $resultado['actualizados']);
        $this->assertCount(2, $resultado['errores']);
        $this->assertSame(5, $resultado['errores'][0]['fila']);
        $this->assertSame(6, $resultado['errores'][1]['fila']);

        $this->assertDatabaseHas('materiales', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'M001',
            'descripcion' => 'Cemento gris actualizado',
            'precio' => 30000,
            'proveedor_id' => $proveedor->id,
        ]);
        $this->assertDatabaseHas('materiales', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'M002',
            'unidad' => 'M3',
            'precio' => 90000,
        ]);
        $this->assertDatabaseMissing('materiales', ['codigo' => 'M003']);
        $this->assertDatabaseMissing('materiales', ['codigo' => 'M004']);
    }

    public function test_importa_equipos_y_mano_de_obra_tolerando_alias_y_formatos_de_moneda(): void
    {
        $resultadoEquipos = $this->importar('equipos', implode("\n", [
            'Código;Equipo;Tipo;Tarifa Día',
            'E001;Mezcladora 1 bulto;Propio;$ 45.000',
        ]));

        $this->assertSame(1, $resultadoEquipos['creados']);
        $this->assertDatabaseHas('equipos', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'E001',
            'tarifa_dia' => 45000,
        ]);

        $resultadoCargos = $this->importar('cargos_mo', implode("\n", [
            'Cargo;Salario;Descripción',
            'Oficial de obra;120.000;Oficial con experiencia',
            'Ayudante;$90.000;',
        ]));

        $this->assertSame(2, $resultadoCargos['creados']);
        $this->assertDatabaseHas('cargos_mo', [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Oficial de obra',
            'jornal' => 120000,
        ]);
    }

    public function test_importa_proveedores_por_nombre_y_actualiza_el_existente(): void
    {
        Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Homecenter',
        ]);

        $resultado = $this->importar('proveedores', implode("\n", [
            'NOMBRE;CONTACTO;TELEFONO;EMAIL;DIRECCION',
            'Homecenter;Juan Vendedor;3001234567;ventas@homecenter.com;Calle 10',
            'Sodimac;Ana Pérez;3107654321;ana@sodimac.com;Carrera 20',
        ]));

        $this->assertSame(1, $resultado['creados']);
        $this->assertSame(1, $resultado['actualizados']);

        $this->assertDatabaseHas('proveedores', [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Homecenter',
            'contacto' => 'Juan Vendedor',
            'email' => 'ventas@homecenter.com',
        ]);
        $this->assertDatabaseHas('proveedores', ['nombre' => 'Sodimac']);
    }

    public function test_nunca_modifica_registros_de_otra_empresa(): void
    {
        $ajena = Empresa::create(['nombre' => 'Empresa Ajena']);
        Material::create([
            'empresa_id' => $ajena->id,
            'codigo' => 'M001',
            'descripcion' => 'Material ajeno',
            'unidad' => 'UND',
            'precio' => 1,
        ]);

        $this->importar('materiales', implode("\n", [
            'CODIGO;DESCRIPCION;UNIDAD;PRECIO',
            'M001;Material propio;UND;5000',
        ]));

        $this->assertDatabaseHas('materiales', [
            'empresa_id' => $ajena->id,
            'codigo' => 'M001',
            'descripcion' => 'Material ajeno',
        ]);
        $this->assertDatabaseHas('materiales', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'M001',
            'descripcion' => 'Material propio',
        ]);
        $this->assertSame(1, Material::withoutGlobalScopes()->where('empresa_id', $ajena->id)->count());
    }

    public function test_rechaza_columnas_desconocidas_sin_importar_nada(): void
    {
        $resultado = $this->importar('materiales', "FOO;BAR\n1;2");

        $this->assertSame(0, $resultado['creados']);
        $this->assertSame(0, $resultado['actualizados']);
        $this->assertNotEmpty($resultado['errores']);
        $this->assertSame(1, $resultado['errores'][0]['fila']);
    }

    public function test_el_componente_importa_desde_un_archivo_excel_subido(): void
    {
        Livewire::test(Materiales::class)
            ->call('abrirImport')
            ->assertSet('mostrarImport', true)
            ->set('archivoImport', $this->archivoXlsx([
                ['CODIGO', 'DESCRIPCION', 'UNIDAD', 'PRECIO'],
                ['X1', 'Yeso de construcción', 'UND', 15000],
                ['X2', 'Cerámica 60x60', 'M2', 48000],
            ]))
            ->call('importar')
            ->assertHasNoErrors()
            ->assertSet('resultadoImport.creados', 2)
            ->assertSet('resultadoImport.actualizados', 0);

        $this->assertDatabaseHas('materiales', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'X1',
            'precio' => 15000,
        ]);
        $this->assertDatabaseHas('materiales', [
            'empresa_id' => $this->empresa->id,
            'codigo' => 'X2',
            'unidad' => 'M2',
        ]);
    }

    public function test_el_componente_exige_un_archivo_antes_de_importar(): void
    {
        Livewire::test(Materiales::class)
            ->call('importar')
            ->assertHasErrors(['archivoImport']);

        $this->assertSame(0, Material::count());
    }

    public function test_descarga_la_plantilla_en_formato_excel(): void
    {
        $this->get(route('catalogos.plantilla', ['modulo' => 'materiales']))
            ->assertOk()
            ->assertDownload('plantilla-materiales.xlsx');
    }

    public function test_devuelve_404_para_una_plantilla_inexistente(): void
    {
        $this->get(route('catalogos.plantilla', ['modulo' => 'inventado']))->assertNotFound();
    }
}
