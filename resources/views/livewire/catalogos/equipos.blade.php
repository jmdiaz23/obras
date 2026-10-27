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
            <h2 class="text-lg font-bold text-slate-900">Alquiler de equipos</h2>
            <p class="text-sm text-slate-500">Tarifa por día y unidad de alquiler (hora, día, m², m³) que entrarán al costo de tus APU.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <x-importar-excel modulo="equipos"
                :mostrar="$mostrarImport"
                :resultado="$resultadoImport"
                :importando="$importando" />
            <button wire:click="crear"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 active:scale-[0.98]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nuevo equipo
            </button>
        </div>
    </div>

    <div class="relative max-w-md">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar por código o descripción..."
               class="campo pl-10">
    </div>

    {{-- Tabla escritorio --}}
    <div class="hidden overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Código</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Equipo</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tipo</th>
                    <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Tarifa / día</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Alquiler</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Proveedor</th>
                    <th class="relative px-6 py-3.5"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($equipos as $equipo)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <span class="rounded-lg bg-slate-900 px-2 py-1 font-mono text-xs font-bold text-white">{{ $equipo->codigo }}</span>
                        </td>
                        <td class="max-w-xs truncate px-6 py-4 text-sm font-medium text-slate-900">{{ $equipo->descripcion }}</td>
                        <td class="px-6 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $equipo->tipo ?? '—' }}</span></td>
                        <td class="px-6 py-4 text-right text-sm font-bold tabular-nums text-slate-900">$ {{ number_format($equipo->tarifa_dia, 2, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                {{ config('obras.unidades_equipo.'.$equipo->unidad_alquiler, $equipo->unidad_alquiler) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">{{ $equipo->proveedor?->nombre ?? '—' }}</td>
                        <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                            <button wire:click="editar({{ $equipo->id }})" class="font-semibold text-amber-600 hover:text-amber-700">Editar</button>
                            @if ($confirmarEliminarId === $equipo->id)
                                <span class="ml-3 text-xs text-slate-500">¿Eliminar?</span>
                                <button wire:click="eliminar" class="ml-1 font-semibold text-red-600 hover:text-red-700">Sí</button>
                                <button wire:click="cancelarEliminar" class="ml-2 text-slate-400 hover:text-slate-600">No</button>
                            @else
                                <button wire:click="pedirConfirmarEliminar({{ $equipo->id }})" class="ml-3 font-semibold text-red-600 hover:text-red-700">Eliminar</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">
                        No hay equipos aún. Registra el trompo, los andamios, la rana...
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tarjetas móvil --}}
    <div class="space-y-3 md:hidden">
        @forelse ($equipos as $equipo)
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="shrink-0 rounded-lg bg-slate-900 px-2 py-1 font-mono text-[11px] font-bold text-white">{{ $equipo->codigo }}</span>
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $equipo->descripcion }}</p>
                    </div>
                    <p class="shrink-0 text-sm font-bold tabular-nums text-slate-900">$ {{ number_format($equipo->tarifa_dia, 0, ',', '.') }}/día</p>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    @if ($equipo->tipo)<span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-600">{{ $equipo->tipo }}</span>@endif
                    <span class="rounded-full bg-amber-50 px-2 py-0.5 font-semibold text-amber-700">
                        Alquiler: {{ $equipo->unidad_alquiler }}
                    </span>
                    {{ $equipo->proveedor?->nombre ?? '' }}
                </div>
                <div class="mt-3 flex gap-4 border-t border-slate-100 pt-3 text-sm">
                    <button wire:click="editar({{ $equipo->id }})" class="font-semibold text-amber-600">Editar</button>
                    @if ($confirmarEliminarId === $equipo->id)
                        <span class="text-slate-500">¿Eliminar?</span>
                        <button wire:click="eliminar" class="font-semibold text-red-600">Sí</button>
                        <button wire:click="cancelarEliminar" class="text-slate-400">No</button>
                    @else
                        <button wire:click="pedirConfirmarEliminar({{ $equipo->id }})" class="font-semibold text-red-600">Eliminar</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-400 ring-1 ring-slate-200">No hay equipos aún.</div>
        @endforelse
    </div>

    {{ $equipos->links() }}

    <x-form.modal :open="$mostrarModal" titulo="{{ $editando ? 'Editar equipo' : 'Nuevo equipo' }}">
        <form class="space-y-4" wire:submit="guardar">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input label="Código" name="form.codigo" wire:model="form.codigo" required placeholder="E1" hint="Ej: E1, E2..." />
                <x-form.input label="Tarifa por día" name="form.tarifa_dia" type="number" step="0.01" min="0" wire:model="form.tarifa_dia" required placeholder="39900" />
                <div>
                    <label for="form.unidad_alquiler" class="mb-1.5 block text-sm font-medium text-slate-700">Unidad de alquiler</label>
                    <select id="form.unidad_alquiler" wire:model="form.unidad_alquiler" class="campo">
                        @foreach (config('obras.unidades_equipo') as $clave => $etiqueta)
                            <option value="{{ $clave }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Se sugiere al agregar el equipo a un APU.</p>
                    @error('form.unidad_alquiler') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-form.input label="Tipo" name="form.tipo" wire:model="form.tipo" placeholder="Rana / Gasolina..." />
            </div>
            <x-form.input label="Descripción" name="form.descripcion" wire:model="form.descripcion" required placeholder="Vibrocompactador manual" />
            <div>
                <label for="form.proveedor_id" class="mb-1.5 block text-sm font-medium text-slate-700">Proveedor</label>
                <select id="form.proveedor_id" wire:model="form.proveedor_id"
                        class="campo">
                    <option value="">— Sin proveedor —</option>
                    @foreach (\App\Models\Proveedor::orderBy('nombre')->get(['id', 'nombre']) as $prov)
                        <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                    @endforeach
                </select>
                @error('form.proveedor_id') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="cerrarModal" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600 active:scale-[0.98]">
                    {{ $editando ? 'Guardar cambios' : 'Crear equipo' }}
                </button>
            </div>
        </form>
    </x-form.modal>
</div>
