<?php

namespace App\Services;

use App\Models\CargoMo;
use App\Models\Equipo;
use App\Models\Material;
use App\Models\Proveedor;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importa catálogos desde un archivo Excel (.xlsx, .xls o .csv).
 *
 * Reglas:
 *  - La primera fila debe contener los encabezados.
 *  - Se toleran mayúsculas, tildes y distintos nombres de columna.
 *  - Si el registro ya existe (misma empresa), se actualiza.
 *  - Los errores se reportan por número de fila sin detener el resto.
 *  - Todo queda aislado por empresa: nunca se toca data de otra empresa.
 */
class ImportacionExcelService
{
    public const MAX_FILAS = 5000;

    public const MAX_ERRORES = 60;

    /**
     * @return array{creados:int, actualizados:int, omitidos:int, errores:array<int, array{fila:int, mensaje:string}>}
     */
    public function importar(string $modulo, string $ruta, int $empresaId): array
    {
        $modulos = ['proveedores', 'materiales', 'equipos', 'cargos_mo'];

        if (! in_array($modulo, $modulos, true)) {
            return $this->resultado(errores: [
                ['fila' => 0, 'mensaje' => 'Módulo de importación no válido.'],
            ]);
        }

        $filas = $this->leerArchivo($ruta);

        if ($filas === []) {
            return $this->resultado(errores: [
                ['fila' => 0, 'mensaje' => 'El archivo está vacío.'],
            ]);
        }

        $columnas = $this->mapearEncabezados($modulo, array_shift($filas));

        if (count(array_filter($columnas, fn (?int $indice): bool => $indice !== null)) === 0) {
            return $this->resultado(errores: [
                ['fila' => 1, 'mensaje' => 'No se reconocieron las columnas. Descarga la plantilla para ver el formato.'],
            ]);
        }

        $filas = array_filter(
            $filas,
            fn (array $fila): bool => ! $this->filaVacia($fila)
        );

        if (count($filas) > self::MAX_FILAS) {
            return $this->resultado(errores: [
                ['fila' => 0, 'mensaje' => 'El archivo supera el máximo de '.self::MAX_FILAS.' filas.'],
            ]);
        }

        return match ($modulo) {
            'proveedores' => $this->importarProveedores($filas, $columnas, $empresaId),
            'materiales' => $this->importarMateriales($filas, $columnas, $empresaId),
            'equipos' => $this->importarEquipos($filas, $columnas, $empresaId),
            'cargos_mo' => $this->importarCargos($filas, $columnas, $empresaId),
        };
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function leerArchivo(string $ruta): array
    {
        if ($this->esCsv($ruta)) {
            return $this->leerCsv($ruta);
        }

        $lector = IOFactory::createReaderForFile($ruta);
        $lector->setReadDataOnly(true);

        return $lector->load($ruta)->getActiveSheet()->toArray(null, true, false, false);
    }

    /**
     * Los CSV se leen a mano para no perder los separadores de miles:
     * PhpSpreadsheet convertiría "120.000" en el número 120.
     *
     * @return array<int, array<int, string|null>>
     */
    private function leerCsv(string $ruta): array
    {
        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            return [];
        }

        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido) ?? $contenido;

        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        $primeraLinea = strtok($contenido, "\r\n") ?: '';
        $delimitador = $this->detectarDelimitador($primeraLinea);

        $manejador = fopen('php://temp', 'r+');
        fwrite($manejador, $contenido);
        rewind($manejador);

        $filas = [];

        while (($fila = fgetcsv($manejador, 0, $delimitador, '"', '')) !== false) {
            $filas[] = $fila;
        }

        fclose($manejador);

