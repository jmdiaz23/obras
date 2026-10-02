<div class="space-y-5">
    @if (session('exito'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3500)" x-show="show"
             class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('exito') }}
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Mano de obra · Cargos</h2>
            <p class="text-sm text-slate-500">Jornales diarios; las prestaciones ({{ number_format($pctPrestaciones) }}%) se suman automáticamente en los APU.</p>
        </div>
        <button wire:click="crear"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 active:scale-[0.98]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nuevo cargo
        </button>
    </div>

    <div class="relative max-w-md">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar cargo..."
               class="block w-full rounded-xl border-slate-300 pl-10 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
    </div>

    {{-- Tabla escritorio --}}
    <div class="hidden overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Cargo</th>
                    <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Jornal día</th>
                    <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Jornal + prestaciones</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Notas</th>
                    <th class="relative px-6 py-3.5"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($cargos as $cargo)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ $cargo->nombre }}</td>
                        <td class="px-6 py-4 text-right text-sm tabular-nums text-slate-700">$ {{ number_format($cargo->jornal, 2, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm font-bold tabular-nums text-emerald-700">$ {{ number_format($cargo->jornal * (1 + $pctPrestaciones / 100), 2, ',', '.') }}</td>
                        <td class="max-w-xs truncate px-6 py-4 text-sm text-slate-500">{{ $cargo->descripcion ?? '—' }}</td>
                        <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                            <button wire:click="editar({{ $cargo->id }})" class="font-semibold text-amber-600 hover:text-amber-700">Editar</button>
                            @if ($confirmarEliminarId === $cargo->id)
                                <span class="ml-3 text-xs text-slate-500">¿Eliminar?</span>
                                <button wire:click="eliminar" class="ml-1 font-semibold text-red-600 hover:text-red-700">Sí</button>
                                <button wire:click="cancelarEliminar" class="ml-2 text-slate-400 hover:text-slate-600">No</button>
                            @else
                                <button wire:click="pedirConfirmarEliminar({{ $cargo->id }})" class="ml-3 font-semibold text-red-600 hover:text-red-700">Eliminar</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">
                        No hay cargos aún. Crea Oficial y Ayudante para empezar.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tarjetas móvil --}}
    <div class="space-y-3 md:hidden">
        @forelse ($cargos as $cargo)
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $cargo->nombre }}</p>
                        @if ($cargo->descripcion)
                            <p class="truncate text-xs text-slate-500">{{ $cargo->descripcion }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-bold tabular-nums text-slate-900">$ {{ number_format($cargo->jornal, 0, ',', '.') }}</p>
                        <p class="text-xs tabular-nums text-emerald-700">+prest. $ {{ number_format($cargo->jornal * (1 + $pctPrestaciones / 100), 0, ',', '.') }}</p>
                    </div>
                </div>
                <div class="mt-3 flex gap-4 border-t border-slate-100 pt-3 text-sm">
                    <button wire:click="editar({{ $cargo->id }})" class="font-semibold text-amber-600">Editar</button>
                    @if ($confirmarEliminarId === $cargo->id)
                        <span class="text-slate-500">¿Eliminar?</span>
                        <button wire:click="eliminar" class="font-semibold text-red-600">Sí</button>
                        <button wire:click="cancelarEliminar" class="text-slate-400">No</button>
                    @else
                        <button wire:click="pedirConfirmarEliminar({{ $cargo->id }})" class="font-semibold text-red-600">Eliminar</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-400 ring-1 ring-slate-200">No hay cargos aún.</div>
        @endforelse
    </div>

    {{ $cargos->links() }}

    <x-form.modal :open="$mostrarModal" titulo="{{ $editando ? 'Editar cargo' : 'Nuevo cargo' }}">
        <form class="space-y-4" wire:submit="guardar">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input label="Nombre del cargo" name="form.nombre" wire:model="form.nombre" required placeholder="Oficial / Ayudante" />
                <div>
                    <x-form.input label="Jornal por día" name="form.jornal" type="number" step="0.01" min="0" wire:model="form.jornal" required placeholder="30284" />
                    @if ($jornalConPrestaciones !== null)
                        <p class="mt-1.5 text-xs font-medium text-emerald-700">Con prestaciones: $ {{ number_format($jornalConPrestaciones, 2, ',', '.') }}/día</p>
                    @endif
                </div>
            </div>
            <x-form.input label="Notas" name="form.descripcion" wire:model="form.descripcion" placeholder="Especificaciones del cargo..." />
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="cerrarModal" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600 active:scale-[0.98]">
                    {{ $editando ? 'Guardar cambios' : 'Crear cargo' }}
                </button>
            </div>
        </form>
    </x-form.modal>
</div>
