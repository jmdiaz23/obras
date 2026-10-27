<?php

namespace App\Services;

use App\Models\Apu;
use App\Models\Equipo;
use App\Models\Material;
use App\Models\Proyecto;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Réplica del libro "CUARTA ENTREGA" del usuario:
 *
 *   1. LISTA DE PRECIOS        - materiales del catálogo de la empresa
 *   2. ALQUILER DE EQUIPOS     - equipos del catálogo
 *   3. RENDIMIENTO             - cuadrilla y rendimiento de los APUs del proyecto
 *   4. APU XX...               - una hoja por APU (secciones A/B/C + costo directo)
 *   5. MEMORIA DE CALCULO      - ítems del proyecto con cantidades y vr. unitario
 *   6. PRESUPUESTO GENERAL     - ítems + resumen con AIU (Adm/Imp/Utilidad/Total) y avance
 *   7. AVANCE DE OBRA          - valor ganado y avance por etapa/capítulo
 *   8. TABLA DE TAREAS         - programación (se llena en el cronograma)
 *   9. EJE UNIVERSAL           - referencia de ejes (manual)
 */
class ExcelExportService
{
    private const MONEDA2 = '"$"#,##0.00';

    private const MONEDA4 = '"$"#,##0.0000';

    private const MONEDA6 = '"$"#,##0.000000';

