@php
    $r = $calculo['resumen'];
    $fmt = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.');
    $pct = fn ($v) => $v === null ? '—' : number_format((float) $v, 1, ',', '.').' %';
    $ancho = fn ($v) => max(0, min(100, (float) ($v ?? 0)));
    $avanceResumen = $avance['resumen'];
    $etapasResumen = collect($avance['etapas'])->keyBy(fn ($e) => (string) ($e['id'] ?? 'sin'));
@endphp

<div class="mx-auto max-w-6xl space-y-5">
    {{-- Volver / avance --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('proyectos.index') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-slate-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Volver a proyectos
        </a>
        <a href="{{ route('proyectos.avance', $proyecto) }}" wire:navigate
           class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
            Ver avance de obra
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
        </a>
    </div>

    {{-- Datos generales --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-form.input label="Nombre del proyecto" name="cab.nombre" wire:model.lazy="cab.nombre" required />
            <x-form.input label="Cliente" name="cab.cliente" wire:model.lazy="cab.cliente" placeholder="Nombre del cliente" />
            <x-form.input label="Ubicación" name="cab.ubicacion" wire:model.lazy="cab.ubicacion" placeholder="Ciudad / barrio" />
            <div>
                <label for="cab.estado" class="mb-1.5 block text-sm font-medium text-slate-700">Estado</label>
                <select id="cab.estado" wire:model.lazy="cab.estado"
                        class="campo">
                    @foreach ($estados as $valor => $texto)
                        <option value="{{ $valor }}" @selected($cab['estado'] === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
                @error('cab.estado') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">AIU e IVA de este proyecto</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Administración %</span>
                        <input type="number" step="any" min="0" max="100" wire:model.lazy="cab.pct_administracion"
                               class="campo">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Imprevistos %</span>
                        <input type="number" step="any" min="0" max="100" wire:model.lazy="cab.pct_imprevistos"
                               class="campo">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Utilidad %</span>
                        <input type="number" step="any" min="0" max="100" wire:model.lazy="cab.pct_utilidad"
                               class="campo">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">IVA s/ utilidad %</span>
                        <input type="number" step="any" min="0" max="100" wire:model.lazy="cab.pct_iva_utilidad"
                               class="campo">
                    </label>
                </div>
                <p class="mt-1.5 text-[11px] text-slate-400">El IVA se aplica únicamente sobre la utilidad. Déjalo en 0 si no aplica.</p>
            </div>
        </div>
    </div>

    {{-- Resumen de avance --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold tracking-wide text-slate-700">AVANCE DE OBRA</h3>
                <p class="mt-0.5 text-sm text-slate-500">Registra cantidades ejecutadas: el avance financiero se calcula con el valor ganado.</p>
            </div>
            <a href="{{ route('proyectos.avance', $proyecto) }}" wire:navigate
               class="text-sm font-semibold text-amber-600 transition hover:text-amber-700">Ver detalle por etapa</a>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Financiero</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $pct($avanceResumen['avance_financiero']) }}</p>
                <p class="text-[11px] text-slate-500">Valor ganado / subtotal</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ejecutado</p>
                <p class="mt-1 font-mono text-lg font-semibold text-slate-900">{{ $fmt($avanceResumen['ejecutado']) }}</p>
                <p class="text-[11px] text-slate-500">de {{ $fmt($avanceResumen['plan']) }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Físico</p>
                <p class="mt-1 text-2xl font-bold {{ $avanceResumen['avance_fisico'] === null ? 'text-slate-400' : 'text-slate-900' }}">{{ $pct($avanceResumen['avance_fisico']) }}</p>
                <p class="text-[11px] text-slate-500">{{ $avanceResumen['avance_fisico'] === null ? 'Unidades mixtas' : 'Unidad: '.$avanceResumen['unidad_fisica'] }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ítems terminados</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $avanceResumen['items_terminados'] }} <span class="text-sm font-medium text-slate-400">de {{ $avanceResumen['items'] }}</span></p>
                <p class="text-[11px] text-slate-500">{{ $avanceResumen['items_con_avance'] }} con ejecución</p>
            </div>
        </div>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-amber-400" style="width: {{ $ancho($avanceResumen['avance_financiero']) }}%"></div>
        </div>
        <p class="mt-2 text-[11px] text-slate-400">El avance físico solo se calcula cuando todos los ítems comparten una unidad de medida.</p>
    </div>

    {{-- Etapas --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold tracking-wide text-slate-700">ETAPAS DE OBRA</h3>
                <p class="mt-0.5 text-sm text-slate-500">Agrupa capítulos por etapa para leer el avance por frentes de trabajo.</p>
            </div>
            <button wire:click="abrirEtapa"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva etapa
            </button>
        </div>

        @if ($mostrarEtapa)
            <form class="mt-4 grid gap-3 rounded-xl bg-amber-50/70 p-4 ring-1 ring-amber-200 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_auto]"
                  wire:submit="agregarEtapa">
                <div>
                    <label for="etapa.nombre" class="mb-1 block text-xs font-semibold text-slate-600">Nombre de la etapa</label>
                    <input id="etapa.nombre" type="text" wire:model="etapa.nombre" placeholder="Etapa 1 — Obra gris"
                           class="campo">
                    @error('etapa.nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="etapa.fecha_inicio" class="mb-1 block text-xs font-semibold text-slate-600">Inicio (opcional)</label>
                    <input id="etapa.fecha_inicio" type="date" wire:model="etapa.fecha_inicio" class="campo">
                    @error('etapa.fecha_inicio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="etapa.fecha_fin" class="mb-1 block text-xs font-semibold text-slate-600">Fin (opcional)</label>
                    <input id="etapa.fecha_fin" type="date" wire:model="etapa.fecha_fin" class="campo">
                    @error('etapa.fecha_fin') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600">Crear</button>
                    <button type="button" wire:click="$set('mostrarEtapa', false)" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 hover:bg-white/70">Cancelar</button>
                </div>
            </form>
        @endif

        @if ($etapas->isEmpty())
            <div class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                Aún no hay etapas. Crea la primera y asígnala a los capítulos del presupuesto.
            </div>
        @else
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($etapas as $etapa)
                    @php
                        $resumenEtapa = $etapasResumen->get((string) $etapa->id);
                    @endphp
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $etapa->nombre }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">
                                    {{ $resumenEtapa['capitulos'] ?? 0 }} capítulo(s) · {{ $resumenEtapa['items'] ?? 0 }} ítem(s)
                                </p>
                            </div>
                            <button wire:click="eliminarEtapa({{ $etapa->id }})"
                                    wire:confirm="¿Eliminar la etapa? Sus capítulos quedarán sin etapa."
                                    title="Eliminar etapa"
                                    class="rounded p-1 text-slate-300 transition hover:bg-red-50 hover:text-red-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            @if ($etapa->fecha_inicio || $etapa->fecha_fin)
                                {{ $etapa->fecha_inicio?->format('d/m/Y') ?? 'Sin inicio' }} — {{ $etapa->fecha_fin?->format('d/m/Y') ?? 'Sin fin' }}
                            @else
                                Sin fechas definidas
                            @endif
                        </p>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Financiero</p>
                                <p class="text-lg font-bold text-slate-900">{{ $pct($resumenEtapa['avance_financiero'] ?? 0) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Físico</p>
                                <p class="text-lg font-bold {{ ($resumenEtapa['avance_fisico'] ?? null) === null ? 'text-slate-400' : 'text-slate-900' }}">{{ $pct($resumenEtapa['avance_fisico'] ?? null) }}</p>
                            </div>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-amber-400" style="width: {{ $ancho($resumenEtapa['avance_financiero'] ?? 0) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Presupuesto --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3.5">
            <h3 class="text-sm font-bold tracking-wide text-slate-700">PRESUPUESTO GENERAL</h3>
            <div class="flex items-center gap-3">
                <span class="hidden text-xs text-slate-500 sm:inline">Vr. unitario = costo directo del APU · el AIU se aplica sobre el subtotal</span>
                <a href="{{ route('proyectos.exportar', $proyecto->id) }}"
                   x-data="{ descargando: false }"
                   @click.prevent="descargando = true; window.descargarExcel($el).finally(function () { descargando = false })"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    <span x-show="!descargando">Exportar Excel</span>
                    <span x-show="descargando" x-cloak>Generando...</span>
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1080px] text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="w-16 px-4 py-2.5">Ítem</th>
                        <th class="px-4 py-2.5">Descripción</th>
                        <th class="w-16 px-3 py-2.5">Und.</th>
                        <th class="w-28 px-3 py-2.5 text-right">Cantidad</th>
                        <th class="w-32 px-3 py-2.5 text-right">Vr. unitario</th>
                        <th class="w-36 px-3 py-2.5 text-right">Vr. total</th>
                        <th class="w-24 px-3 py-2.5 text-right">Días</th>
                        <th class="w-28 px-3 py-2.5 text-right">Ejecutado</th>
                        <th class="w-28 px-3 py-2.5 text-right">Avance</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($calculo['lineas'] as $linea)
                        @if ($linea['es_capitulo'])
                            <tr wire:key="cap{{ $linea['id'] }}" class="bg-slate-50/80">
                                <td class="px-4 py-2.5 font-mono text-xs font-bold text-amber-600">{{ $linea['item'] }}</td>
                                <td class="px-4 py-2.5">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-600">{{ $linea['descripcion'] ?: 'Capítulo sin título' }}</p>
                                    <div class="mt-1.5 flex items-center gap-2">
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Etapa</span>
                                        <select wire:change="asignarEtapa({{ $linea['id'] }}, $event.target.value)"
                                                class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-[11px] font-medium text-slate-600 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400">
                                            <option value="" @selected($linea['etapa_id'] === null)>Sin etapa</option>
                                            @foreach ($etapas as $etapa)
                                                <option value="{{ $etapa->id }}" @selected($linea['etapa_id'] === $etapa->id)>{{ $etapa->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5"></td>
                                <td class="px-3 py-2.5"></td>
                                <td class="px-3 py-2.5"></td>
                                <td class="px-3 py-2.5 text-right font-mono text-xs font-semibold text-slate-700">
                                    <span class="mr-1 text-[9px] font-bold uppercase tracking-wider text-slate-400">Subtotal</span>
                                    {{ $fmt($linea['subtotal_capitulo'] ?? 0) }}
                                </td>
                                <td class="px-3 py-2.5 text-right text-xs text-slate-400">—</td>
                                <td class="px-3 py-2.5 text-right font-mono text-xs text-slate-600">{{ number_format((float) ($linea['vr_ejecutado'] ?? 0), 2, ',', '.') }}</td>
                                <td class="px-3 py-2.5 text-right">
                                    <span class="text-xs font-semibold text-slate-700">{{ $pct($linea['avance'] ?? 0) }}</span>
                                    <div class="ml-auto mt-1 h-1.5 w-20 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-amber-400" style="width: {{ $ancho($linea['avance'] ?? 0) }}%"></div>
                                    </div>
                                </td>
                                <td class="pr-3 text-right">
                                    <button wire:click="quitarLinea({{ $linea['id'] }})" title="Quitar capítulo"
                                            class="rounded p-1 text-slate-300 transition hover:bg-red-50 hover:text-red-500">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @else
                            <tr wire:key="lin{{ $linea['id'] }}" class="hover:bg-slate-50/60">
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $linea['item'] }}</td>
                                <td class="px-4 py-2.5 font-medium text-slate-700">{{ $linea['descripcion'] }}</td>
                                <td class="px-3 py-2.5 text-slate-500">{{ $linea['unidad'] }}</td>
                                <td class="px-3 py-2.5">
                                    <input type="number" step="any" min="0" wire:model.live="cantidades.{{ $linea['id'] }}"
                                           class="campo px-2 py-2 text-right">
                                </td>
                                <td class="px-3 py-2.5 text-right font-mono text-slate-500">{{ number_format((float) $linea['vr_unitario'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2.5 text-right font-mono font-semibold text-slate-900">{{ number_format((float) $linea['vr_total'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2.5 text-right font-mono text-slate-500">
                                    {{ $linea['duracion'] !== null ? number_format((float) $linea['duracion'], 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <input type="number" step="any" min="0" wire:model.live.debounce.500ms="ejecutadas.{{ $linea['id'] }}"
                                           class="campo px-2 py-2 text-right" title="Cantidad ejecutada">
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <span class="text-xs font-semibold {{ (float) $linea['avance'] >= 100 ? 'text-emerald-600' : 'text-slate-700' }}">{{ $pct($linea['avance']) }}</span>
                                    <div class="ml-auto mt-1 h-1.5 w-20 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ (float) $linea['avance'] >= 100 ? 'bg-emerald-500' : 'bg-amber-400' }}" style="width: {{ $ancho($linea['avance']) }}%"></div>
                                    </div>
                                </td>
                                <td class="pr-3 text-right">
                                    <button wire:click="quitarLinea({{ $linea['id'] }})" title="Quitar ítem"
                                            class="rounded p-1 text-slate-300 transition hover:bg-red-50 hover:text-red-500">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="10" class="px-5 py-10 text-center text-sm text-slate-400">
                            Agrega un capítulo y luego los ítems desde tus APUs.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Barra de agregar --}}
        <div class="border-t border-slate-100 px-5 py-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[240px] flex-1">
                    <label for="sel-apu" class="mb-1 block text-xs font-semibold text-slate-500">Agregar APU como ítem</label>
                    <select id="sel-apu" wire:model="selApu"
                            class="campo">
                        <option value="">Selecciona un APU...</option>
                        @foreach ($apusDisponibles as $apuItem)
                            <option value="{{ $apuItem->id }}">{{ $apuItem->codigo ?? '—' }} — {{ Str::limit($apuItem->descripcion, 55) }} ({{ $apuItem->unidad }})</option>
                        @endforeach
                    </select>
                </div>
                <button wire:click="agregarApu" @disabled(!$selApu)
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition enabled:hover:bg-slate-800 disabled:opacity-40">
                    Agregar
                </button>
                <button type="button" wire:click="abrirCapitulo"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    + Capítulo
                </button>
            </div>

            @if ($mostrarCapitulo)
                <form class="mt-4 flex flex-wrap items-end gap-3 rounded-xl bg-amber-50/70 p-3 ring-1 ring-amber-200" wire:submit="agregarCapitulo">
                    <div class="w-24">
                        <label for="capitulo.item" class="mb-1 block text-xs font-semibold text-slate-600">Nº capítulo</label>
                        <input id="capitulo.item" type="text" wire:model="capitulo.item"
                               class="campo">
                        @error('capitulo.item') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="min-w-[220px] flex-1">
                        <label for="capitulo.titulo" class="mb-1 block text-xs font-semibold text-slate-600">Título</label>
                        <input id="capitulo.titulo" type="text" wire:model="capitulo.titulo" placeholder="OBRAS PRELIMINARES"
                               class="campo">
                        @error('capitulo.titulo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="w-48">
                        <label for="capitulo.etapa_id" class="mb-1 block text-xs font-semibold text-slate-600">Etapa (opcional)</label>
                        <select id="capitulo.etapa_id" wire:model="capitulo.etapa_id" class="campo">
                            <option value="">Sin etapa</option>
                            @foreach ($etapas as $etapa)
                                <option value="{{ $etapa->id }}">{{ $etapa->nombre }}</option>
                            @endforeach
                        </select>
                        @error('capitulo.etapa_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600">Guardar capítulo</button>
                    <button type="button" wire:click="$set('mostrarCapitulo', false)" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 hover:bg-white/70">Cancelar</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Panel de resumen AIU --}}
    <div class="sticky bottom-4 rounded-2xl bg-slate-900 p-5 shadow-xl shadow-slate-900/25">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-6">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Subtotal (costo directo)</p>
                <p class="mt-0.5 font-mono text-sm text-slate-200">{{ $fmt($r['subtotal']) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administración ({{ rtrim(rtrim(number_format($cab['pct_administracion'], 2, '.', ''), '0'), '.') }}%)</p>
                <p class="mt-0.5 font-mono text-sm text-slate-200">{{ $fmt($r['administracion']) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Imprevistos ({{ rtrim(rtrim(number_format($cab['pct_imprevistos'], 2, '.', ''), '0'), '.') }}%)</p>
                <p class="mt-0.5 font-mono text-sm text-slate-200">{{ $fmt($r['imprevistos']) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Utilidad ({{ rtrim(rtrim(number_format($cab['pct_utilidad'], 2, '.', ''), '0'), '.') }}%)</p>
                <p class="mt-0.5 font-mono text-sm text-slate-200">{{ $fmt($r['utilidad']) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">IVA s/ utilidad ({{ rtrim(rtrim(number_format($cab['pct_iva_utilidad'], 2, '.', ''), '0'), '.') }}%)</p>
                <p class="mt-0.5 font-mono text-sm text-slate-200">{{ $fmt($r['iva_utilidad']) }}</p>
            </div>
            <div class="lg:border-l lg:border-white/10 lg:pl-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Total venta</p>
                <p class="mt-0.5 font-mono text-lg font-bold text-white">{{ $fmt($r['total']) }}</p>
            </div>
        </div>
    </div>
</div>
