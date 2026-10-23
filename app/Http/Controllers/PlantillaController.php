<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlantillaController extends Controller
{
    public function descargar(string $modulo): StreamedResponse
    {
        abort_unless(array_key_exists($modulo, config('importaciones', [])), 404);

        $definicion = config('importaciones.'.$modulo);
        $columnas = $definicion['columnas'];
        $ultimaColumna = chr(64 + count($columnas));

        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle(mb_substr($definicion['titulo'], 0, 31));

        $hoja->fromArray($columnas, null, 'A1');
        $hoja->fromArray($definicion['ejemplo'], null, 'A2');

        $hoja->getStyle('A1:'.$ultimaColumna.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
        ]);

        foreach (range('A', $ultimaColumna) as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($libro): void {
            (new Xlsx($libro))->save('php://output');
        }, 'plantilla-'.$modulo.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
