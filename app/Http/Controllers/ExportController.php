<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Services\ExcelExportService;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    public function xlsx(Proyecto $proyecto)
    {
        $contenido = app(ExcelExportService::class)->exportar($proyecto);

        $nombre = 'PRESUPUESTO_'.strtoupper(Str::slug($proyecto->nombre, '_')).'.xlsx';

        return response()->streamDownload(
            static function () use ($contenido): void {
                echo $contenido;
            },
            $nombre,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
