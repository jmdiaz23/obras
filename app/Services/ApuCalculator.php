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
     * C. EQUIPOS     : según la unidad de alquiler de la línea:
     *                    hr  -> valor_unitario = tarifa_dia / 8
     *                    dia -> valor_unitario = tarifa_dia
     *                    m2/m3/und -> valor_unitario definido en la línea
     *                  total = valor_unitario * cantidad
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
            $unidad = $linea->unidad ?: 'hr';
            $tarifaDia = (float) ($linea->equipo?->tarifa_dia ?? 0);

            $valorUnitario = match ($unidad) {
                'hr' => $tarifaDia / 8,
                'dia' => $tarifaDia,
                default => (float) ($linea->valor_unitario ?? 0),
            };

            return [
                'id' => $linea->id,
                'referencia' => $linea->equipo,
                'descripcion' => $linea->equipo?->descripcion ?? '[equipo eliminado]',
                'unidad' => $unidad,
                'cantidad' => $linea->cantidad,
                'valor_unitario' => $valorUnitario,
                'total' => $valorUnitario * $linea->cantidad,
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
