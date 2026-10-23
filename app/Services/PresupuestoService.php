<?php

namespace App\Services;

use App\Models\Proyecto;

/**
 * Fórmulas idénticas a la hoja "PRESUPUESTO GENERAL" del Excel del usuario:
 *
 * VR.UNITARIO = costo directo del APU (el AIU NO va dentro del precio unitario)
 * VR.TOTAL    = cantidad * vr_unitario
 * DURACIÓN    = cantidad * dias_por_unidad de la cuadrilla del APU
 *
 * Subtotal      = Σ vr_total                       (= costo directo del proyecto)
 * Administración= subtotal * pct_administracion%
 * Imprevistos   = subtotal * pct_imprevistos%
 * Utilidad      = subtotal * pct_utilidad%
 * TOTAL         = subtotal + administración + imprevistos + utilidad
 */
class PresupuestoService
{
    public static function calcular(Proyecto $proyecto): array
    {
        $lineas = collect();

        foreach ($proyecto->items as $fila) {
            if ($fila->es_capitulo || ! $fila->apu) {
                $lineas->push([
                    'id' => $fila->id,
                    'item' => $fila->item,
                    'descripcion' => $fila->titulo ?: ($fila->apu?->descripcion ?? ($fila->es_capitulo ? '' : '[APU eliminado]')),
                    'es_capitulo' => true,
                    'unidad' => '',
                    'cantidad' => null,
                    'vr_unitario' => null,
                    'vr_total' => null,
                    'duracion' => null,
                ]);

                continue;
            }

            $costoDirecto = ApuCalculator::calcular($fila->apu)['totales']['costo_directo'];
            $cantidad = $fila->cantidad ?? 0;
            $diasPorUnidad = $fila->apu->dias_por_unidad ?? null;

            $lineas->push([
                'id' => $fila->id,
                'item' => $fila->item,
                'descripcion' => $fila->apu->descripcion,
                'es_capitulo' => false,
                'unidad' => $fila->apu->unidad,
                'cantidad' => $cantidad,
                'vr_unitario' => $costoDirecto,
                'vr_total' => round($cantidad * $costoDirecto, 2),
                'duracion' => $diasPorUnidad !== null ? round($cantidad * $diasPorUnidad, 4) : null,
            ]);
        }

        $subtotal = round((float) $lineas->sum('vr_total'), 2);
        $administracion = round($subtotal * ($proyecto->pct_administracion / 100), 2);
        $imprevistos = round($subtotal * ($proyecto->pct_imprevistos / 100), 2);
        $utilidad = round($subtotal * ($proyecto->pct_utilidad / 100), 2);
        $total = round($subtotal + $administracion + $imprevistos + $utilidad, 2);

        return [
            'lineas' => $lineas,
            'resumen' => [
                'subtotal' => $subtotal,
                'administracion' => $administracion,
                'imprevistos' => $imprevistos,
                'utilidad' => $utilidad,
                'total' => $total,
            ],
        ];
    }
}
