<?php

namespace Database\Seeders;

use App\Models\CargoMo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoObraSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'jesu@obras.com')->exists()) {
            return;
        }

        $empresa = Empresa::create([
            'nombre' => 'Constructora Jesu S.A.S.',
            'nit' => '901456789-2',
            'telefono' => '300 123 4567',
            'direccion' => 'Barranquilla, Colombia',
        ]);

        $empresa->users()->create([
            'name' => 'Jesu Ingeniero',
            'email' => 'jesu@obras.com',
            'password' => 'password123',
            'role' => User::ROLE_PROPIETARIO,
        ]);

        $proveedores = collect([
            'Homecenter' => Proveedor::create(['empresa_id' => $empresa->id, 'nombre' => 'Homecenter']),
            'S.M.A.S' => Proveedor::create(['empresa_id' => $empresa->id, 'nombre' => 'S.M.A.S']),
            'INVIAS' => Proveedor::create(['empresa_id' => $empresa->id, 'nombre' => 'INVIAS']),
        ]);

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
                'empresa_id' => $empresa->id,
                'proveedor_id' => $proveedores[$proveedor]->id,
                'codigo' => 'M'.($i + 1),
                'descripcion' => $descripcion,
                'unidad' => $unidad,
                'precio' => $precio,
            ]);
        }

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
                'empresa_id' => $empresa->id,
                'proveedor_id' => $proveedores[$proveedor]->id,
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'tipo' => $tipo,
                'tarifa_dia' => $tarifa,
            ]);
        }

        CargoMo::create(['empresa_id' => $empresa->id, 'nombre' => 'Ayudante', 'jornal' => 30284, 'descripcion' => 'Jornal real del APU de concreto 3000 PSI']);
        CargoMo::create(['empresa_id' => $empresa->id, 'nombre' => 'Oficial', 'jornal' => 40000, 'descripcion' => 'Valor referencial, ajústalo a tu cuadrilla']);
    }
}
