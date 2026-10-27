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
 * Ejecutado     = Σ cantidad_ejecutada * vr_unitario (valor ganado)
 * Avance financ.= ejecutado / subtotal
 * Administración= subtotal * pct_administracion%
 * Imprevistos   = subtotal * pct_imprevistos%
 * Utilidad      = subtotal * pct_utilidad%
 * IVA utilidad  = utilidad * pct_iva_utilidad%     (0% por defecto)
 * TOTAL         = subtotal + administración + imprevistos + utilidad + IVA
 *
 * Cada capítulo reporta el subtotal de sus ítems y su valor ejecutado.
 */
class PresupuestoService
{
    public static function calcular(Proyecto $proyecto): array
    {
        $lineas = collect();
        $capituloActualId = null;

        foreach ($proyecto->items as $fila) {
            if ($fila->es_capitulo || ! $fila->apu) {
                if ($fila->es_capitulo) {
                    $capituloActualId = $fila->id;
                }

                $lineas->push([
                    'id' => $fila->id,
                    'item' => $fila->item,
                    'descripcion' => $fila->titulo ?: ($fila->apu?->descripcion ?? ($fila->es_capitulo ? '' : '[APU eliminado]')),
                    'es_capitulo' => true,
                    'capitulo_id' => $fila->es_capitulo ? $fila->id : $capituloActualId,
                    'etapa_id' => $fila->etapa_id,
                    'unidad' => '',
                    'cantidad' => null,
                    'cantidad_ejecutada' => null,
                    'vr_unitario' => null,
                    'vr_total' => null,
                    'vr_ejecutado' => null,
                    'avance' => null,
                    'duracion' => null,
                ]);

                continue;
            }

            $costoDirecto = ApuCalculator::calcular($fila->apu)['totales']['costo_directo'];
            $cantidad = $fila->cantidad ?? 0;
            $ejecutada = $fila->cantidad_ejecutada ?? 0;
            $diasPorUnidad = $fila->apu->dias_por_unidad ?? null;

            $lineas->push([
                'id' => $fila->id,
                'item' => $fila->item,
                'descripcion' => $fila->apu->descripcion,
                'es_capitulo' => false,
                'capitulo_id' => $capituloActualId,
                'etapa_id' => $fila->etapa_id,
                'unidad' => $fila->apu->unidad,
                'cantidad' => $cantidad,
                'cantidad_ejecutada' => $ejecutada,
                'vr_unitario' => $costoDirecto,
                'vr_total' => round($cantidad * $costoDirecto, 2),
                'vr_ejecutado' => round($ejecutada * $costoDirecto, 2),
                'avance' => $cantidad > 0 ? round($ejecutada / $cantidad * 100, 2) : 0.0,
                'duracion' => $diasPorUnidad !== null ? round($cantidad * $diasPorUnidad, 4) : null,
            ]);
        }

        $items = $lineas->where('es_capitulo', false);

        $subtotal = round((float) $items->sum('vr_total'), 2);
        $ejecutado = round((float) $items->sum('vr_ejecutado'), 2);
        $avanceFinanciero = $subtotal > 0 ? round($ejecutado / $subtotal * 100, 2) : 0.0;

        // El capítulo agrupa los ítems que aparecen debajo de él en el presupuesto.
        $subtotalesCapitulo = [];
        $ejecutadosCapitulo = [];

        foreach ($lineas as $capitulo) {
            if (! $capitulo['es_capitulo']) {
                continue;
            }

            $itemsDelCapitulo = $lineas->filter(fn (array $linea): bool => ! $linea['es_capitulo']
                && $linea['capitulo_id'] === $capitulo['id']);

            $subtotalesCapitulo[$capitulo['id']] = round((float) $itemsDelCapitulo->sum('vr_total'), 2);
            $ejecutadosCapitulo[$capitulo['id']] = round((float) $itemsDelCapitulo->sum('vr_ejecutado'), 2);
        }

        $lineas = $lineas->map(function (array $linea) use ($subtotalesCapitulo, $ejecutadosCapitulo): array {
            if (! $linea['es_capitulo']) {
                return $linea;
            }

            $subtotalCapitulo = $subtotalesCapitulo[$linea['id']] ?? 0.0;
            $ejecutadoCapitulo = $ejecutadosCapitulo[$linea['id']] ?? 0.0;

            $linea['subtotal_capitulo'] = $subtotalCapitulo;
            $linea['vr_ejecutado'] = $ejecutadoCapitulo;
            $linea['avance'] = $subtotalCapitulo > 0
                ? round($ejecutadoCapitulo / $subtotalCapitulo * 100, 2)
                : 0.0;

            return $linea;
        });

        $administracion = round($subtotal * ($proyecto->pct_administracion / 100), 2);
        $imprevistos = round($subtotal * ($proyecto->pct_imprevistos / 100), 2);
        $utilidad = round($subtotal * ($proyecto->pct_utilidad / 100), 2);
        $ivaUtilidad = round($utilidad * ($proyecto->pct_iva_utilidad / 100), 2);
        $total = round($subtotal + $administracion + $imprevistos + $utilidad + $ivaUtilidad, 2);

        return [
            'lineas' => $lineas,
            'resumen' => [
                'subtotal' => $subtotal,
                'ejecutado' => $ejecutado,
                'avance_financiero' => $avanceFinanciero,
                'administracion' => $administracion,
                'imprevistos' => $imprevistos,
                'utilidad' => $utilidad,
                'iva_utilidad' => $ivaUtilidad,
                'total' => $total,
            ],
        ];
    }
}
