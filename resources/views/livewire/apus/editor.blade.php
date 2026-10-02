@php
    $t = $calculo['totales'];
    $fmt = fn ($v) => '$ '.number_format((float) $v, 2, ',', '.');
@endphp

<div class="mx-auto max-w-6xl space-y-5">
    {{-- Volver --}}
    <div>
        <a href="{{ route('apus.index') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-slate-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Volver a APUs
        </a>
    </div>

    {{-- Cabecera del APU --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-form.input label="Código" name="cab.codigo" wire:model.lazy="cab.codigo" wire:change="guardarCabecera" placeholder="APU-001" />
            <div class="sm:col-span-2">
                <label for="cab.descripcion" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Actividad <span class="text-red-500">*</span>
                </label>
                <input type="text" id="cab.descripcion" wire:model.lazy="cab.descripcion" wire:change="guardarCabecera"
                       class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm @error('cab.descripcion') border-red-400 bg-red-50 @enderror">
                @error('cab.descripcion') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="cab.unidad" class="mb-1.5 block text-sm font-medium text-slate-700">Unidad <span class="text-red-500">*</span></label>
                <select id="cab.unidad" wire:model.lazy="cab.unidad" wire:change="guardarCabecera"
                        class="block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                    @foreach (config('obras.unidades') as $valor => $texto)
                        <option value="{{ $valor }}" @selected($cab['unidad'] === $valor)>{{ $valor }}</option>
                    @endforeach
                </select>
            </div>
            <x-form.input label="% Herramientas menores" name="cab.pct_herramientas" type="number" step="any"
                          wire:model.lazy="cab.pct_herramientas" wire:change="guardarCabecera"
                          hint="Se aplica sobre el costo de mano de obra." />
            <x-form.input label="Cuadrilla" name="cab.cuadrilla" wire:model.lazy="cab.cuadrilla" wire:change="guardarCabecera"
                          placeholder="O+2A" hint="Ej: 1 oficial + 2 ayudantes." />
            <div>
                <label for="cab.dias_por_unidad" class="mb-1.5 block text-sm font-medium text-slate-700">Días por unidad</label>
                <input type="number" step="any" min="0" id="cab.dias_por_unidad"
                       wire:model.lazy="cab.dias_por_unidad" wire:change="guardarCabecera" placeholder="0.05875"
                       class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm @error('cab.dias_por_unidad') border-red-400 bg-red-50 @enderror">
                @error('cab.dias_por_unidad') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-slate-400">Días que tarda la cuadrilla completa por unidad. Se usa para calcular duraciones en los proyectos.</p>
            </div>
            <div class="lg:col-span-2">
                <label for="cab.notas" class="mb-1.5 block text-sm font-medium text-slate-700">Notas</label>
                <input type="text" id="cab.notas" wire:model.lazy="cab.notas" wire:change="guardarCabecera"
                       placeholder="Ej: incluye transporte hasta 10 km"
                       class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
            </div>
        </div>
    </div>

    {{-- SECCIÓN A: MATERIALES --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3.5">
            <h3 class="text-sm font-bold tracking-wide text-slate-700">A. MATERIALES</h3>
            <span class="font-mono text-sm font-bold text-slate-900">{{ $fmt($t['materiales']) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-2.5">Descripción</th>
                        <th class="w-16 px-3 py-2.5">Und.</th>
                        <th class="w-28 px-3 py-2.5 text-right">Cantidad</th>
                        <th class="w-24 px-3 py-2.5 text-right">% Desp.</th>
                        <th class="w-32 px-3 py-2.5 text-right">Precio</th>
                        <th class="w-36 px-3 py-2.5 text-right">Total</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($calculo['materiales'] as $linea)
                        <tr wire:key="mat{{ $linea['id'] }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-2.5 font-medium text-slate-700">{{ $linea['descripcion'] }}</td>
                            <td class="px-3 py-2.5 text-slate-500">{{ $linea['unidad'] }}</td>
                            <td class="px-3 py-2.5">
                                <input type="number" step="any" min="0" wire:model.live="linM.{{ $linea['id'] }}.cantidad"
                                       class="w-full rounded-lg border-slate-200 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="number" step="any" min="0" max="100" wire:model.live="linM.{{ $linea['id'] }}.desperdicio"
                                       class="w-full rounded-lg border-slate-200 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-500">{{ number_format((float) $linea['precio'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right font-mono font-semibold text-slate-900">{{ number_format((float) $linea['total'], 2, ',', '.') }}</td>
                            <td class="pr-3 text-right">
                                <button wire:click="quitarLinea('material', {{ $linea['id'] }})" title="Quitar"
                                        class="rounded p-1 text-slate-300 transition hover:bg-red-50 hover:text-red-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">Agrega materiales de tu lista de precios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-end gap-3 border-t border-slate-100 px-5 py-4">
            <div class="min-w-[240px] flex-1">
                <label for="sel-material" class="mb-1 block text-xs font-semibold text-slate-500">Agregar material</label>
                <select id="sel-material" wire:model="selMaterial"
                        class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">Selecciona un material...</option>
                    @foreach ($materialesDisponibles as $material)
                        <option value="{{ $material->id }}">{{ $material->codigo }} — {{ Str::limit($material->descripcion, 60) }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="agregarMaterial" @disabled(!$selMaterial)
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition enabled:hover:bg-slate-800 disabled:opacity-40">
                Agregar
            </button>
        </div>
    </div>

    {{-- SECCIÓN B: MANO DE OBRA --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3.5">
            <h3 class="text-sm font-bold tracking-wide text-slate-700">B. MANO DE OBRA</h3>
            <span class="font-mono text-sm font-bold text-slate-900">{{ $fmt($t['mano_obra']) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-2.5">Cargo</th>
                        <th class="w-32 px-3 py-2.5 text-right">Jornal</th>
                        <th class="w-32 px-3 py-2.5 text-right">Jornal + prest.</th>
                        <th class="w-32 px-3 py-2.5 text-right">Días/und.</th>
                        <th class="w-36 px-3 py-2.5 text-right">Total</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($calculo['manoObra'] as $linea)
                        <tr wire:key="mo{{ $linea['id'] }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-2.5 font-medium text-slate-700">{{ $linea['descripcion'] }}</td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-500">{{ number_format((float) $linea['jornal'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-600">{{ number_format((float) $linea['jornal_prestaciones'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2.5">
                                <input type="number" step="any" min="0" wire:model.live="linMO.{{ $linea['id'] }}.dias"
                                       class="w-full rounded-lg border-slate-200 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-semibold text-slate-900">{{ number_format((float) $linea['total'], 2, ',', '.') }}</td>
                            <td class="pr-3 text-right">
                                <button wire:click="quitarLinea('mano_obra', {{ $linea['id'] }})" title="Quitar"
                                        class="rounded p-1 text-slate-300 transition hover:bg-red-50 hover:text-red-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">Agrega cargos de tu catálogo de mano de obra.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-end gap-3 border-t border-slate-100 px-5 py-4">
            <div class="min-w-[240px] flex-1">
                <label for="sel-cargo" class="mb-1 block text-xs font-semibold text-slate-500">Agregar cargo</label>
                <select id="sel-cargo" wire:model="selCargo"
                        class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">Selecciona un cargo...</option>
                    @foreach ($cargosDisponibles as $cargo)
                        <option value="{{ $cargo->id }}">{{ $cargo->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="agregarCargo" @disabled(!$selCargo)
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition enabled:hover:bg-slate-800 disabled:opacity-40">
                Agregar
            </button>
        </div>
    </div>

    {{-- SECCIÓN C: EQUIPOS Y HERRAMIENTAS --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3.5">
            <h3 class="text-sm font-bold tracking-wide text-slate-700">C. EQUIPOS Y HERRAMIENTAS</h3>
            <span class="font-mono text-sm font-bold text-slate-900">{{ $fmt($t['equipos']) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-2.5">Equipo</th>
                        <th class="w-32 px-3 py-2.5 text-right">Tarifa/hora</th>
                        <th class="w-32 px-3 py-2.5 text-right">Horas/und.</th>
                        <th class="w-36 px-3 py-2.5 text-right">Total</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($calculo['equipos'] as $linea)
                        <tr wire:key="eq{{ $linea['id'] }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-2.5 font-medium text-slate-700">{{ $linea['descripcion'] }}</td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-500">{{ number_format((float) $linea['tarifa_hora'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2.5">
                                <input type="number" step="any" min="0" wire:model.live="linE.{{ $linea['id'] }}.horas"
                                       class="w-full rounded-lg border-slate-200 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-semibold text-slate-900">{{ number_format((float) $linea['total'], 2, ',', '.') }}</td>
                            <td class="pr-3 text-right">
                                <button wire:click="quitarLinea('equipo', {{ $linea['id'] }})" title="Quitar"
                                        class="rounded p-1 text-slate-300 transition hover:bg-red-50 hover:text-red-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">Agrega equipos de tu catálogo de alquiler.</td></tr>
                    @endforelse
                    <tr class="bg-slate-50/80">
                        <td colspan="3" class="px-5 py-2.5 text-slate-600">
                            Herramientas menores <span class="text-slate-400">({{ $cab['pct_herramientas'] }}% de la mano de obra)</span>
                        </td>
                        <td colspan="2" class="px-3 py-2.5 text-right font-mono font-semibold text-slate-700">{{ $fmt($t['herramientas']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-end gap-3 border-t border-slate-100 px-5 py-4">
            <div class="min-w-[240px] flex-1">
                <label for="sel-equipo" class="mb-1 block text-xs font-semibold text-slate-500">Agregar equipo</label>
                <select id="sel-equipo" wire:model="selEquipo"
                        class="block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    <option value="">Selecciona un equipo...</option>
                    @foreach ($equiposDisponibles as $equipo)
                        <option value="{{ $equipo->id }}">{{ $equipo->codigo }} — {{ Str::limit($equipo->descripcion, 55) }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="agregarEquipo" @disabled(!$selEquipo)
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition enabled:hover:bg-slate-800 disabled:opacity-40">
                Agregar
            </button>
        </div>
    </div>

    {{-- Panel de totales --}}
    <div class="sticky bottom-4 rounded-2xl bg-slate-900 p-5 shadow-xl shadow-slate-900/25">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Materiales</p>
                <p class="mt-0.5 font-mono text-sm text-slate-200">{{ $fmt($t['materiales']) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Mano de obra</p>
                <p class="mt-0.5 font-mono text-sm text-slate-200">{{ $fmt($t['mano_obra']) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Equipos y herram.</p>
                <p class="mt-0.5 font-mono text-sm text-slate-200">{{ $fmt($t['equipos']) }}</p>
            </div>
            <div class="col-span-2 sm:col-span-1 sm:border-l sm:border-white/10 sm:pl-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Costo directo</p>
                <p class="mt-0.5 font-mono text-lg font-bold text-white">{{ $fmt($t['costo_directo']) }}</p>
            </div>
        </div>
    </div>
</div>
