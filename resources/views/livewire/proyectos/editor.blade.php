@php
    $r = $calculo['resumen'];
    $fmt = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.');
@endphp

<div class="mx-auto max-w-6xl space-y-5">
    {{-- Volver --}}
    <div>
        <a href="{{ route('proyectos.index') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-slate-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Volver a proyectos
        </a>
    </div>

    {{-- Datos generales --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-form.input label="Nombre del proyecto" name="cab.nombre" wire:model.lazy="cab.nombre" wire:change="guardarDatos" required />
            <x-form.input label="Cliente" name="cab.cliente" wire:model.lazy="cab.cliente" wire:change="guardarDatos" placeholder="Nombre del cliente" />
            <x-form.input label="Ubicación" name="cab.ubicacion" wire:model.lazy="cab.ubicacion" wire:change="guardarDatos" placeholder="Ciudad / barrio" />
            <div>
                <label for="cab.estado" class="mb-1.5 block text-sm font-medium text-slate-700">Estado</label>
                <select id="cab.estado" wire:model.lazy="cab.estado" wire:change="guardarDatos"
                        class="block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                    @foreach ($estados as $valor => $texto)
                        <option value="{{ $valor }}" @selected($cab['estado'] === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
                @error('cab.estado') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <p class="mb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Porcentajes AIU de este proyecto</p>
                <div class="grid grid-cols-3 gap-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Administración %</span>
                        <input type="number" step="any" min="0" max="100" wire:model.lazy="cab.pct_administracion" wire:change="guardarDatos"
                               class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Imprevistos %</span>
                        <input type="number" step="any" min="0" max="100" wire:model.lazy="cab.pct_imprevistos" wire:change="guardarDatos"
                               class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Utilidad %</span>
                        <input type="number" step="any" min="0" max="100" wire:model.lazy="cab.pct_utilidad" wire:change="guardarDatos"
                               class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Presupuesto --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3.5">
            <h3 class="text-sm font-bold tracking-wide text-slate-700">PRESUPUESTO GENERAL</h3>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500">Vr. unitario = costo directo del APU · el AIU se aplica sobre el subtotal</span>
                <a href="{{ route('proyectos.exportar', $proyecto->id) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Exportar Excel
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="w-16 px-4 py-2.5">Ítem</th>
                        <th class="px-4 py-2.5">Descripción</th>
                        <th class="w-16 px-3 py-2.5">Und.</th>
                        <th class="w-28 px-3 py-2.5 text-right">Cantidad</th>
                        <th class="w-32 px-3 py-2.5 text-right">Vr. unitario</th>
                        <th class="w-36 px-3 py-2.5 text-right">Vr. total</th>
                        <th class="w-24 px-3 py-2.5 text-right">Días</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($calculo['lineas'] as $linea)
                        @if ($linea['es_capitulo'])
                            <tr wire:key="cap{{ $linea['id'] }}" class="bg-slate-50/80">
                                <td class="px-4 py-2.5 font-mono text-xs font-bold text-amber-600">{{ $linea['item'] }}</td>
                                <td colspan="6" class="px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-600">{{ $linea['descripcion'] ?: 'Capítulo sin título' }}</td>
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
                                           class="w-full rounded-lg border-slate-200 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                </td>
                                <td class="px-3 py-2.5 text-right font-mono text-slate-500">{{ number_format((float) $linea['vr_unitario'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2.5 text-right font-mono font-semibold text-slate-900">{{ number_format((float) $linea['vr_total'], 2, ',', '.') }}</td>
                                <td class="px-3 py-2.5 text-right font-mono text-slate-500">
                                    {{ $linea['duracion'] !== null ? number_format((float) $linea['duracion'], 2, ',', '.') : '—' }}
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
                        <tr><td colspan="8" class="px-5 py-10 text-center text-sm text-slate-400">
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
                            class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
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
                               class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                        @error('capitulo.item') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="min-w-[220px] flex-1">
                        <label for="capitulo.titulo" class="mb-1 block text-xs font-semibold text-slate-600">Título</label>
                        <input id="capitulo.titulo" type="text" wire:model="capitulo.titulo" placeholder="OBRAS PRELIMINARES"
                               class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                        @error('capitulo.titulo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600">Guardar capítulo</button>
                    <button type="button" wire:click="$set('mostrarCapitulo', false)" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 hover:bg-white/70">Cancelar</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Panel de resumen AIU --}}
    <div class="sticky bottom-4 rounded-2xl bg-slate-900 p-5 shadow-xl shadow-slate-900/25">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
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
            <div class="lg:border-l lg:border-white/10 lg:pl-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Total venta</p>
                <p class="mt-0.5 font-mono text-lg font-bold text-white">{{ $fmt($r['total']) }}</p>
            </div>
        </div>
    </div>
</div>
