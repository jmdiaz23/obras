<?php

namespace App\Services;

use App\Models\Apu;

class ApuCalculator
{
    /**
     * Fórmulas idénticas a la hoja "ANALISIS DE PRECIOS UNITARIOS - A.P.U":
     *
     * A. MATERIALES  : total = cantidad * (1 + desperdicio%) * precio_lista
     * B. MANO DE OBRA: jornal+prest = jornal * (1 + prest%)
     *                  total       = jornal+prest * dias_por_unidad
     * C. EQUIPOS     : tarifa_hora = tarifa_dia / 8
     *                  total       = tarifa_hora * horas_por_unidad
     *   Herramientas : total_mo * pct_herramientas%
     *
     * COSTO DIRECTO = A + B + C(incl. herramientas)
     */
    public static function calcular(Apu $apu): array
    {
        $pctPrestaciones = $apu->empresa->pct_prestaciones;

        // Sin redondeos intermedios (igual que el Excel, que guarda los valores crudos);
        // solo se redondean los totales finales a 4 decimales como la hoja original.
        $materiales = $apu->materiales->map(function ($linea) {
            $precio = $linea->material?->precio ?? 0;
            $factor = 1 + ($linea->desperdicio_pct / 100);

            return [
                'id' => $linea->id,
                'referencia' => $linea->material,
                'descripcion' => $linea->material?->descripcion ?? '[material eliminado]',
                'unidad' => $linea->material?->unidad ?? '',
                'cantidad' => $linea->cantidad,
                'desperdicio' => $linea->desperdicio_pct,
                'precio' => $precio,
                'total' => $linea->cantidad * $factor * $precio,
            ];
        });

        $manoObra = $apu->manoObra->map(function ($linea) use ($pctPrestaciones) {
            $jornal = $linea->cargo?->jornal ?? 0;
            $jornalConPrestaciones = $jornal * (1 + $pctPrestaciones / 100);

            return [
                'id' => $linea->id,
                'referencia' => $linea->cargo,
                'descripcion' => $linea->cargo?->nombre ?? '[cargo eliminado]',
                'jornal' => $jornal,
                'jornal_prestaciones' => $jornalConPrestaciones,
                'dias_por_unidad' => $linea->dias_por_unidad,
                'total' => $jornalConPrestaciones * $linea->dias_por_unidad,
            ];
        });

        $equipos = $apu->equipos->map(function ($linea) {
            $tarifaHora = ($linea->equipo?->tarifa_dia ?? 0) / 8;

            return [
                'id' => $linea->id,
                'referencia' => $linea->equipo,
                'descripcion' => $linea->equipo?->descripcion ?? '[equipo eliminado]',
                'tarifa_hora' => $tarifaHora,
                'horas_por_unidad' => $linea->horas_por_unidad,
                'total' => $tarifaHora * $linea->horas_por_unidad,
            ];
        });

        // Precisión de 4 decimales, igual que la hoja original (ej: 14.482,5659).
        $totalMateriales = round($materiales->sum('total'), 4);
        $totalManoObra = round($manoObra->sum('total'), 4);
        $herramientas = round($totalManoObra * ($apu->pct_herramientas / 100), 4);
        $totalEquipos = round($equipos->sum('total') + $herramientas, 4);
        $costoDirecto = round($totalMateriales + $totalManoObra + $totalEquipos, 4);

        return [
            'materiales' => $materiales,
            'manoObra' => $manoObra,
            'equipos' => $equipos,
            'totales' => [
                'materiales' => $totalMateriales,
                'mano_obra' => $totalManoObra,
                'herramientas' => $herramientas,
                'equipos' => $totalEquipos,
                'costo_directo' => $costoDirecto,
            ],
        ];
    }
}
