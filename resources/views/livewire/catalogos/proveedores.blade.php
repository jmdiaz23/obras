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
            <h2 class="text-lg font-bold text-slate-900">Proveedores</h2>
            <p class="text-sm text-slate-500">Empresas donde compras materiales y alquilas equipos.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <x-importar-excel modulo="proveedores"
                :mostrar="$mostrarImport"
                :resultado="$resultadoImport"
                :importando="$importando" />
            <button wire:click="crear"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 active:scale-[0.98]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nuevo proveedor
            </button>
        </div>
    </div>

    {{-- Búsqueda --}}
    <div class="relative max-w-md">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar por nombre o contacto..."
               class="campo pl-10">
    </div>

    {{-- Tabla escritorio --}}
    <div class="hidden overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Proveedor</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Contacto</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Catálogo</th>
                    <th class="relative px-6 py-3.5"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($proveedores as $proveedor)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $proveedor->nombre }}</p>
                            @if ($proveedor->email)
                                <p class="text-xs text-slate-500">{{ $proveedor->email }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($proveedor->contacto || $proveedor->telefono)
                                <p class="text-sm text-slate-700">{{ $proveedor->contacto }}</p>
                                <p class="text-xs text-slate-500">{{ $proveedor->telefono }}</p>
                            @else
                                <span class="text-xs text-slate-400">Sin contacto</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $proveedor->materiales_count }} materiales</span>
                            <span class="ml-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $proveedor->equipos_count }} equipos</span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                            <button wire:click="editar({{ $proveedor->id }})" class="font-semibold text-amber-600 hover:text-amber-700">Editar</button>
                            @if ($confirmarEliminarId === $proveedor->id)
                                <span class="ml-3 text-xs text-slate-500">¿Eliminar?</span>
                                <button wire:click="eliminar" wire:loading.attr="disabled" class="ml-1 font-semibold text-red-600 hover:text-red-700">Sí</button>
                                <button wire:click="cancelarEliminar" class="ml-2 text-slate-400 hover:text-slate-600">No</button>
                            @else
                                <button wire:click="pedirConfirmarEliminar({{ $proveedor->id }})" class="ml-3 font-semibold text-red-600 hover:text-red-700">Eliminar</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400">No hay proveedores{{ trim($buscar) ? ' que coincidan con la búsqueda' : '' }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tarjetas móvil --}}
    <div class="space-y-3 md:hidden">
        @forelse ($proveedores as $proveedor)
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $proveedor->nombre }}</p>
                        <p class="text-xs text-slate-500">{{ $proveedor->contacto ?: 'Sin contacto' }} {{ $proveedor->telefono ? '· '.$proveedor->telefono : '' }}</p>
                    </div>
                    <div class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $proveedor->materiales_count + $proveedor->equipos_count }} ítems</div>
                </div>
                <div class="mt-3 flex gap-4 border-t border-slate-100 pt-3 text-sm">
                    <button wire:click="editar({{ $proveedor->id }})" class="font-semibold text-amber-600">Editar</button>
                    @if ($confirmarEliminarId === $proveedor->id)
                        <span class="text-slate-500">¿Eliminar?</span>
                        <button wire:click="eliminar" class="font-semibold text-red-600">Sí</button>
                        <button wire:click="cancelarEliminar" class="text-slate-400">No</button>
                    @else
                        <button wire:click="pedirConfirmarEliminar({{ $proveedor->id }})" class="font-semibold text-red-600">Eliminar</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-400 ring-1 ring-slate-200">No hay proveedores aún.</div>
        @endforelse
    </div>

    {{ $proveedores->links() }}

    {{-- Modal --}}
    <x-form.modal :open="$mostrarModal" titulo="{{ $editando ? 'Editar proveedor' : 'Nuevo proveedor' }}">
        <form class="space-y-4" wire:submit="guardar">
            <x-form.input label="Nombre de la empresa" name="form.nombre" wire:model="form.nombre" required placeholder="Homecenter S.A." />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input label="Persona de contacto" name="form.contacto" wire:model="form.contacto" placeholder="Nombre del vendedor" />
                <x-form.input label="Teléfono" name="form.telefono" wire:model="form.telefono" placeholder="300 123 4567" />
            </div>
            <x-form.input label="Correo" name="form.email" type="email" wire:model="form.email" placeholder="ventas@proveedor.com" />
            <div>
                <label for="form.notas" class="mb-1.5 block text-sm font-medium text-slate-700">Notas</label>
                <textarea id="form.notas" rows="2" wire:model="form.notas"
                          class="campo"
                          placeholder="Condiciones, descuentos, tiempos de entrega..."></textarea>
                @error('form.notas') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="cerrarModal" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600 active:scale-[0.98]">
                    {{ $editando ? 'Guardar cambios' : 'Crear proveedor' }}
                </button>
            </div>
        </form>
    </x-form.modal>
</div>
