<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plantillas de importación masiva
    |--------------------------------------------------------------------------
    |
    | Estas definiciones se usan para generar el archivo de plantilla que el
    | usuario descarga y para mostrar las columnas esperadas en el modal.
    |
    */

    'proveedores' => [
        'titulo' => 'Proveedores',
        'columnas' => ['NOMBRE', 'CONTACTO', 'TELEFONO', 'EMAIL', 'DIRECCION'],
        'ejemplo' => ['Ferretería Central', 'Juan Pérez', '3001234567', 'ventas@ferreteria.com', 'Calle 10 #20-30'],
        'nota' => 'El nombre es la llave: si ya existe un proveedor con ese nombre, se actualiza. Las celdas vacías no borran datos existentes.',
    ],

    'materiales' => [
        'titulo' => 'Materiales',
        'columnas' => ['CODIGO', 'DESCRIPCION', 'UNIDAD', 'PRECIO', 'PROVEEDOR'],
        'ejemplo' => ['M001', 'Cemento gris tipo UGC', 'UND', 28500, 'Ferretería Central'],
        'nota' => 'El código es la llave: si ya existe, se actualiza. El proveedor debe existir previamente y las celdas vacías no borran datos existentes.',
    ],

    'equipos' => [
        'titulo' => 'Equipos',
        'columnas' => ['CODIGO', 'DESCRIPCION', 'TIPO', 'TARIFA_DIA', 'PROVEEDOR'],
        'ejemplo' => ['E001', 'Mezcladora 1 bulto', 'PROPIO', 45000, ''],
        'nota' => 'El código es la llave. La tarifa es por día y las celdas vacías no borran datos existentes.',
    ],

    'cargos_mo' => [
        'titulo' => 'Mano de obra',
        'columnas' => ['CARGO', 'JORNAL', 'DESCRIPCION'],
        'ejemplo' => ['Oficial de obra', 120000, 'Oficial con experiencia'],
        'nota' => 'El cargo es la llave. Las prestaciones se calculan con el porcentaje de la empresa y las celdas vacías no borran datos existentes.',
    ],

];