    public function exportar(Proyecto $proyecto): string
    {
        $proyecto->load('empresa');

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $this->hojaListaDePrecios($spreadsheet, $proyecto);
        $this->hojaAlquilerDeEquipos($spreadsheet, $proyecto);
        $this->hojaRendimiento($spreadsheet, $proyecto);
        $this->hojasApu($spreadsheet, $proyecto);
        $this->hojaMemoriaDeCalculo($spreadsheet, $proyecto);
        $this->hojaPresupuestoGeneral($spreadsheet, $proyecto);
        $this->hojaAvanceDeObra($spreadsheet, $proyecto);
        $this->hojaTablaDeTareas($spreadsheet, $proyecto);
        $this->hojaEjeUniversal($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');

        return ob_get_clean();
    }

    private function hojaListaDePrecios(Spreadsheet $spreadsheet, Proyecto $proyecto): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('LISTA DE PRECIOS');

        $this->tituloHoja($sheet, 'LISTA DE PRECIOS', 6);

        $this->encabezados($sheet, 2, ['ITEM', 'CODIGO', 'DESCRIPCION', 'UNIDAD', 'PROVEEDOR', 'PRECIO UNITARIO']);

        $materiales = Material::query()->with('proveedor')->orderBy('codigo')->get();

        $fila = 3;
        foreach ($materiales as $i => $m) {
            $sheet->setCellValue("A{$fila}", $i + 1);
            $sheet->setCellValue("B{$fila}", $m->codigo);
            $sheet->setCellValue("C{$fila}", $m->descripcion);
            $sheet->setCellValue("D{$fila}", $m->unidad);
            $sheet->setCellValue("E{$fila}", $m->proveedor?->nombre ?? '-');
            $sheet->setCellValue("F{$fila}", $m->precio);
            $this->estiloFila($sheet, $fila, 'A2:F2');
            $fila++;
        }

        $this->bordes($sheet, "A2:F{$fila}");
        $this->anchuras($sheet, ['A' => 7, 'B' => 14, 'C' => 52, 'D' => 9, 'E' => 26, 'F' => 15]);
        $this->money($sheet, "F3:F{$fila}", self::MONEDA2);
        $sheet->getStyle("A3:A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function hojaAlquilerDeEquipos(Spreadsheet $spreadsheet, Proyecto $proyecto): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('ALQUILER DE EQUIPOS');

        $this->tituloHoja($sheet, 'ALQUILER DE EQUIPOS', 7);

        $this->encabezados($sheet, 2, ['ITEM', 'CODIGO', 'DESCRIPCION', 'TIPO', 'PROVEEDOR', 'TARIFA DIA', 'UNIDAD ALQUILER']);

        $equipos = Equipo::query()->with('proveedor')->orderBy('codigo')->get();

        $fila = 3;
        foreach ($equipos as $i => $e) {
            $sheet->setCellValue("A{$fila}", $i + 1);
            $sheet->setCellValue("B{$fila}", $e->codigo);
            $sheet->setCellValue("C{$fila}", $e->descripcion);
            $sheet->setCellValue("D{$fila}", $e->tipo);
            $sheet->setCellValue("E{$fila}", $e->proveedor?->nombre ?? '-');
            $sheet->setCellValue("F{$fila}", $e->tarifa_dia);
            $sheet->setCellValue("G{$fila}", $e->unidad_alquiler);
            $this->estiloFila($sheet, $fila, 'A2:G2');
            $fila++;
        }

        $this->bordes($sheet, "A2:G{$fila}");
        $this->anchuras($sheet, ['A' => 7, 'B' => 14, 'C' => 52, 'D' => 22, 'E' => 26, 'F' => 15, 'G' => 18]);
        $this->money($sheet, "F3:F{$fila}", self::MONEDA2);
        $sheet->getStyle("A3:A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function hojaRendimiento(Spreadsheet $spreadsheet, Proyecto $proyecto): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('RENDIMIENTO');

        $this->tituloHoja($sheet, 'RENDIMIENTO (cuadrillas y días por unidad)', 6);

        $this->encabezados($sheet, 2, ['ITEM', 'ACTIVIDAD', 'PERSONAL', 'UND', 'RENDIMIENTO/UND', 'DIAS/UND']);

        $fila = 3;
        foreach ($this->apusDelProyecto($proyecto) as $i => $apu) {
            $dias = $apu->dias_por_unidad;
            $rend = $dias ? 1 / $dias : null;

            $sheet->setCellValue("A{$fila}", $i + 1);
            $sheet->setCellValue("B{$fila}", $apu->descripcion);
            $sheet->setCellValue("C{$fila}", $apu->cuadrilla);
            $sheet->setCellValue("D{$fila}", $apu->unidad);
            $sheet->setCellValue("E{$fila}", $rend);
            $sheet->setCellValue("F{$fila}", $dias);
            $this->estiloFila($sheet, $fila, 'A2:F2');
            $fila++;
        }

        $this->bordes($sheet, "A2:F{$fila}");
        $this->anchuras($sheet, ['A' => 7, 'B' => 52, 'C' => 26, 'D' => 9, 'E' => 16, 'F' => 16]);
        $sheet->getStyle("E3:E{$fila}")->getNumberFormat()->setFormatCode('#,##0.0000');
        $sheet->getStyle("F3:F{$fila}")->getNumberFormat()->setFormatCode('#,##0.0000');
        $sheet->getStyle("A3:A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function hojasApu(Spreadsheet $spreadsheet, Proyecto $proyecto): void
    {
        $usados = [];

        foreach ($this->apusDelProyecto($proyecto) as $apu) {
            $hoja = $spreadsheet->createSheet();
            $hoja->setTitle($this->nombreHoja($apu, $usados));
            $usados[$hoja->getTitle()] = true;

            $this->hojaApu($hoja, $apu);
        }
    }

    private function hojaApu(Worksheet $sheet, Apu $apu): void
    {
        $calculo = ApuCalculator::calcular($apu->loadMissing('empresa'));
        $pctPrest = $apu->empresa->pct_prestaciones;

        $this->tituloHoja($sheet, 'ANALISIS DE PRECIOS UNITARIOS - A.P.U.', 7);

        $sheet->setCellValue('A2', 'ACTIVIDAD: '.$apu->descripcion);
        $sheet->getStyle('A2:G2')->getFont()->setBold(true);
        $sheet->setCellValue('A3', 'UNIDAD: '.$apu->unidad);
        $sheet->setCellValue('C3', 'CUADRILLA: '.($apu->cuadrilla ?: '-'));

        $fila = 5;
        $fila = $this->seccionMateriales($sheet, $calculo, $fila);
        $fila = $this->seccionManoObra($sheet, $calculo, $fila, $pctPrest);
        $fila = $this->seccionEquipos($sheet, $calculo, $fila, $apu->pct_herramientas);

        $this->totalesApu($sheet, $calculo, $fila);
    }

    private function seccionMateriales(Worksheet $sheet, array $calculo, int $fila): int
    {
        $sheet->setCellValue("A{$fila}", 'A. MATERIALES');
        $this->estiloSeccion($sheet, "A{$fila}:G{$fila}");
        $fila++;

        $this->encabezados($sheet, $fila, ['#', 'DESCRIPCION', 'UNIDAD', 'CANTIDAD', '%DESP', 'V. UNITARIO', 'V. TOTAL']);

        $inicio = $fila + 1;
        foreach ($calculo['materiales'] as $i => $m) {
            $fila++;
            $sheet->setCellValue("A{$fila}", $i + 1);
            $sheet->setCellValue("B{$fila}", $m['descripcion']);
            $sheet->setCellValue("C{$fila}", $m['unidad']);
            $sheet->setCellValue("D{$fila}", $m['cantidad']);
            $sheet->setCellValue("E{$fila}", $m['desperdicio']);
            $sheet->setCellValue("F{$fila}", $m['precio']);
            $sheet->setCellValue("G{$fila}", $m['total']);
            $this->estiloFila($sheet, $fila, "A{$fila}:G{$fila}");
        }

        if ($inicio <= $fila) {
            $this->money($sheet, "F{$inicio}:F{$fila}", self::MONEDA4);
            $this->money($sheet, "G{$inicio}:G{$fila}", self::MONEDA4);
        }

        $fila++;
        $sheet->setCellValue("A{$fila}", 'SUBTOTAL A');
        $sheet->setCellValue("G{$fila}", $calculo['totales']['materiales']);
        $this->estiloSubtotal($sheet, "A{$fila}:G{$fila}");
        $this->money($sheet, "G{$fila}", self::MONEDA4);

        return $fila + 1;
    }

    private function seccionManoObra(Worksheet $sheet, array $calculo, int $fila, float $pctPrest): int
    {
        $sheet->setCellValue("A{$fila}", 'B. MANO DE OBRA');
        $this->estiloSeccion($sheet, "A{$fila}:G{$fila}");
        $fila++;

        $this->encabezados($sheet, $fila, ['#', 'DESCRIPCION', 'JORNAL', '%PREST', 'JORNAL C/PREST', 'DIAS/UND', 'V. TOTAL']);

        $inicio = $fila + 1;
        foreach ($calculo['manoObra'] as $i => $m) {
            $fila++;
            $sheet->setCellValue("A{$fila}", $i + 1);
            $sheet->setCellValue("B{$fila}", $m['descripcion']);
            $sheet->setCellValue("C{$fila}", $m['jornal']);
            $sheet->setCellValue("D{$fila}", $pctPrest);
            $sheet->setCellValue("E{$fila}", $m['jornal_prestaciones']);
            $sheet->setCellValue("F{$fila}", $m['dias_por_unidad']);
            $sheet->setCellValue("G{$fila}", $m['total']);
            $this->estiloFila($sheet, $fila, "A{$fila}:G{$fila}");
        }

        if ($inicio <= $fila) {
            $this->money($sheet, "C{$inicio}:C{$fila}", self::MONEDA2);
            $this->money($sheet, "E{$inicio}:E{$fila}", self::MONEDA4);
            $this->money($sheet, "G{$inicio}:G{$fila}", self::MONEDA4);
        }

        $fila++;
        $sheet->setCellValue("A{$fila}", 'SUBTOTAL B');
        $sheet->setCellValue("G{$fila}", $calculo['totales']['mano_obra']);
        $this->estiloSubtotal($sheet, "A{$fila}:G{$fila}");
        $this->money($sheet, "G{$fila}", self::MONEDA4);

        return $fila + 1;
    }

    private function seccionEquipos(Worksheet $sheet, array $calculo, int $fila, float $pctHerramientas): int
    {
        $sheet->setCellValue("A{$fila}", 'C. EQUIPOS');
        $this->estiloSeccion($sheet, "A{$fila}:G{$fila}");
        $fila++;

        $this->encabezados($sheet, $fila, ['#', 'DESCRIPCION', 'UNIDAD', 'CANTIDAD', 'V. UNITARIO', 'V. TOTAL', '']);

        $inicio = $fila + 1;
        foreach ($calculo['equipos'] as $i => $e) {
            $fila++;
            $sheet->setCellValue("A{$fila}", $i + 1);
            $sheet->setCellValue("B{$fila}", $e['descripcion']);
            $sheet->setCellValue("C{$fila}", $e['unidad']);
            $sheet->setCellValue("D{$fila}", $e['cantidad']);
            $sheet->setCellValue("E{$fila}", $e['valor_unitario']);
            $sheet->setCellValue("F{$fila}", $e['total']);
            $this->estiloFila($sheet, $fila, "A{$fila}:G{$fila}");
        }

        if ($inicio <= $fila) {
            $this->money($sheet, "E{$inicio}:F{$fila}", self::MONEDA4);
        }

        $fila++;
        $sheet->setCellValue("A{$fila}", 'SUBTOTAL C - EQUIPOS');
        $sheet->setCellValue("F{$fila}", $calculo['totales']['equipos'] - $calculo['totales']['herramientas']);
        $this->estiloSubtotal($sheet, "A{$fila}:G{$fila}");
        $this->money($sheet, "F{$fila}", self::MONEDA4);

        $fila++;
        $sheet->setCellValue("A{$fila}", 'HERRAMIENTAS ('.$pctHerramientas.'% de mano de obra)');
        $sheet->setCellValue("F{$fila}", $calculo['totales']['herramientas']);
        $this->estiloSubtotal($sheet, "A{$fila}:G{$fila}");
        $this->money($sheet, "F{$fila}", self::MONEDA4);

        $fila++;
        $sheet->setCellValue("A{$fila}", 'SUBTOTAL C - INCL. HERRAMIENTAS');
        $sheet->setCellValue("F{$fila}", $calculo['totales']['equipos']);
        $this->estiloSubtotal($sheet, "A{$fila}:G{$fila}");
        $this->money($sheet, "F{$fila}", self::MONEDA4);

        return $fila + 1;
    }

    private function totalesApu(Worksheet $sheet, array $calculo, int $fila): void
    {
        $sheet->setCellValue("A{$fila}", 'COSTO DIRECTO = A + B + C');
        $sheet->setCellValue("G{$fila}", $calculo['totales']['costo_directo']);
        $style = $this->estiloSubtotal($sheet, "A{$fila}:G{$fila}");
        $style->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('C6EFCE');
        $this->money($sheet, "G{$fila}", self::MONEDA4);

        $this->bordes($sheet, "A5:G{$fila}");
        $this->anchuras($sheet, ['A' => 6, 'B' => 42, 'C' => 12, 'D' => 12, 'E' => 16, 'F' => 12, 'G' => 16]);
    }

    private function hojaMemoriaDeCalculo(Spreadsheet $spreadsheet, Proyecto $proyecto): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('MEMORIA DE CALCULO');

        $this->tituloHoja($sheet, 'MEMORIA DE CALCULO', 7);

        $this->encabezados($sheet, 2, ['ITEM', 'DESCRIPCION', 'UND', 'CANTIDAD', 'VR. UNITARIO', 'VR. TOTAL', 'DURACION (DIAS)']);

        $calculo = PresupuestoService::calcular($proyecto);

        $fila = 3;
        foreach ($calculo['lineas'] as $linea) {
            if ($linea['es_capitulo']) {
                $sheet->setCellValue("A{$fila}", $linea['item']);
                $sheet->setCellValue("B{$fila}", $linea['descripcion']);
                $sheet->setCellValue("F{$fila}", $linea['subtotal_capitulo'] ?? 0);
                $this->estiloSeccion($sheet, "A{$fila}:G{$fila}");
                $fila++;

                continue;
            }

            $sheet->setCellValue("A{$fila}", $linea['item']);
            $sheet->setCellValue("B{$fila}", $linea['descripcion']);
            $sheet->setCellValue("C{$fila}", $linea['unidad']);
            $sheet->setCellValue("D{$fila}", $linea['cantidad']);
            $sheet->setCellValue("E{$fila}", $linea['vr_unitario']);
            $sheet->setCellValue("F{$fila}", $linea['vr_total']);
            $sheet->setCellValue("G{$fila}", $linea['duracion']);
            $this->estiloFila($sheet, $fila, 'A2:G2');
            $fila++;
        }

        $this->bordes($sheet, "A2:G{$fila}");
        $this->anchuras($sheet, ['A' => 10, 'B' => 52, 'C' => 9, 'D' => 13, 'E' => 15, 'F' => 15, 'G' => 15]);
        $this->money($sheet, "E3:E{$fila}", self::MONEDA4);
        $this->money($sheet, "F3:F{$fila}", self::MONEDA2);
        $sheet->getStyle("D3:D{$fila}")->getNumberFormat()->setFormatCode('#,##0.0000');
        $sheet->getStyle("G3:G{$fila}")->getNumberFormat()->setFormatCode('#,##0.0000');
    }

    private function hojaPresupuestoGeneral(Spreadsheet $spreadsheet, Proyecto $proyecto): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('PRESUPUESTO GENERAL');

        $this->tituloHoja($sheet, 'PRESUPUESTO GENERAL', 10);

        $empresa = $proyecto->empresa;

        $sheet->setCellValue('A2', ($empresa?->nombre ?: 'EMPRESA').'  ·  NIT '.($empresa?->nit ?: '-'));
        $sheet->setCellValue('A3', 'PROYECTO: '.strtoupper($proyecto->nombre));
        $sheet->setCellValue('A4', 'CLIENTE: '.($proyecto->cliente ?: '-').'  ·  UBICACION: '.($proyecto->ubicacion ?: '-'));

        $this->encabezados($sheet, 6, ['ITEM', 'DESCRIPCION', 'UND', 'CANTIDAD', 'VR. UNITARIO', 'VR. TOTAL', 'DURACION (DIAS)', 'EJECUTADO', 'VR. EJECUTADO', 'AVANCE %']);

        $calculo = PresupuestoService::calcular($proyecto);

        $fila = 7;
        $capituloPendiente = null;

        foreach ($calculo['lineas'] as $linea) {
            if ($linea['es_capitulo']) {
                if ($capituloPendiente !== null) {
                    $fila = $this->filaSubtotalCapitulo($sheet, $fila, $capituloPendiente);
                }

                $capituloPendiente = [
                    'item' => $linea['item'],
                    'subtotal' => $linea['subtotal_capitulo'] ?? 0,
                    'ejecutado' => $linea['vr_ejecutado'] ?? 0,
                    'avance' => $linea['avance'] ?? 0,
                ];

                $sheet->mergeCells("B{$fila}:H{$fila}");
                $sheet->setCellValue("A{$fila}", $linea['item']);
                $sheet->setCellValue("B{$fila}", $linea['descripcion']);
                $sheet->setCellValue("I{$fila}", $linea['vr_ejecutado'] ?? 0);
                $sheet->setCellValue("J{$fila}", $linea['avance'] ?? 0);
                $this->estiloSeccion($sheet, "A{$fila}:J{$fila}");
                $fila++;

                continue;
            }

            $sheet->setCellValue("A{$fila}", $linea['item']);
            $sheet->setCellValue("B{$fila}", $linea['descripcion']);
            $sheet->setCellValue("C{$fila}", $linea['unidad']);
            $sheet->setCellValue("D{$fila}", $linea['cantidad']);
            $sheet->setCellValue("E{$fila}", $linea['vr_unitario']);
            $sheet->setCellValue("F{$fila}", $linea['vr_total']);
            $sheet->setCellValue("G{$fila}", $linea['duracion']);
            $sheet->setCellValue("H{$fila}", $linea['cantidad_ejecutada'] ?? 0);
            $sheet->setCellValue("I{$fila}", $linea['vr_ejecutado'] ?? 0);
            $sheet->setCellValue("J{$fila}", $linea['avance'] ?? 0);
            $this->estiloFila($sheet, $fila, 'A6:J6');
            $fila++;
        }

        if ($capituloPendiente !== null) {
            $fila = $this->filaSubtotalCapitulo($sheet, $fila, $capituloPendiente);
        }

        $this->bordes($sheet, "A6:J{$fila}");

        $fila++;
        $sheet->setCellValue("A{$fila}", 'SUBTOTAL (costo directo)');
        $sheet->setCellValue("F{$fila}", $calculo['resumen']['subtotal']);
        $sheet->setCellValue("I{$fila}", $calculo['resumen']['ejecutado']);
        $sheet->setCellValue("J{$fila}", $calculo['resumen']['avance_financiero']);
        $this->estiloResumen($sheet, "A{$fila}:J{$fila}");
        $fila++;

        $sheet->setCellValue("A{$fila}", 'ADMINISTRACION '.$proyecto->pct_administracion.'%');
        $sheet->setCellValue("F{$fila}", $calculo['resumen']['administracion']);
        $this->estiloFila($sheet, $fila, 'A6:J6');
        $fila++;

        $sheet->setCellValue("A{$fila}", 'IMPREVISTOS '.$proyecto->pct_imprevistos.'%');
        $sheet->setCellValue("F{$fila}", $calculo['resumen']['imprevistos']);
        $this->estiloFila($sheet, $fila, 'A6:J6');
        $fila++;

        $sheet->setCellValue("A{$fila}", 'UTILIDAD '.$proyecto->pct_utilidad.'%');
        $sheet->setCellValue("F{$fila}", $calculo['resumen']['utilidad']);
        $this->estiloFila($sheet, $fila, 'A6:J6');
        $fila++;

        if ($proyecto->pct_iva_utilidad > 0) {
            $sheet->setCellValue("A{$fila}", 'IVA SOBRE UTILIDAD '.$proyecto->pct_iva_utilidad.'%');
            $sheet->setCellValue("F{$fila}", $calculo['resumen']['iva_utilidad']);
            $this->estiloFila($sheet, $fila, 'A6:J6');
            $fila++;
        }

        $sheet->setCellValue("A{$fila}", 'TOTAL VENTA');
        $sheet->setCellValue("F{$fila}", $calculo['resumen']['total']);
        $this->estiloResumen($sheet, "A{$fila}:J{$fila}");

        $this->anchuras($sheet, ['A' => 10, 'B' => 48, 'C' => 9, 'D' => 13, 'E' => 16, 'F' => 16, 'G' => 15, 'H' => 13, 'I' => 17, 'J' => 11]);
        $this->money($sheet, "E7:E{$fila}", self::MONEDA4);
        $this->money($sheet, "F7:F{$fila}", self::MONEDA2);
        $this->money($sheet, "I7:I{$fila}", self::MONEDA2);
        $sheet->getStyle("D7:D{$fila}")->getNumberFormat()->setFormatCode('#,##0.0000');
        $sheet->getStyle("G7:G{$fila}")->getNumberFormat()->setFormatCode('#,##0.0000');
        $sheet->getStyle("H7:H{$fila}")->getNumberFormat()->setFormatCode('#,##0.0000');
        $sheet->getStyle("J7:J{$fila}")->getNumberFormat()->setFormatCode('0.00"%"');
    }

    private function filaSubtotalCapitulo(Worksheet $sheet, int $fila, array $capitulo): int
    {
        $sheet->mergeCells("A{$fila}:E{$fila}");
        $sheet->setCellValue("A{$fila}", 'SUBTOTAL CAPITULO '.$capitulo['item']);
        $sheet->setCellValue("F{$fila}", $capitulo['subtotal']);
        $sheet->setCellValue("I{$fila}", $capitulo['ejecutado']);
        $sheet->setCellValue("J{$fila}", $capitulo['avance']);
        $this->estiloSubtotal($sheet, "A{$fila}:J{$fila}");
        $this->money($sheet, "F{$fila}", self::MONEDA2);
        $this->money($sheet, "I{$fila}", self::MONEDA2);

        return $fila + 1;
    }

    private function hojaAvanceDeObra(Spreadsheet $spreadsheet, Proyecto $proyecto): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('AVANCE DE OBRA');

        $this->tituloHoja($sheet, 'AVANCE DE OBRA', 8);

        $avance = AvanceService::calcular($proyecto);
        $resumen = $avance['resumen'];

        $sheet->setCellValue('A2', 'PROYECTO: '.strtoupper($proyecto->nombre));
        $sheet->setCellValue('A3', 'CLIENTE: '.($proyecto->cliente ?: '-').'  ·  UBICACION: '.($proyecto->ubicacion ?: '-'));

        $sheet->setCellValue('A5', 'AVANCE FINANCIERO');
        $sheet->setCellValue('B5', $resumen['avance_financiero']);
        $sheet->getStyle('A5')->getFont()->setBold(true);
        $sheet->getStyle('B5')->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->setCellValue('D5', 'AVANCE FISICO');
        $sheet->setCellValue('E5', $resumen['avance_fisico'] === null ? 'Unidades mixtas' : $resumen['avance_fisico']);
        $sheet->getStyle('D5')->getFont()->setBold(true);
        if ($resumen['avance_fisico'] !== null) {
            $sheet->getStyle('E5')->getNumberFormat()->setFormatCode('0.00"%"');
        }
        $sheet->setCellValue('G5', 'ITEMS TERMINADOS');
        $sheet->setCellValue('H5', $resumen['items_terminados'].' de '.$resumen['items']);
        $sheet->getStyle('G5')->getFont()->setBold(true);

        $sheet->setCellValue('A6', 'VALOR PLAN (COSTO DIRECTO)');
        $sheet->setCellValue('B6', $resumen['plan']);
        $sheet->getStyle('A6')->getFont()->setBold(true);
        $sheet->getStyle('B6')->getNumberFormat()->setFormatCode(self::MONEDA2);

        $sheet->setCellValue('A7', 'VALOR EJECUTADO (VALOR GANADO)');
        $sheet->setCellValue('B7', $resumen['ejecutado']);
        $sheet->getStyle('A7')->getFont()->setBold(true);
        $sheet->getStyle('B7')->getNumberFormat()->setFormatCode(self::MONEDA2);

        $this->encabezados($sheet, 9, ['ETAPA', 'CAPITULOS', 'ITEMS', 'VALOR PLAN', 'VALOR EJECUTADO', 'AVANCE FINANCIERO', 'AVANCE FISICO', 'UNIDAD']);

        $fila = 10;
        foreach ($avance['etapas'] as $etapa) {
            $sheet->setCellValue("A{$fila}", $etapa['nombre']);
            $sheet->setCellValue("B{$fila}", $etapa['capitulos']);
            $sheet->setCellValue("C{$fila}", $etapa['items']);
            $sheet->setCellValue("D{$fila}", $etapa['plan']);
            $sheet->setCellValue("E{$fila}", $etapa['ejecutado']);
            $sheet->setCellValue("F{$fila}", $etapa['avance_financiero']);
            $sheet->setCellValue("G{$fila}", $etapa['avance_fisico'] === null ? '—' : $etapa['avance_fisico']);
            $sheet->setCellValue("H{$fila}", $etapa['unidad_fisica'] ?: 'Mixta');
            $this->estiloFila($sheet, $fila, 'A9:H9');
            $fila++;
        }

        $this->bordes($sheet, "A9:H{$fila}");
        $this->money($sheet, "D10:E{$fila}", self::MONEDA2);
        $sheet->getStyle("F10:F{$fila}")->getNumberFormat()->setFormatCode('0.00"%"');
        $sheet->getStyle("G10:G{$fila}")->getNumberFormat()->setFormatCode('0.00"%"');

        $fila += 2;
        $this->encabezados($sheet, $fila, ['ETAPA', 'CAPITULO', 'ITEM', 'VALOR PLAN', 'VALOR EJECUTADO', 'AVANCE FINANCIERO', 'AVANCE FISICO', 'UNIDAD']);
        $fila++;

        $nombresEtapa = collect($avance['etapas'])->mapWithKeys(fn (array $etapa): array => [
            ($etapa['id'] ?? 'sin') => $etapa['nombre'],
        ]);

        $inicioCapitulos = $fila;
        foreach ($avance['capitulos'] as $capitulo) {
            $nombreEtapa = $nombresEtapa->get($capitulo['etapa_id'] ?? 'sin', 'Sin etapa');
            $sheet->setCellValue("A{$fila}", $nombreEtapa);
            $sheet->setCellValue("B{$fila}", $capitulo['nombre']);
            $sheet->setCellValue("C{$fila}", $capitulo['item']);
            $sheet->setCellValue("D{$fila}", $capitulo['plan']);
            $sheet->setCellValue("E{$fila}", $capitulo['ejecutado']);
            $sheet->setCellValue("F{$fila}", $capitulo['avance_financiero']);
            $sheet->setCellValue("G{$fila}", $capitulo['avance_fisico'] === null ? '—' : $capitulo['avance_fisico']);
            $sheet->setCellValue("H{$fila}", $capitulo['unidad_fisica'] ?: 'Mixta');
            $this->estiloFila($sheet, $fila, 'A9:H9');
            $fila++;
        }

        if ($inicioCapitulos < $fila) {
            $this->bordes($sheet, "A{$inicioCapitulos}:H".($fila - 1));
            $this->money($sheet, "D{$inicioCapitulos}:E".($fila - 1), self::MONEDA2);
            $sheet->getStyle("F{$inicioCapitulos}:F".($fila - 1))->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle("G{$inicioCapitulos}:G".($fila - 1))->getNumberFormat()->setFormatCode('0.00"%"');
        }

        $this->anchuras($sheet, ['A' => 30, 'B' => 42, 'C' => 10, 'D' => 18, 'E' => 19, 'F' => 18, 'G' => 16, 'H' => 11]);
    }

    private function hojaTablaDeTareas(Spreadsheet $spreadsheet, Proyecto $proyecto): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('TABLA DE TAREAS');

        $this->tituloHoja($sheet, 'TABLA DE TAREAS / PROGRAMACION', 6);
        $this->encabezados($sheet, 2, ['EDT', 'ACTIVIDAD', 'DURACION (DIAS)', 'COMIENZO', 'FIN', 'PREDECESORAS']);

        $fila = 3;
        foreach ($this->apusDelProyecto($proyecto) as $i => $apu) {
            $sheet->setCellValue("A{$fila}", $i + 1);
            $sheet->setCellValue("B{$fila}", $apu->descripcion);
            $sheet->setCellValue("C{$fila}", $apu->dias_por_unidad);
            $this->estiloFila($sheet, $fila, 'A2:F2');
            $fila++;
        }

        $this->bordes($sheet, "A2:F{$fila}");
        $this->anchuras($sheet, ['A' => 8, 'B' => 52, 'C' => 15, 'D' => 12, 'E' => 12, 'F' => 14]);
        if ($fila > 2) {
            $sheet->getStyle("C3:C{$fila}")->getNumberFormat()->setFormatCode('#,##0.0000');
        }
        $sheet->getStyle('D2:F2')->getFont()->getColor()->setRGB('9CA3AF');
    }

