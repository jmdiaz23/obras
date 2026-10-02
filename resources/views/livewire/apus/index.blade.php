<div class="space-y-5">
    @if (session('exito'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3500)" x-show="show"
             class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('exito') }}
        </div>
    @endif

    {{-- Encabezado --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Análisis de Precios Unitarios</h2>
            <p class="text-sm text-slate-500">El costo directo de cada actividad sale de aquí.</p>
        </div>
        <button wire:click="abrirModal"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 active:scale-[0.98]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nuevo APU
        </button>
    </div>

    {{-- Búsqueda --}}
    <div class="relative max-w-md">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar por código o actividad..."
               class="block w-full rounded-xl border-slate-300 pl-10 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
    </div>

    {{-- Tabla escritorio --}}
    <div class="hidden overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Código</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Actividad</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Und.</th>
                    <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Costo directo</th>
                    <th class="relative px-6 py-3.5"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($apus as $fila)
                    @php($apu = $fila['modelo'])
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 font-mono text-xs font-bold text-amber-700">{{ $apu->codigo ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $apu->descripcion }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $apu->materiales->count() }} materiales · {{ $apu->manoObra->count() }} cargos · {{ $apu->equipos->count() }} equipos</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $apu->unidad }}</td>
                        <td class="px-6 py-4 text-right font-mono text-sm font-semibold text-slate-900">$ {{ number_format((float) $fila['costo_directo'], 2, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                            <a href="{{ route('apus.editar', $apu) }}" wire:navigate class="font-semibold text-amber-600 hover:text-amber-700">Editar</a>
                            @if ($confirmarEliminarId === $apu->id)
                                <span class="ml-3 text-xs text-slate-500">¿Eliminar?</span>
                                <button wire:click="eliminar" wire:loading.attr="disabled" class="ml-1 font-semibold text-red-600 hover:text-red-700">Sí</button>
                                <button wire:click="cancelarEliminar" class="ml-2 text-slate-400 hover:text-slate-600">No</button>
                            @else
                                <button wire:click="pedirConfirmarEliminar({{ $apu->id }})" class="ml-3 font-semibold text-red-600 hover:text-red-700">Eliminar</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">
                        No hay APUs{{ trim($buscar) ? ' que coincidan con la búsqueda' : ' todavía' }}.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tarjetas móvil --}}
    <div class="space-y-3 md:hidden">
        @forelse ($apus as $fila)
            @php($apu = $fila['modelo'])
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $apu->descripcion }}</p>
                        <p class="mt-0.5 font-mono text-xs text-amber-700">{{ $apu->codigo ?? '' }} · und. {{ $apu->unidad }}</p>
                    </div>
                    <div class="shrink-0 rounded-xl bg-slate-900 px-2.5 py-1.5 font-mono text-xs font-bold text-white">$ {{ number_format((float) $fila['costo_directo'], 0, ',', '.') }}</div>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ $apu->materiales->count() }} mat · {{ $apu->manoObra->count() }} cargos · {{ $apu->equipos->count() }} equipos</p>
                <div class="mt-3 flex gap-4 border-t border-slate-100 pt-3 text-sm">
                    <a href="{{ route('apus.editar', $apu) }}" wire:navigate class="font-semibold text-amber-600">Editar</a>
                    @if ($confirmarEliminarId === $apu->id)
                        <span class="text-slate-500">¿Eliminar?</span>
                        <button wire:click="eliminar" class="font-semibold text-red-600">Sí</button>
                        <button wire:click="cancelarEliminar" class="text-slate-400">No</button>
                    @else
                        <button wire:click="pedirConfirmarEliminar({{ $apu->id }})" class="font-semibold text-red-600">Eliminar</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-400 ring-1 ring-slate-200">No hay APUs aún.</div>
        @endforelse
    </div>

    {{ $apus->links() }}

    {{-- Modal creación rápida --}}
    <x-form.modal :open="$mostrarModal" titulo="Nuevo APU">
        <form class="space-y-4" wire:submit="crear">
            <x-form.input label="Código (opcional, se autogenera)" name="form.codigo" wire:model="form.codigo" placeholder="APU-001" />
            <x-form.input label="Actividad" name="form.descripcion" wire:model="form.descripcion" required placeholder="CONCRETO DE 3000 PSI EN ZAPATAS" />
            <div>
                <label for="form.unidad" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Unidad <span class="text-red-500">*</span>
                </label>
                <select id="form.unidad" wire:model="form.unidad"
                        class="block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                    @foreach (config('obras.unidades') as $valor => $texto)
                        <option value="{{ $valor }}" @selected($form['unidad'] === $valor)>{{ $valor }}</option>
                    @endforeach
                </select>
                @error('form.unidad') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="cerrarModal" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600 active:scale-[0.98]">
                    Crear y editar
                </button>
            </div>
        </form>
    </x-form.modal>
</div>
