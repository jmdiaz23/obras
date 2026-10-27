<?php

namespace Database\Seeders;

use App\Models\Apu;
use App\Models\CargoMo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\Proyecto;
use App\Models\ProyectoApu;
use App\Models\ProyectoEtapa;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración de ObraOS.
 *
 * Es idempotente: se puede correr sobre una base que ya tiene los catálogos
 * cargados y solo completa lo que falta (APUs, proyecto). No borra nada.
 */
class DemoObraSeeder extends Seeder
{
    private Empresa $empresa;

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'super@obras.com'],
            [
                'empresa_id' => null,
                'name' => 'Super Administrador',
                'password' => 'password123',
                'role' => User::ROLE_SUPER_ADMIN,
                'es_super_admin' => true,
            ],
        );

        $this->empresa = Empresa::firstOrCreate(
            ['nit' => '901456789-2'],
            [
                'nombre' => 'Constructora Jesu S.A.S.',
                'telefono' => '300 123 4567',
                'direccion' => 'Barranquilla, Colombia',
            ],
        );

        if (! User::where('email', 'jesu@obras.com')->exists()) {
            $this->empresa->users()->create([
                'name' => 'Jesu Ingeniero',
                'email' => 'jesu@obras.com',
                'password' => 'password123',
                'role' => User::ROLE_PROPIETARIO,
            ]);
        }

        $this->crearCatalogos();
        $this->crearApus();
        $this->crearProyecto();
    }

    // ---------------------------------------------------------------------
    // Catálogos
    // ---------------------------------------------------------------------

    private function crearCatalogos(): void
    {
        $proveedores = collect(['Homecenter', 'S.M.A.S', 'INVIAS'])->mapWithKeys(
            fn (string $nombre) => [
                $nombre => Proveedor::withoutGlobalScopes()->firstOrCreate([
                    'empresa_id' => $this->empresa->id,
                    'nombre' => $nombre,
                ]),
            ],
        );

        if (Material::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)->doesntExist()) {
            $materiales = [
                ['Cemento', 'KG', 530, 'Homecenter'],
                ['Agua potable', 'M3', 1840, 'S.M.A.S'],
                ['Agregado grueso', 'M3', 82048, 'Homecenter'],
                ['Agregado fino', 'M3', 64191, 'Homecenter'],
                ['Material seleccionado de acuerdo a la granulometria', 'M3', 24528, 'INVIAS'],
                ['Acero de refuerzo de 1/2"', 'KG', 4835, 'Homecenter'],
                ['Acero de refuerzo de 3/8"', 'KG', 4845, 'Homecenter'],
                ['Acero de refuerzo de 1/4"', 'KG', 5582, 'Homecenter'],
                ['Alambre Negro', 'KG', 2084, 'Homecenter'],
                ['Bloque de arena - cemento', 'UND', 312, 'Homecenter'],
                ['Estuco interior liso - CORONA', 'KG', 2630, 'Homecenter'],
                ['Estuco exterior rústico (tipo graniplast) - PINTUCO', 'KG', 2930, 'Homecenter'],
                ['Pintura blanca tipo 1 - PINTUCO', 'GAL', 30000, 'Homecenter'],
                ['Piso en cerámica antideslizante formato de 0,5 x 0,5 m color blanco - CORONA', 'M2', 19900, 'Homecenter'],
                ['Pegacor Corona', 'KG', 1208, 'Homecenter'],
                ['Boquilla Blanca', 'KG', 5950, 'Homecenter'],
                ['Ángulo perimetral (2,44m)', 'UND', 2650, 'Homecenter'],
                ['Omega (2,44m)', 'UND', 4050, 'Homecenter'],
                ['Masilla liquida', 'KG', 3750, 'Homecenter'],
                ['Chazo puntilla', 'UND', 930, 'Homecenter'],
                ['Tornillos 7x7/16"', 'UND', 35, 'Homecenter'],
                ['Tornillos 6X1"', 'UND', 34, 'Homecenter'],
                ['Lija 150', 'PLIEGO', 1300, 'Homecenter'],
                ['Cinta malla adhesiva 90m', 'UND', 16667, 'Homecenter'],
                ['Vigueta (Principales)', 'UND', 4400, 'Homecenter'],
                ['Chazo de 3/8"', 'UND', 590, 'Homecenter'],
                ['Tornillo de 2 1/2" para madera', 'UND', 135, 'Homecenter'],
                ['Puerta de madera entamborada, de 0,9m x 2m completa', 'UND', 245000, 'Homecenter'],
                ['Puerta de madera entamborada, de 0,7m x 2m completa', 'UND', 225000, 'Homecenter'],
                ['Puerta de madera entamborada, de 1m x 2m completa', 'UND', 300000, 'Homecenter'],
                ['Closet de habitación en madera MDF (1,81m x 1,20m x 0,46m)', 'UND', 569900, 'Homecenter'],
                ['Chazo', 'UND', 139, 'Homecenter'],
                ['Tornillo de 1 1/2"', 'UND', 74, 'Homecenter'],
                ['Ventana metálica, de 1,75m x 1,2m', 'UND', 300000, 'Homecenter'],
                ['Protector de ventana para ventana 1,75 x 1,2 m', 'UND', 152000, 'Homecenter'],
                ['Ventana metálica, de 1,47 x 1,2 m', 'UND', 265600, 'Homecenter'],
                ['Protector de ventana para ventana 1,47 x 1,2 m', 'UND', 148000, 'Homecenter'],
                ['Ventana metálica, de 1,25 x 1,2 m', 'UND', 225000, 'Homecenter'],
                ['Protector de ventana para ventana 1,25 x 1,2 m', 'UND', 110000, 'Homecenter'],
                ['Ventana metálica, de 0,7 x 1,2 m', 'UND', 126000, 'Homecenter'],
                ['Protector de ventana para ventana 0,7 x 1,2 m', 'UND', 61000, 'Homecenter'],
                ['Ventanillla de baño de 0,7 x 0,3 m', 'UND', 72900, 'Homecenter'],
                ['Protector de Ventanillla de baño de 0,7 x 0,3 m', 'UND', 36100, 'Homecenter'],
                ['Inodoro cerámico sanitario marca CORONA', 'UND', 163900, 'Homecenter'],
                ['Lavamanos cerámico marca CORONA', 'UND', 83900, 'Homecenter'],
                ['Lavaplatos metálico', 'UND', 139900, 'Homecenter'],
                ['Cemento blanco', 'KG', 2000, 'Homecenter'],
            ];

            foreach ($materiales as $i => [$descripcion, $unidad, $precio, $proveedor]) {
                Material::create([
                    'empresa_id' => $this->empresa->id,
                    'proveedor_id' => $proveedores[$proveedor]->id,
                    'codigo' => 'M'.($i + 1),
                    'descripcion' => $descripcion,
                    'unidad' => $unidad,
                    'precio' => $precio,
                ]);
            }
        }

        if (Equipo::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)->doesntExist()) {
            $equipos = [
                ['E1', 'Vibrocompactador manual', 'Rana', 39900, 'Homecenter'],
                ['E2', 'Vibrador manual para concreto', 'Eléctrico-portátil', 36900, 'S.M.A.S'],
                ['E3', 'Formaleteria metálica', 'Armable', 13760, 'Homecenter'],
                ['E4', 'Andamios tubulares', 'Armable Portátil', 35200, 'Homecenter'],
                ['E5', 'Escalera de aluminio', 'Retractil', 1500, 'Homecenter'],
                ['E6', 'Trompo rotatorio de 1 1/2 bulto', 'Gasolina', 49990, 'S.M.A.S'],
            ];

            foreach ($equipos as [$codigo, $descripcion, $tipo, $tarifa, $proveedor]) {
                Equipo::create([
                    'empresa_id' => $this->empresa->id,
                    'proveedor_id' => $proveedores[$proveedor]->id,
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'tipo' => $tipo,
                    'tarifa_dia' => $tarifa,
                ]);
            }
        }

        if (CargoMo::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)->doesntExist()) {
            CargoMo::create(['empresa_id' => $this->empresa->id, 'nombre' => 'Ayudante', 'jornal' => 30284, 'descripcion' => 'Jornal real del APU de concreto 3000 PSI']);
            CargoMo::create(['empresa_id' => $this->empresa->id, 'nombre' => 'Oficial', 'jornal' => 40000, 'descripcion' => 'Valor referencial, ajústalo a tu cuadrilla']);
        }
    }

    // ---------------------------------------------------------------------
    // APUs
    // ---------------------------------------------------------------------

    private function crearApus(): void
    {
        $this->crearApu('APU-001', [
            'descripcion' => 'CONCRETO DE 3000 PSI EN ZAPATAS',
            'unidad' => 'M3',
            'dias_por_unidad' => 0.89,
            'cuadrilla' => '1 ayudante',
            'pct_herramientas' => 10,
            'notas' => 'Reproduce la hoja "CONCRETO DE 3000 PSI" del Excel del ingeniero: costo directo 348.671,72.',
        ], [
            ['Cemento', 350, 0],
            ['Agua potable', 0.18, 0],
            ['Agregado grueso', 0.84, 0],
            ['Agregado fino', 0.56, 0],
        ], [
            ['Ayudante', 0.89],
        ], [
            ['Trompo rotatorio de 1 1/2 bulto', 0.5],
        ]);

        $this->crearApu('APU-002', [
            'descripcion' => 'MURO DE BLOQUE DE ARENA-CEMENTO',
            'unidad' => 'M2',
            'dias_por_unidad' => 0.23,
            'cuadrilla' => '1 oficial + 1 ayudante',
            'pct_herramientas' => 10,
            'notas' => 'Incluye 3 % de desperdicio en bloque. Valores referenciales, ajústalos a tu cuadrilla.',
        ], [
            ['Bloque de arena - cemento', 12.5, 3],
            ['Cemento', 6.5, 0],
            ['Material seleccionado de acuerdo a la granulometria', 0.028, 0],
        ], [
            ['Oficial', 0.15],
            ['Ayudante', 0.08],
        ], []);

        $this->crearApu('APU-003', [
            'descripcion' => 'INSTALACIÓN DE PISO CERÁMICO 0,5 x 0,5',
            'unidad' => 'M2',
            'dias_por_unidad' => 0.10,
            'cuadrilla' => '1 oficial',
            'pct_herramientas' => 10,
            'notas' => 'Incluye 5 % de desperdicio en cerámica. Valores referenciales.',
        ], [
            ['Piso en cerámica antideslizante formato de 0,5 x 0,5 m color blanco - CORONA', 1, 5],
            ['Pegacor Corona', 7, 0],
            ['Boquilla Blanca', 0.3, 0],
        ], [
            ['Oficial', 0.10],
        ], []);
    }

    /**
     * @param  array<int, array{0:string, 1:float, 2:float}>  $materiales
     * @param  array<int, array{0:string, 1:float}>  $manoObra
     * @param  array<int, array{0:string, 1:float}>  $equipos
     */
    private function crearApu(string $codigo, array $cabecera, array $materiales, array $manoObra, array $equipos): void
    {
        $apu = Apu::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $this->empresa->id, 'codigo' => $codigo],
            $cabecera,
        );

        if ($apu->materiales()->exists() || $apu->manoObra()->exists() || $apu->equipos()->exists()) {
            return;
        }

        foreach ($materiales as $orden => [$descripcion, $cantidad, $desperdicio]) {
            $material = $this->buscarMaterial($descripcion);

            if ($material === null) {
                continue;
            }

            $apu->materiales()->create([
                'material_id' => $material->id,
                'cantidad' => $cantidad,
                'desperdicio_pct' => $desperdicio,
                'orden' => $orden + 1,
            ]);
        }

        foreach ($manoObra as $orden => [$nombre, $dias]) {
            $cargo = $this->buscarCargo($nombre);

            if ($cargo === null) {
                continue;
            }

            $apu->manoObra()->create([
                'cargo_id' => $cargo->id,
                'dias_por_unidad' => $dias,
                'orden' => $orden + 1,
            ]);
        }

        foreach ($equipos as $orden => [$descripcion, $horas]) {
            $equipo = $this->buscarEquipo($descripcion);

            if ($equipo === null) {
                continue;
            }

            $apu->equipos()->create([
                'equipo_id' => $equipo->id,
                'unidad' => 'hr',
                'cantidad' => $horas,
                'orden' => $orden + 1,
            ]);
        }
    }

    // ---------------------------------------------------------------------
    // Proyecto
    // ---------------------------------------------------------------------

    private function crearProyecto(): void
    {
        $proyecto = Proyecto::withoutGlobalScopes()->firstOrCreate(
            [
                'empresa_id' => $this->empresa->id,
                'nombre' => 'Casa lote 12, Altos de la Sabana',
            ],
            [
                'cliente' => 'Carlos Mendoza',
                'ubicacion' => 'Barranquilla, Atlántico',
                'estado' => 'en_ejecucion',
                'pct_administracion' => 10,
                'pct_imprevistos' => 5,
                'pct_utilidad' => 10,
            ],
        );

        if ($proyecto->items()->exists()) {
            $etapaObraGris = ProyectoEtapa::firstOrCreate(
                ['proyecto_id' => $proyecto->id, 'nombre' => 'Etapa 1 - Obra gris'],
                ['orden' => 1, 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-10-31'],
            );
            $etapaAcabados = ProyectoEtapa::firstOrCreate(
                ['proyecto_id' => $proyecto->id, 'nombre' => 'Etapa 2 - Acabados'],
                ['orden' => 2, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2027-01-31'],
            );
            $this->vincularEtapasYAvance($proyecto, $etapaObraGris, $etapaAcabados);

            return;
        }

        $filas = [
            ['item' => '1', 'titulo' => 'OBRA GRUESA', 'apu' => null, 'cantidad' => null],
            ['item' => '1.1', 'titulo' => null, 'apu' => 'APU-001', 'cantidad' => 85],
            ['item' => '1.2', 'titulo' => null, 'apu' => 'APU-002', 'cantidad' => 620],
            ['item' => '2', 'titulo' => 'ACABADOS', 'apu' => null, 'cantidad' => null],
            ['item' => '2.1', 'titulo' => null, 'apu' => 'APU-003', 'cantidad' => 310],
        ];

        foreach ($filas as $orden => $fila) {
            $apu = $fila['apu'] === null
                ? null
                : Apu::withoutGlobalScopes()
                    ->where('empresa_id', $this->empresa->id)
                    ->where('codigo', $fila['apu'])
                    ->first();

            ProyectoApu::create([
                'proyecto_id' => $proyecto->id,
                'apu_id' => $apu?->id,
                'item' => $fila['item'],
                'titulo' => $fila['titulo'],
                'es_capitulo' => $fila['apu'] === null,
                'cantidad' => $fila['cantidad'],
                'cantidad_ejecutada' => 0,
                'orden' => $orden + 1,
            ]);
        }

        $etapaObraGris = ProyectoEtapa::firstOrCreate(
            ['proyecto_id' => $proyecto->id, 'nombre' => 'Etapa 1 - Obra gris'],
            ['orden' => 1, 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-10-31'],
        );
        $etapaAcabados = ProyectoEtapa::firstOrCreate(
            ['proyecto_id' => $proyecto->id, 'nombre' => 'Etapa 2 - Acabados'],
            ['orden' => 2, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2027-01-31'],
        );

        $this->vincularEtapasYAvance($proyecto, $etapaObraGris, $etapaAcabados);
    }

    private function vincularEtapasYAvance(Proyecto $proyecto, ProyectoEtapa $etapaObraGris, ProyectoEtapa $etapaAcabados): void
    {
        foreach (['1' => $etapaObraGris, '2' => $etapaAcabados] as $itemCapitulo => $etapa) {
            ProyectoApu::query()
                ->where('proyecto_id', $proyecto->id)
                ->where('item', $itemCapitulo)
                ->where('es_capitulo', true)
                ->whereNull('etapa_id')
                ->update(['etapa_id' => $etapa->id]);

            ProyectoApu::query()
                ->where('proyecto_id', $proyecto->id)
                ->where('es_capitulo', false)
                ->where('item', 'like', $itemCapitulo.'.%')
                ->whereNull('etapa_id')
                ->update(['etapa_id' => $etapa->id]);
        }

        $tieneAvance = ProyectoApu::query()
            ->where('proyecto_id', $proyecto->id)
            ->where('es_capitulo', false)
            ->where('cantidad_ejecutada', '>', 0)
            ->exists();

        if ($tieneAvance) {
            return;
        }

        // Avance de demostración: concreto terminado, muro parcial y piso parcial.
        foreach (['1.1' => 85, '1.2' => 380, '2.1' => 120] as $item => $ejecutada) {
            ProyectoApu::query()
                ->where('proyecto_id', $proyecto->id)
                ->where('es_capitulo', false)
                ->where('item', $item)
                ->update(['cantidad_ejecutada' => $ejecutada]);
        }
    }

    // ---------------------------------------------------------------------
    // Búsquedas
    // ---------------------------------------------------------------------

    private function buscarMaterial(string $descripcion): ?Material
    {
        return Material::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa->id)
            ->where('descripcion', $descripcion)
            ->first();
    }

    private function buscarCargo(string $nombre): ?CargoMo
    {
        return CargoMo::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa->id)
            ->where('nombre', $nombre)
            ->first();
    }

    private function buscarEquipo(string $descripcion): ?Equipo
    {
        return Equipo::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa->id)
            ->where('descripcion', $descripcion)
            ->first();
    }
}