        return $filas;
    }

    private function detectarDelimitador(string $linea): string
    {
        $ocurrencias = [
            ',' => substr_count($linea, ','),
            ';' => substr_count($linea, ';'),
            "\t" => substr_count($linea, "\t"),
            '|' => substr_count($linea, '|'),
        ];

        arsort($ocurrencias);
        $mejor = array_key_first($ocurrencias);

        return ($ocurrencias[$mejor] ?? 0) > 0 ? (string) $mejor : ',';
    }

    private function esCsv(string $ruta): bool
    {
        if (in_array(strtolower(pathinfo($ruta, PATHINFO_EXTENSION)), ['xlsx', 'xls'], true)) {
            return false;
        }

        $inicio = (string) file_get_contents($ruta, false, null, 0, 4);

        return ! str_starts_with($inicio, 'PK')
            && ! str_starts_with($inicio, "\xD0\xCF\x11\xE0");
    }

    /**
     * @param  array<int, mixed>  $fila
     * @return array<string, int>
     */
    private function mapearEncabezados(string $modulo, array $fila): array
    {
        $alias = $this->alias($modulo);
        $mapa = [];

        foreach ($fila as $indice => $valor) {
            $clave = $this->normalizar($valor);

            if ($clave === '') {
                continue;
            }

            foreach ($alias as $campo => $nombres) {
                if (isset($mapa[$campo])) {
                    continue;
                }

                foreach ($nombres as $nombre) {
                    if ($this->normalizar($nombre) === $clave) {
                        $mapa[$campo] = $indice;
                        break 2;
                    }
                }
            }
        }

        foreach ($alias as $campo => $nombres) {
            $mapa[$campo] ??= null;
        }

        return $mapa;
    }

    // ---------------------------------------------------------------------
    // Proveedores
    // ---------------------------------------------------------------------

    private function importarProveedores(array $filas, array $columnas, int $empresaId): array
    {
        if (! isset($columnas['nombre'])) {
            return $this->faltaColumna('NOMBRE');
        }

        $creados = 0;
        $actualizados = 0;
        $errores = [];

        $existentes = Proveedor::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->get()
            ->keyBy(fn (Proveedor $proveedor): string => $this->normalizar($proveedor->nombre));

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;

            $nombre = $this->texto($fila[$columnas['nombre']] ?? null);

            if ($nombre === null) {
                $errores[] = $this->error($numeroFila, 'El nombre es obligatorio.');

                continue;
            }

            if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 120) {
                $errores[] = $this->error($numeroFila, 'El nombre debe tener entre 3 y 120 caracteres.');

                continue;
            }

            $email = $this->texto($fila[$columnas['email']] ?? null);

            if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = $this->error($numeroFila, 'El correo no es válido: '.$email);

                continue;
            }

            $datos = [
                'empresa_id' => $empresaId,
                'nombre' => $nombre,
                'contacto' => $this->texto($fila[$columnas['contacto']] ?? null),
                'telefono' => $this->texto($fila[$columnas['telefono']] ?? null),
                'email' => $email,
                'notas' => $this->texto($fila[$columnas['notas']] ?? null),
            ];

            $llave = $this->normalizar($nombre);

            try {
                if ($existente = $existentes->get($llave)) {
                    $existente->update($this->datosParaActualizar($datos, ['nombre']));
                    $actualizados++;
                } else {
                    $existentes->put($llave, Proveedor::withoutGlobalScopes()->create($datos));
                    $creados++;
                }
            } catch (\Throwable $e) {
                $errores[] = $this->error($numeroFila, 'No se pudo guardar: '.$e->getMessage());
            }
        }

        return $this->resultado($creados, $actualizados, 0, $this->limitarErrores($errores));
    }

    // ---------------------------------------------------------------------
    // Materiales
    // ---------------------------------------------------------------------

    private function importarMateriales(array $filas, array $columnas, int $empresaId): array
    {
        foreach (['codigo', 'descripcion', 'precio'] as $requerida) {
            if (! isset($columnas[$requerida])) {
                return $this->faltaColumna(mb_strtoupper($requerida));
            }
        }

        $creados = 0;
        $actualizados = 0;
        $errores = [];

        $unidades = config('obras.unidades');

        $proveedores = Proveedor::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->get(['id', 'nombre'])
            ->keyBy(fn (Proveedor $proveedor): string => $this->normalizar($proveedor->nombre));

        $existentes = Material::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->get()
            ->keyBy(fn (Material $material): string => mb_strtoupper(trim($material->codigo)));

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;

            $codigo = $this->codigo($fila[$columnas['codigo']] ?? null);
            $descripcion = $this->texto($fila[$columnas['descripcion']] ?? null);
            $precio = $this->numero($fila[$columnas['precio']] ?? null);

            if ($codigo === null) {
                $errores[] = $this->error($numeroFila, 'El código es obligatorio.');

                continue;
            }

            if ($descripcion === null) {
                $errores[] = $this->error($numeroFila, 'La descripción es obligatoria.');

                continue;
            }

            if ($precio === null || $precio < 0) {
                $errores[] = $this->error($numeroFila, 'El precio debe ser un número mayor o igual a cero.');

                continue;
            }

            $unidad = null;

            if ($columnas['unidad'] !== null) {
                $unidadTexto = $this->texto($fila[$columnas['unidad']] ?? null);
                $unidad = $unidadTexto === null ? 'UND' : $this->normalizarUnidad($unidadTexto);

                if ($unidad === null || ! array_key_exists($unidad, $unidades)) {
                    $errores[] = $this->error($numeroFila, 'La unidad "'.($unidadTexto ?? '').'" no es válida. Usa: '.implode(', ', array_keys($unidades)).'.');

                    continue;
                }
            }

            $proveedorNombre = $this->texto($fila[$columnas['proveedor']] ?? null);
            $proveedorId = null;

            if ($proveedorNombre !== null) {
                $proveedor = $proveedores->get($this->normalizar($proveedorNombre));

                if ($proveedor === null) {
                    $errores[] = $this->error($numeroFila, 'Proveedor no encontrado: '.$proveedorNombre.'. Impórtalo primero.');

                    continue;
                }

                $proveedorId = $proveedor->id;
            }

            $datos = [
                'empresa_id' => $empresaId,
                'proveedor_id' => $proveedorId,
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'precio' => $precio,
            ];

            $llave = mb_strtoupper(trim($codigo));
            $existente = $existentes->get($llave);

            if ($unidad !== null) {
                $datos['unidad'] = $unidad;
            } elseif ($existente === null) {
                $datos['unidad'] = 'UND';
            }

            try {
                if ($existente) {
                    $existente->update($this->datosParaActualizar($datos, ['codigo', 'descripcion', 'precio']));
                    $actualizados++;
                } else {
                    $existentes->put($llave, Material::withoutGlobalScopes()->create($datos));
                    $creados++;
                }
            } catch (\Throwable $e) {
                $errores[] = $this->error($numeroFila, 'No se pudo guardar: '.$e->getMessage());
            }
        }

        return $this->resultado($creados, $actualizados, 0, $this->limitarErrores($errores));
    }

    // ---------------------------------------------------------------------
    // Equipos
    // ---------------------------------------------------------------------

    private function importarEquipos(array $filas, array $columnas, int $empresaId): array
    {
        foreach (['codigo', 'descripcion', 'tarifa_dia'] as $requerida) {
            if (! isset($columnas[$requerida])) {
                return $this->faltaColumna(mb_strtoupper($requerida));
            }
        }

        $creados = 0;
        $actualizados = 0;
        $errores = [];

        $proveedores = Proveedor::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->get(['id', 'nombre'])
            ->keyBy(fn (Proveedor $proveedor): string => $this->normalizar($proveedor->nombre));

        $existentes = Equipo::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->get()
            ->keyBy(fn (Equipo $equipo): string => mb_strtoupper(trim($equipo->codigo)));

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;

            $codigo = $this->codigo($fila[$columnas['codigo']] ?? null);
            $descripcion = $this->texto($fila[$columnas['descripcion']] ?? null);
            $tarifa = $this->numero($fila[$columnas['tarifa_dia']] ?? null);

            if ($codigo === null) {
                $errores[] = $this->error($numeroFila, 'El código es obligatorio.');

                continue;
            }

            if ($descripcion === null) {
                $errores[] = $this->error($numeroFila, 'La descripción es obligatoria.');

                continue;
            }

            if ($tarifa === null || $tarifa < 0) {
                $errores[] = $this->error($numeroFila, 'La tarifa por día debe ser un número mayor o igual a cero.');

                continue;
            }

            $proveedorNombre = $this->texto($fila[$columnas['proveedor']] ?? null);
            $proveedorId = null;

            if ($proveedorNombre !== null) {
                $proveedor = $proveedores->get($this->normalizar($proveedorNombre));

                if ($proveedor === null) {
                    $errores[] = $this->error($numeroFila, 'Proveedor no encontrado: '.$proveedorNombre.'. Impórtalo primero.');

                    continue;
                }

                $proveedorId = $proveedor->id;
            }

            $datos = [
                'empresa_id' => $empresaId,
                'proveedor_id' => $proveedorId,
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'tipo' => $this->texto($fila[$columnas['tipo']] ?? null),
                'tarifa_dia' => $tarifa,
            ];

            if (($columnas['unidad_alquiler'] ?? null) !== null) {
                $textoUnidad = $this->texto($fila[$columnas['unidad_alquiler']] ?? null);

                if ($textoUnidad !== null) {
                    $unidadAlquiler = $this->normalizarUnidadAlquiler($textoUnidad);

                    if ($unidadAlquiler === null) {
                        $errores[] = $this->error(
                            $numeroFila,
                            'La unidad de alquiler "'.$textoUnidad.'" no es válida. Usa: hr, dia, m2, m3, und.',
                        );

                        continue;
                    }

                    $datos['unidad_alquiler'] = $unidadAlquiler;
                }
            }

            $llave = mb_strtoupper(trim($codigo));

            try {
                if ($existente = $existentes->get($llave)) {
                    $existente->update($this->datosParaActualizar($datos, ['codigo', 'descripcion', 'tarifa_dia']));
                    $actualizados++;
                } else {
                    $existentes->put($llave, Equipo::withoutGlobalScopes()->create($datos));
                    $creados++;
                }
            } catch (\Throwable $e) {
                $errores[] = $this->error($numeroFila, 'No se pudo guardar: '.$e->getMessage());
            }
        }

        return $this->resultado($creados, $actualizados, 0, $this->limitarErrores($errores));
    }

    // ---------------------------------------------------------------------
    // Mano de obra
    // ---------------------------------------------------------------------

    private function importarCargos(array $filas, array $columnas, int $empresaId): array
    {
        foreach (['nombre', 'jornal'] as $requerida) {
            if (! isset($columnas[$requerida])) {
                return $this->faltaColumna(mb_strtoupper($requerida));
            }
        }

        $creados = 0;
        $actualizados = 0;
        $errores = [];

        $existentes = CargoMo::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->get()
            ->keyBy(fn (CargoMo $cargo): string => $this->normalizar($cargo->nombre));

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;

            $nombre = $this->texto($fila[$columnas['nombre']] ?? null);
            $jornal = $this->numero($fila[$columnas['jornal']] ?? null);

            if ($nombre === null) {
                $errores[] = $this->error($numeroFila, 'El cargo es obligatorio.');

                continue;
            }

            if ($jornal === null || $jornal < 0) {
                $errores[] = $this->error($numeroFila, 'El jornal debe ser un número mayor o igual a cero.');

                continue;
            }

            $datos = [
                'empresa_id' => $empresaId,
                'nombre' => $nombre,
                'jornal' => $jornal,
                'descripcion' => $this->texto($fila[$columnas['descripcion']] ?? null),
            ];

            $llave = $this->normalizar($nombre);

            try {
                if ($existente = $existentes->get($llave)) {
                    $existente->update($this->datosParaActualizar($datos, ['nombre', 'jornal']));
                    $actualizados++;
                } else {
                    $existentes->put($llave, CargoMo::withoutGlobalScopes()->create($datos));
                    $creados++;
                }
            } catch (\Throwable $e) {
                $errores[] = $this->error($numeroFila, 'No se pudo guardar: '.$e->getMessage());
            }
        }

        return $this->resultado($creados, $actualizados, 0, $this->limitarErrores($errores));
    }

    // ---------------------------------------------------------------------
    // Utilidades
    // ---------------------------------------------------------------------

    /**
     * @return array<string, array<int, string>>
     */
    private function alias(string $modulo): array
    {
        $codigo = ['CODIGO', 'CÓDIGO', 'COD', 'CODE', 'REFERENCIA', 'ITEM'];
        $proveedor = ['PROVEEDOR', 'NOMBRE PROVEEDOR', 'PROVEEDOR NOMBRE'];

        return match ($modulo) {
            'proveedores' => [
                'nombre' => ['NOMBRE', 'PROVEEDOR', 'RAZON SOCIAL', 'RAZÓN SOCIAL', 'NOMBRE PROVEEDOR', 'EMPRESA'],
                'contacto' => ['CONTACTO', 'PERSONA CONTACTO', 'REPRESENTANTE', 'VENDEDOR'],
                'telefono' => ['TELEFONO', 'TELÉFONO', 'CELULAR', 'MOVIL', 'MÓVIL', 'TEL', 'WHATSAPP'],
                'email' => ['EMAIL', 'CORREO', 'CORREO ELECTRONICO', 'CORREO ELECTRÓNICO', 'E MAIL', 'MAIL'],
                'notas' => ['NOTAS', 'OBSERVACIONES', 'DIRECCION', 'DIRECCIÓN', 'DIRECCION PROVEEDOR'],
            ],
            'materiales' => [
                'codigo' => $codigo,
                'descripcion' => ['DESCRIPCION', 'DESCRIPCIÓN', 'DETALLE', 'NOMBRE', 'INSUMO', 'MATERIAL'],
                'unidad' => ['UNIDAD', 'UND', 'U M', 'UM', 'UNID', 'UNIDAD DE MEDIDA'],
                'precio' => ['PRECIO', 'VALOR', 'PRECIO UNITARIO', 'VALOR UNITARIO', 'COSTO', 'COSTO UNITARIO'],
                'proveedor' => $proveedor,
            ],
            'equipos' => [
                'codigo' => $codigo,
                'descripcion' => ['DESCRIPCION', 'DESCRIPCIÓN', 'NOMBRE', 'EQUIPO', 'DETALLE', 'MAQUINA', 'MÁQUINA'],
                'tipo' => ['TIPO', 'CLASE', 'CATEGORIA', 'CATEGORÍA', 'PROPIEDAD'],
                'tarifa_dia' => ['TARIFA DIA', 'TARIFA DÍA', 'TARIFA', 'VALOR DIA', 'VALOR DÍA', 'PRECIO', 'VALOR', 'ALQUILER', 'COSTO DIA', 'COSTO'],
                'unidad_alquiler' => ['UNIDAD ALQUILER', 'UNIDAD DE ALQUILER', 'UND ALQUILER', 'UNIDAD', 'PERIODO', 'PERÍODO', 'TARIFA POR'],
                'proveedor' => $proveedor,
            ],
            'cargos_mo' => [
                'nombre' => ['CARGO', 'NOMBRE', 'MANO DE OBRA', 'TRABAJADOR', 'OFICIO'],
                'jornal' => ['JORNAL', 'SALARIO', 'VALOR DIA', 'VALOR DÍA', 'JORNAL DIA', 'JORNAL DÍA', 'COSTO DIA', 'PRECIO', 'VALOR'],
                'descripcion' => ['DESCRIPCION', 'DESCRIPCIÓN', 'DETALLE', 'NOTAS', 'OBSERVACIONES'],
            ],
        };
    }

    private function normalizar(mixed $valor): string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return '';
        }

        $texto = Str::upper(Str::ascii($texto));
        $texto = preg_replace('/[^A-Z0-9]+/', ' ', $texto) ?? '';

        return trim($texto);
    }

    private function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function codigo(mixed $valor): ?string
    {
        if (is_float($valor) && floor($valor) === $valor) {
            return number_format($valor, 0, '', '');
        }

        return $this->texto($valor);
    }

    private function numero(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $texto = preg_replace('/[^\d.,\-]/u', '', trim((string) $valor)) ?? '';

        if ($texto === '' || $texto === '-' || $texto === '.' || $texto === ',') {
            return null;
        }

        $ultimoPunto = strrpos($texto, '.');
        $ultimaComa = strrpos($texto, ',');

        if ($ultimoPunto !== false && $ultimaComa !== false) {
            if ($ultimaComa > $ultimoPunto) {
                $texto = str_replace('.', '', $texto);
                $texto = str_replace(',', '.', $texto);
            } else {
                $texto = str_replace(',', '', $texto);
            }
        } elseif ($ultimaComa !== false) {
            $texto = preg_match('/^\d{1,3}(,\d{3})+$/', $texto)
                ? str_replace(',', '', $texto)
                : str_replace(',', '.', $texto);
        } elseif ($ultimoPunto !== false) {
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $texto)) {
                $texto = str_replace('.', '', $texto);
            }
        }

        return is_numeric($texto) ? (float) $texto : null;
    }

    private function normalizarUnidad(string $unidad): ?string
    {
        $mapa = [
            'UND' => 'UND', 'UN' => 'UND', 'UNID' => 'UND', 'UNIDAD' => 'UND', 'UNDS' => 'UND',
            'UNIDADES' => 'UND', 'U' => 'UND', 'UNIT' => 'UND',
            'M2' => 'M2', 'METRO CUADRADO' => 'M2', 'METROS CUADRADOS' => 'M2', 'MTS2' => 'M2',
            'M3' => 'M3', 'METRO CUBICO' => 'M3', 'METROS CUBICOS' => 'M3', 'MTS3' => 'M3',
            'ML' => 'ML', 'METRO LINEAL' => 'ML', 'METROS LINEALES' => 'ML', 'MTS' => 'ML',
            'KG' => 'KG', 'KILO' => 'KG', 'KILOS' => 'KG', 'KILOGRAMO' => 'KG', 'KILOGRAMOS' => 'KG',
            'GAL' => 'GAL', 'GALON' => 'GAL', 'GALONES' => 'GAL',
            'PLIEGO' => 'PLIEGO', 'PLIEGOS' => 'PLIEGO',
            'GL' => 'GL', 'GLOBAL' => 'GL',
            'BL' => 'BL', 'BULTO' => 'BL', 'BULTOS' => 'BL',
            'DIA' => 'DIA', 'DIAS' => 'DIA',
            'HORA' => 'HORA', 'HORAS' => 'HORA', 'HR' => 'HORA', 'H' => 'HORA',
            'MES' => 'MES', 'MESES' => 'MES',
        ];

        return $mapa[$this->normalizar($unidad)] ?? null;
    }

    private function normalizarUnidadAlquiler(string $unidad): ?string
    {
        $mapa = [
            'HR' => 'hr', 'HORA' => 'hr', 'HORAS' => 'hr', 'H' => 'hr',
            'DIA' => 'dia', 'DIAS' => 'dia', 'D' => 'dia', 'JORNAL' => 'dia',
            'M2' => 'm2', 'METRO CUADRADO' => 'm2', 'METROS CUADRADOS' => 'm2', 'MTS2' => 'm2',
            'M3' => 'm3', 'METRO CUBICO' => 'm3', 'METROS CUBICOS' => 'm3', 'MTS3' => 'm3',
            'UND' => 'und', 'UN' => 'und', 'UNID' => 'und', 'UNIDAD' => 'und',
            'UNIDADES' => 'und', 'GL' => 'und', 'GLOBAL' => 'und',
        ];

        return $mapa[$this->normalizar($unidad)] ?? null;
    }

    private function filaVacia(array $fila): bool
    {
        foreach ($fila as $valor) {
            if ($this->texto($valor) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * En una actualización, las celdas vacías no borran datos existentes:
     * solo se reemplazan los campos obligatorios y los que traen valor.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, string>  $obligatorios
     * @return array<string, mixed>
     */
    private function datosParaActualizar(array $datos, array $obligatorios): array
    {
        return array_filter(
            $datos,
            fn (mixed $valor, string $campo): bool => $valor !== null || in_array($campo, $obligatorios, true),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    private function faltaColumna(string $columna): array
    {
        return $this->resultado(errores: [
            ['fila' => 1, 'mensaje' => 'Falta la columna obligatoria: '.$columna.'.'],
        ]);
    }

    private function error(int $fila, string $mensaje): array
    {
        return ['fila' => $fila, 'mensaje' => $mensaje];
    }

    /**
     * @param  array<int, array{fila:int, mensaje:string}>  $errores
     * @return array<int, array{fila:int, mensaje:string}>
     */
    private function limitarErrores(array $errores): array
    {
        if (count($errores) <= self::MAX_ERRORES) {
            return $errores;
        }

        $restantes = count($errores) - self::MAX_ERRORES;
        $errores = array_slice($errores, 0, self::MAX_ERRORES);
        $errores[] = $this->error(0, 'Y '.$restantes.' error(es) más.');

        return $errores;
    }

    private function resultado(int $creados = 0, int $actualizados = 0, int $omitidos = 0, array $errores = []): array
    {
        return [
            'creados' => $creados,
            'actualizados' => $actualizados,
            'omitidos' => $omitidos,
            'errores' => $errores,
        ];
    }
}