    private function hojaEjeUniversal(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('EJE UNIVERSAL');

        $this->tituloHoja($sheet, 'EJE UNIVERSAL', 8);
        $this->encabezados($sheet, 2, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']);

        $sheet->getStyle('A1:H2')->getFont()->getColor()->setRGB('9CA3AF');
        $this->anchuras($sheet, ['A' => 12, 'B' => 12, 'C' => 12, 'D' => 12, 'E' => 12, 'F' => 12, 'G' => 12, 'H' => 12]);
    }

    // ------------------------------------------------------------------
    //  Utilidades de formato
    // ------------------------------------------------------------------

    private function apusDelProyecto(Proyecto $proyecto): iterable
    {
        return $proyecto->items
            ->filter(fn ($f) => ! $f->es_capitulo && $f->apu)
            ->map(fn ($f) => $f->apu)
            ->unique('id')
            ->values();
    }

    private function tituloHoja(Worksheet $sheet, string $texto, int $ultimaColumna): void
    {
        $letra = $this->letraColumna($ultimaColumna);
        $sheet->mergeCells("A1:{$letra}1");
        $sheet->setCellValue('A1', $texto);
        $sheet->getStyle("A1:{$letra}1")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A1:{$letra}1")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('D9E1F2');
        $sheet->getStyle("A1:{$letra}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(24);
    }

    private function encabezados(Worksheet $sheet, int $fila, array $valores): void
    {
        $letra = $this->letraColumna(count($valores));

        foreach ($valores as $i => $valor) {
            $sheet->setCellValue($this->letraColumna($i + 1).$fila, $valor);
        }

        $sheet->getStyle("A{$fila}:{$letra}{$fila}")->getFont()->setBold(true);
        $sheet->getStyle("A{$fila}:{$letra}{$fila}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('F3F4F6');
        $sheet->getStyle("A{$fila}:{$letra}{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function estiloFila(Worksheet $sheet, int $fila, string $referencia): void
    {
        $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("F{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("I{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("J{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("B{$fila}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function estiloSeccion(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->getFont()->setBold(true);
        $sheet->getStyle($rango)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('FFF2CC');
    }

    private function estiloSubtotal(Worksheet $sheet, string $rango)
    {
        $sheet->getStyle($rango)->getFont()->setBold(true);
        $sheet->getStyle($rango)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('E2E3F3');

        return $sheet->getStyle($rango);
    }

    private function estiloResumen(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($rango)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('1E3A5F');
    }

    private function bordes(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->getBorders()->applyFromArray([
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FFBFBFBF'],
            ],
        ]);
    }

    private function money(Worksheet $sheet, string $rango, string $formato): void
    {
        $sheet->getStyle($rango)->getNumberFormat()->setFormatCode($formato);
    }

    private function anchuras(Worksheet $sheet, array $columnas): void
    {
        foreach ($columnas as $letra => $ancho) {
            $sheet->getColumnDimension($letra)->setWidth($ancho);
        }
    }

    private function letraColumna(int $n): string
    {
        $letra = '';
        while ($n > 0) {
            $resto = ($n - 1) % 26;
            $letra = chr(65 + $resto).$letra;
            $n = intdiv($n - 1, 26);
        }

        return $letra;
    }

    private function nombreHoja(Apu $apu, array $usados): string
    {
        $base = 'APU '.($apu->codigo ?: 'SIN CODIGO');
        $base = substr($base, 0, 31);

        if (!isset($usados[$base])) {
            return $base;
        }

        $n = 2;

        while (true) {
            $nombre = substr($base, 0, 31 - strlen('-'.$n)).'-'.$n;

            if (!isset($usados[$nombre])) {
                return $nombre;
            }

            $n++;
        }
    }
}
