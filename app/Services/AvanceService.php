<?php

namespace App\Services;

use App\Models\Proyecto;
use App\Models\ProyectoEtapa;
use Illuminate\Support\Collection;

/**
 * Consolida el avance de obra a partir de las cantidades ejecutadas.
 *
 * - Avance financiero: valor ganado / subtotal (costo directo). Es el
 *   indicador confiable cuando el proyecto mezcla unidades.
 * - Avance físico: solo se calcula cuando todos los ítems del grupo comparten
 *   una misma unidad; de lo contrario se informa como no disponible.
 */
class AvanceService
{
    public static function calcular(Proyecto $proyecto, ?array $presupuesto = null): array
    {
        $proyecto->loadMissing([
            'items.apu.materiales.material',
            'items.apu.manoObra.cargo',
            'items.apu.equipos.equipo',
            'items.apu.empresa',
            'etapas',
        ]);

        $presupuesto ??= PresupuestoService::calcular($proyecto);
        $etapas = $proyecto->etapas->sortBy('orden')->values();

        $capitulosBase = $presupuesto['lineas']->where('es_capitulo', true)->values();
        $etapaPorCapitulo = $capitulosBase
            ->mapWithKeys(fn (array $capitulo): array => [$capitulo['id'] => $capitulo['etapa_id']])
            ->all();

        $items = $presupuesto['lineas']
            ->where('es_capitulo', false)
            ->values()
            ->map(function (array $item) use ($etapaPorCapitulo): array {
                // El capítulo manda: así una etapa recién asignada se refleja
                // de inmediato aunque algún ítem no se haya propagado todavía.
                $item['etapa_id'] = $etapaPorCapitulo[$item['capitulo_id']] ?? $item['etapa_id'];

                return $item;
            });

        $itemsPorCapitulo = $items->groupBy(fn (array $item) => $item['capitulo_id'] ?? 'sin');
        $itemsPorEtapa = $items->groupBy(fn (array $item) => $item['etapa_id'] ?? 'sin');

        $capitulos = $capitulosBase
            ->map(function (array $capitulo) use ($itemsPorCapitulo): array {
                $itemsCapitulo = $itemsPorCapitulo->get($capitulo['id'], collect());
                $plan = round((float) $itemsCapitulo->sum('vr_total'), 2);
                $ejecutado = round((float) $itemsCapitulo->sum('vr_ejecutado'), 2);
                $fisico = self::fisico($itemsCapitulo);

                return [
                    'id' => $capitulo['id'],
                    'item' => $capitulo['item'],
                    'nombre' => $capitulo['descripcion'] ?: 'Capítulo sin título',
                    'etapa_id' => $capitulo['etapa_id'],
                    'plan' => $plan,
                    'ejecutado' => $ejecutado,
                    'avance_financiero' => self::porcentaje($plan, $ejecutado),
                    'avance_fisico' => $fisico['porcentaje'],
                    'unidad_fisica' => $fisico['unidad'],
                    'items' => $itemsCapitulo->count(),
                    'items_terminados' => self::terminados($itemsCapitulo),
                ];
            })
            ->values();

        $capitulosPorEtapa = $capitulos->groupBy(fn (array $capitulo) => $capitulo['etapa_id'] ?? 'sin');

        $resumenEtapas = $etapas
            ->map(fn (ProyectoEtapa $etapa): array => self::resumenGrupo(
                $etapa,
                $itemsPorEtapa->get($etapa->id, collect()),
                $capitulosPorEtapa->get($etapa->id, collect()),
            ))
            ->values();

        if ($itemsPorEtapa->has('sin') || $capitulosPorEtapa->has('sin')) {
            $resumenEtapas->push(self::resumenGrupo(
                null,
                $itemsPorEtapa->get('sin', collect()),
                $capitulosPorEtapa->get('sin', collect()),
            ));
        }

        $fisicoGeneral = self::fisico($items);
        $resumen = $presupuesto['resumen'];

        return [
            'resumen' => [
                'plan' => $resumen['subtotal'],
                'ejecutado' => $resumen['ejecutado'],
                'avance_financiero' => $resumen['avance_financiero'],
                'avance_fisico' => $fisicoGeneral['porcentaje'],
                'unidad_fisica' => $fisicoGeneral['unidad'],
                'items' => $items->count(),
                'items_terminados' => self::terminados($items),
                'items_con_avance' => $items
                    ->filter(fn (array $item): bool => (float) $item['cantidad_ejecutada'] > 0)
                    ->count(),
            ],
            'etapas' => $resumenEtapas->all(),
            'capitulos' => $capitulos->all(),
            'items' => $items
                ->map(fn (array $item): array => [
                    'id' => $item['id'],
                    'item' => $item['item'],
                    'descripcion' => $item['descripcion'],
                    'unidad' => $item['unidad'],
                    'cantidad' => $item['cantidad'],
                    'cantidad_ejecutada' => $item['cantidad_ejecutada'],
                    'avance' => $item['avance'],
                    'plan' => $item['vr_total'],
                    'ejecutado' => $item['vr_ejecutado'],
                    'capitulo_id' => $item['capitulo_id'],
                    'etapa_id' => $item['etapa_id'],
                ])
                ->values()
                ->all(),
        ];
    }

    private static function resumenGrupo(?ProyectoEtapa $etapa, Collection $items, Collection $capitulos): array
    {
        $plan = round((float) $items->sum('vr_total'), 2);
        $ejecutado = round((float) $items->sum('vr_ejecutado'), 2);
        $fisico = self::fisico($items);

        return [
            'id' => $etapa?->id,
            'nombre' => $etapa?->nombre ?? 'Sin etapa',
            'orden' => $etapa?->orden ?? PHP_INT_MAX,
            'fecha_inicio' => $etapa?->fecha_inicio?->toDateString(),
            'fecha_fin' => $etapa?->fecha_fin?->toDateString(),
            'plan' => $plan,
            'ejecutado' => $ejecutado,
            'avance_financiero' => self::porcentaje($plan, $ejecutado),
            'avance_fisico' => $fisico['porcentaje'],
            'unidad_fisica' => $fisico['unidad'],
            'capitulos' => $capitulos->count(),
            'items' => $items->count(),
            'items_terminados' => self::terminados($items),
        ];
    }

    private static function fisico(Collection $items): array
    {
        $conCantidad = $items->filter(fn (array $item): bool => (float) $item['cantidad'] > 0);

        if ($conCantidad->isEmpty()) {
            return ['porcentaje' => null, 'unidad' => null];
        }

        $unidades = $conCantidad
            ->map(fn (array $item): string => strtoupper(trim((string) $item['unidad'])))
            ->filter()
            ->unique();

        if ($unidades->count() !== 1) {
            return ['porcentaje' => null, 'unidad' => null];
        }

        $plan = (float) $conCantidad->sum('cantidad');
        $ejecutado = (float) $conCantidad->sum('cantidad_ejecutada');

        return [
            'porcentaje' => $plan > 0 ? round($ejecutado / $plan * 100, 2) : null,
            'unidad' => (string) $conCantidad->first()['unidad'],
        ];
    }

    private static function terminados(Collection $items): int
    {
        return $items
            ->filter(function (array $item): bool {
                $cantidad = (float) $item['cantidad'];

                return $cantidad > 0 && (float) $item['cantidad_ejecutada'] >= $cantidad - 0.00005;
            })
            ->count();
    }

    private static function porcentaje(float $plan, float $ejecutado): float
    {
        return $plan > 0 ? round($ejecutado / $plan * 100, 2) : 0.0;
    }
}
