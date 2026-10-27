@php
    $r = $avance['resumen'];
    $fmt = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.');
    $pct = fn ($v) => $v === null ? '—' : number_format((float) $v, 1, ',', '.').' %';
    $ancho = fn ($v) => max(0, min(100, (float) ($v ?? 0)));
    $etapas = collect($avance['etapas']);
    $capitulos = collect($avance['capitulos']);
    $items = collect($avance['items']);
@endphp

<div class="mx-auto max-w-6xl space-y-5">
    {{-- Encabezado --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('proyectos.editar', $proyecto) }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-slate-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Volver al editor
            </a>
            <h2 class="mt-2 text-xl font-bold text-slate-900">Avance de obra</h2>
            <p class="text-sm text-slate-500">{{ $proyecto->nombre }}@if($proyecto->cliente) · {{ $proyecto->cliente }}@endif</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('proyectos.editar', $proyecto) }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Editar presupuesto
            </a>
            <a href="{{ route('proyectos.exportar', $proyecto->id) }}"
               x-data="{ descargando: false }"
               @click.prevent="descargando = true; window.descargarExcel($el).finally(function () { descargando = false })"
               class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                <span x-show="!descargando">Exportar Excel</span>
                <span x-show="descargando" x-cloak>Generando...</span>
            </a>
        </div>
    </div>

    {{-- Resumen general --}}
    <div class="rounded-2xl bg-slate-900 p-5 text-white shadow-xl shadow-slate-900/20 sm:p-6">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Avance financiero</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-amber-400">{{ $pct($r['avance_financiero']) }}</p>
                <p class="mt-1 text-xs text-slate-400">Valor ganado / costo directo</p>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Valor ejecutado</p>
                <p class="mt-1 font-mono text-xl font-semibold">{{ $fmt($r['ejecutado']) }}</p>
                <p class="mt-1 text-xs text-slate-400">de {{ $fmt($r['plan']) }} de presupuesto</p>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Avance físico</p>
                <p class="mt-1 text-2xl font-bold {{ $r['avance_fisico'] === null ? 'text-slate-500' : 'text-white' }}">
                    {{ $pct($r['avance_fisico']) }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    {{ $r['avance_fisico'] === null ? 'Unidades mixtas: usa el avance financiero' : 'Unidad: '.$r['unidad_fisica'] }}
                </p>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Ítems terminados</p>
                <p class="mt-1 text-2xl font-bold">{{ $r['items_terminados'] }} <span class="text-base font-medium text-slate-400">de {{ $r['items'] }}</span></p>
                <p class="mt-1 text-xs text-slate-400">{{ $r['items_con_avance'] }} con ejecución registrada</p>
            </div>
        </div>

        <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-white/10">
            <div class="h-full rounded-full bg-amber-400 transition-all" style="width: {{ $ancho($r['avance_financiero']) }}%"></div>
        </div>
    </div>

    {{-- Etapas --}}
    <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-900">Avance por etapa</h3>
                <p class="text-sm text-slate-500">Cada etapa agrupa sus capítulos y consolida el valor ganado.</p>
            </div>
            <p class="text-xs text-slate-400">El avance físico solo aparece cuando la etapa comparte una unidad.</p>
        </div>

        @forelse ($etapas as $etapa)
            @php
                $capitulosEtapa = $etapa['id'] === null
                    ? $capitulos->filter(fn ($capitulo) => $capitulo['etapa_id'] === null)
                    : $capitulos->where('etapa_id', $etapa['id']);
            @endphp
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-semibold text-slate-900">{{ $etapa['nombre'] }}</h4>
                                @if ($etapa['id'] === null)
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-700">Requiere asignación</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $etapa['capitulos'] }} capítulo(s) · {{ $etapa['items'] }} ítem(s)
                                @if ($etapa['fecha_inicio'] || $etapa['fecha_fin'])
                                    · {{ $etapa['fecha_inicio'] ? \Carbon\Carbon::parse($etapa['fecha_inicio'])->format('d/m/Y') : 'Sin inicio' }}
                                    — {{ $etapa['fecha_fin'] ? \Carbon\Carbon::parse($etapa['fecha_fin'])->format('d/m/Y') : 'Sin fin' }}
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-slate-900">{{ $pct($etapa['avance_financiero']) }}</p>
                            <p class="text-xs text-slate-500">{{ $fmt($etapa['ejecutado']) }} / {{ $fmt($etapa['plan']) }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500">
                                <span>Financiero</span>
                                <span>{{ $pct($etapa['avance_financiero']) }}</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-amber-400" style="width: {{ $ancho($etapa['avance_financiero']) }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500">
                                <span>Físico @if($etapa['unidad_fisica']) · {{ $etapa['unidad_fisica'] }} @endif</span>
                                <span>{{ $etapa['avance_fisico'] === null ? 'No disponible' : $pct($etapa['avance_fisico']) }}</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
                                @if ($etapa['avance_fisico'] === null)
                                    <div class="h-full w-0 rounded-full bg-slate-300"></div>
                                @else
                                    <div class="h-full rounded-full bg-sky-500" style="width: {{ $ancho($etapa['avance_fisico']) }}%"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-2.5">Capítulo</th>
                                <th class="px-3 py-2.5 text-center">Ítems</th>
                                <th class="px-3 py-2.5 text-right">Valor plan</th>
                                <th class="px-3 py-2.5 text-right">Ejecutado</th>
                                <th class="px-3 py-2.5 text-right">Financiero</th>
                                <th class="px-5 py-2.5 text-right">Físico</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($capitulosEtapa as $capitulo)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-3">
                                        <p class="font-mono text-xs text-amber-600">{{ $capitulo['item'] }}</p>
                                        <p class="font-medium text-slate-700">{{ $capitulo['nombre'] }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-center text-xs text-slate-500">{{ $capitulo['items'] }}</td>
                                    <td class="px-3 py-3 text-right font-mono text-xs text-slate-600">{{ $fmt($capitulo['plan']) }}</td>
                                    <td class="px-3 py-3 text-right font-mono text-xs font-semibold text-slate-900">{{ $fmt($capitulo['ejecutado']) }}</td>
                                    <td class="px-3 py-3 text-right">
                                        <span class="text-xs font-semibold text-slate-800">{{ $pct($capitulo['avance_financiero']) }}</span>
                                        <div class="ml-auto mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-amber-400" style="width: {{ $ancho($capitulo['avance_financiero']) }}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="text-xs font-semibold {{ $capitulo['avance_fisico'] === null ? 'text-slate-400' : 'text-slate-800' }}">
                                            {{ $pct($capitulo['avance_fisico']) }}
                                        </span>
                                        @if ($capitulo['avance_fisico'] !== null)
                                            <p class="mt-0.5 text-[10px] text-slate-400">{{ $capitulo['unidad_fisica'] }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-6 text-center text-xs text-slate-400">Esta etapa todavía no tiene capítulos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-200">
                Aún no hay etapas. Créalas desde el editor del proyecto para agrupar los capítulos.
            </div>
        @endforelse
    </div>

    {{-- Detalle de cantidades --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h3 class="font-semibold text-slate-900">Detalle de cantidades ejecutadas</h3>
            <p class="text-sm text-slate-500">El avance por ítem es la cantidad ejecutada sobre la cantidad presupuestada.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-2.5">Ítem</th>
                        <th class="px-3 py-2.5">Descripción</th>
                        <th class="px-3 py-2.5">Und.</th>
                        <th class="px-3 py-2.5 text-right">Cantidad</th>
                        <th class="px-3 py-2.5 text-right">Ejecutado</th>
                        <th class="px-3 py-2.5 text-right">Avance</th>
                        <th class="px-3 py-2.5 text-right">Valor plan</th>
                        <th class="px-5 py-2.5 text-right">Valor ganado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $item['item'] }}</td>
                            <td class="px-3 py-3 font-medium text-slate-700">{{ $item['descripcion'] }}</td>
                            <td class="px-3 py-3 text-xs text-slate-500">{{ $item['unidad'] }}</td>
                            <td class="px-3 py-3 text-right font-mono text-xs text-slate-600">{{ number_format((float) $item['cantidad'], 4, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right font-mono text-xs font-semibold text-slate-900">{{ number_format((float) $item['cantidad_ejecutada'], 4, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right">
                                <span class="text-xs font-semibold {{ (float) $item['avance'] >= 100 ? 'text-emerald-600' : 'text-slate-700' }}">{{ $pct($item['avance']) }}</span>
                                <div class="ml-auto mt-1 h-1.5 w-20 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ (float) $item['avance'] >= 100 ? 'bg-emerald-500' : 'bg-amber-400' }}" style="width: {{ $ancho($item['avance']) }}%"></div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-xs text-slate-600">{{ $fmt($item['plan']) }}</td>
                            <td class="px-5 py-3 text-right font-mono text-xs font-semibold text-slate-900">{{ $fmt($item['ejecutado']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-8 text-center text-sm text-slate-400">Todavía no hay ítems ejecutables en el presupuesto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="pb-2 text-center text-[11px] text-slate-400">
        El avance financiero es el indicador consolidado del proyecto. El avance físico se calcula solo dentro de una misma unidad de medida.
    </p>
</div>
