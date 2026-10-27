<div class="space-y-5">
    @if (session('exito'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
             class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('exito') }}
        </div>
    @endif

    {{-- Encabezado --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Empresas</h2>
            <p class="text-sm text-slate-500">Alta de organizaciones y de su usuario propietario.</p>
        </div>
        <button wire:click="abrirModal"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 active:scale-[0.98]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nueva empresa
        </button>
    </div>

    {{-- Búsqueda --}}
    <div class="relative max-w-md">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar por nombre o NIT..."
               class="campo pl-10">
    </div>

    {{-- Tabla escritorio --}}
    <div class="hidden overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Empresa</th>
                    <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Usuarios</th>
                    <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Proyectos</th>
                    <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">AIU</th>
                    <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Creada</th>
                    <th class="relative px-6 py-3.5"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($empresas as $empresa)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $empresa->nombre }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $empresa->nit ?: 'Sin NIT' }}</p>
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-slate-600">{{ $empresa->users_count }}</td>
                        <td class="px-6 py-4 text-center text-sm text-slate-600">{{ $empresa->proyectos_count }}</td>
                        <td class="px-6 py-4 text-center text-sm text-slate-600">
                            {{ number_format((float) $empresa->pct_administracion + (float) $empresa->pct_imprevistos + (float) $empresa->pct_utilidad, 0) }} %
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-slate-500">{{ $empresa->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="editar({{ $empresa->id }})" class="text-sm font-semibold text-amber-600 hover:text-amber-700">Editar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-400">
                            No hay empresas{{ trim($buscar) ? ' que coincidan con la búsqueda' : ' todavía' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tarjetas móvil --}}
    <div class="space-y-3 md:hidden">
        @forelse ($empresas as $empresa)
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $empresa->nombre }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $empresa->nit ?: 'Sin NIT' }}</p>
                    </div>
                    <button wire:click="editar({{ $empresa->id }})" class="shrink-0 text-sm font-semibold text-amber-600">Editar</button>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <div><p class="font-bold text-slate-900">{{ $empresa->users_count }}</p><p>Usuarios</p></div>
                    <div><p class="font-bold text-slate-900">{{ $empresa->proyectos_count }}</p><p>Proyectos</p></div>
                    <div><p class="font-bold text-slate-900">{{ number_format((float) $empresa->pct_administracion + (float) $empresa->pct_imprevistos + (float) $empresa->pct_utilidad, 0) }} %</p><p>AIU</p></div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-400 ring-1 ring-slate-200">No hay empresas aún.</div>
        @endforelse
    </div>

    {{ $empresas->links() }}

    <x-form.modal titulo="{{ $editandoId ? 'Editar empresa' : 'Nueva empresa' }}" max-width="max-w-2xl">
        <form class="space-y-5" wire:submit="guardar">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form.input label="Nombre de la empresa" name="form.nombre" wire:model="form.nombre" required placeholder="Constructora El Ejemplo S.A.S." />
                </div>
                <x-form.input label="NIT" name="form.nit" wire:model="form.nit" placeholder="900.123.456-7" />
                <x-form.input label="Teléfono" name="form.telefono" wire:model="form.telefono" placeholder="300 123 4567" />
                <div class="sm:col-span-2">
                    <x-form.input label="Dirección" name="form.direccion" wire:model="form.direccion" placeholder="Ciudad, departamento" />
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-800">Porcentajes de AIU</p>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <x-form.input label="Administración %" name="form.pct_administracion" type="number" step="0.01" wire:model="form.pct_administracion" />
                    <x-form.input label="Imprevistos %" name="form.pct_imprevistos" type="number" step="0.01" wire:model="form.pct_imprevistos" />
                    <x-form.input label="Utilidad %" name="form.pct_utilidad" type="number" step="0.01" wire:model="form.pct_utilidad" />
                    <x-form.input label="Prestaciones %" name="form.pct_prestaciones" type="number" step="0.01" wire:model="form.pct_prestaciones" />
                </div>
            </div>

            @if ($editandoId === null)
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Propietario inicial</p>
                            <p class="mt-0.5 text-xs text-slate-500">Es la cuenta que administrará la empresa. Podrá iniciar sesión apenas guardes.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-form.input label="Nombre completo" name="form.owner_name" wire:model="form.owner_name" required placeholder="Juan Pérez" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-form.input label="Correo electrónico" name="form.owner_email" type="email" wire:model="form.owner_email" required placeholder="propietario@empresa.com" />
                        </div>
                        <x-form.input label="Contraseña inicial" name="form.owner_password" type="password" wire:model="form.owner_password" required hint="Mínimo 8 caracteres." />
                        <x-form.input label="Confirmar contraseña" name="form.owner_password_confirmation" type="password" wire:model="form.owner_password_confirmation" required />
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-3 pt-1">
                <button type="button" wire:click="cerrarModal" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" wire:loading.attr="disabled"
                        class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600 active:scale-[0.98] disabled:opacity-60">
                    <span wire:loading.remove wire:target="guardar">{{ $editandoId ? 'Guardar cambios' : 'Crear empresa' }}</span>
                    <span wire:loading wire:target="guardar">Guardando...</span>
                </button>
            </div>
        </form>
    </x-form.modal>
</div>
