<?php

namespace App\Livewire\Catalogos\Concerns;

use App\Services\ImportacionExcelService;
use Livewire\WithFileUploads;

/**
 * Agrega a un catálogo la capacidad de importar registros desde Excel.
 *
 * El componente que lo use debe implementar moduloImportacion() y usar
 * WithPagination (para resetPage()).
 */
trait ImportaExcel
{
    use WithFileUploads;

    public bool $mostrarImport = false;

    public $archivoImport = null;

    public bool $importando = false;

    public ?array $resultadoImport = null;

    abstract protected function moduloImportacion(): string;

    public function abrirImport(): void
    {
        $this->reset(['archivoImport', 'resultadoImport']);
        $this->resetErrorBag('archivoImport');
        $this->mostrarImport = true;
    }

    public function cerrarImport(): void
    {
        $this->mostrarImport = false;
        $this->reset(['archivoImport', 'resultadoImport']);
    }

    public function importar(): void
    {
        $this->validate([
            'archivoImport' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'archivoImport.required' => 'Selecciona un archivo Excel.',
            'archivoImport.file' => 'El archivo no es válido.',
            'archivoImport.mimes' => 'El archivo debe ser .xlsx, .xls o .csv.',
            'archivoImport.max' => 'El archivo no puede superar los 10 MB.',
        ]);

        $this->importando = true;

        try {
            $this->resultadoImport = app(ImportacionExcelService::class)->importar(
                $this->moduloImportacion(),
                $this->archivoImport->getRealPath(),
                (int) auth()->user()->empresa_id,
            );

            $this->archivoImport = null;
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->resultadoImport = [
                'creados' => 0,
                'actualizados' => 0,
                'omitidos' => 0,
                'errores' => [
                    ['fila' => 0, 'mensaje' => 'No se pudo leer el archivo: '.$e->getMessage()],
                ],
            ];
        } finally {
            $this->importando = false;
        }
    }
}
