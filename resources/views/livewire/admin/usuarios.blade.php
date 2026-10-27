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
            <h2 class="text-lg font-bold text-slate-900">Usuarios</h2>
            <p class="text-sm text-slate-500">Cuentas de acceso a las empresas de la plataforma.</p>
        </div>
        <button wire:click="abrirModal"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 active:scale-[0.98]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Crear usuario
        </button>
    </div>

    {{-- Búsqueda --}}
    <div class="relative max-w-md">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar por nombre, correo o empresa..."
               class="campo pl-10">
    </div>

    {{-- Tabla escritorio --}}
    <div class="hidden overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Usuario</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Empresa</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Rol</th>
                    <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Creado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($usuarios as $usuario)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">
                                    {{ strtoupper(substr($usuario->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $usuario->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $usuario->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $usuario->empresa?->nombre ?? 'Plataforma' }}</td>
                        <td class="px-6 py-4">
                            @if ($usuario->esSuperAdmin())
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Super administrador</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-600">{{ $roles[$usuario->role] ?? $usuario->role }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-slate-500">{{ $usuario->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400">
                            No hay usuarios{{ trim($buscar) ? ' que coincidan con la búsqueda' : ' todavía' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tarjetas móvil --}}
    <div class="space-y-3 md:hidden">
        @forelse ($usuarios as $usuario)
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">
                        {{ strtoupper(substr($usuario->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $usuario->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $usuario->email }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $usuario->empresa?->nombre ?? 'Plataforma' }}</p>
                    </div>
                    @if ($usuario->esSuperAdmin())
                        <span class="shrink-0 rounded-full bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-800">Plataforma</span>
                    @else
                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600">{{ $roles[$usuario->role] ?? $usuario->role }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-400 ring-1 ring-slate-200">No hay usuarios aún.</div>
        @endforelse
    </div>

    {{ $usuarios->links() }}

    <x-form.modal titulo="Crear usuario" max-width="max-w-xl">
        <form class="space-y-4" wire:submit="crear">
            <div>
                <label for="form.empresa_id" class="mb-2 block text-sm font-medium text-slate-700">Empresa <span class="text-red-500">*</span></label>
                <select id="form.empresa_id" wire:model="form.empresa_id"
                        class="campo @error('form.empresa_id') campo-error @enderror">
                    <option value="">Selecciona una empresa</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
                @error('form.empresa_id') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <x-form.input label="Nombre completo" name="form.name" wire:model="form.name" required placeholder="María Rodríguez" />
            <x-form.input label="Correo electrónico" name="form.email" type="email" wire:model="form.email" required placeholder="usuario@empresa.com" />

            <div>
                <label for="form.role" class="mb-2 block text-sm font-medium text-slate-700">Rol <span class="text-red-500">*</span></label>
                <select id="form.role" wire:model="form.role"
                        class="campo @error('form.role') campo-error @enderror">
                    @foreach ($roles as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('form.role') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input label="Contraseña inicial" name="form.password" type="password" wire:model="form.password" required hint="Mínimo 8 caracteres." />
                <x-form.input label="Confirmar contraseña" name="form.password_confirmation" type="password" wire:model="form.password_confirmation" required />
            </div>

            <p class="rounded-xl bg-slate-50 px-3.5 py-2.5 text-xs leading-5 text-slate-500 ring-1 ring-slate-200">
                Las cuentas de <strong class="font-semibold text-slate-700">super administrador</strong> solo se crean por consola.
            </p>

            <div class="flex justify-end gap-3 pt-1">
                <button type="button" wire:click="cerrarModal" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                <button type="submit" wire:loading.attr="disabled"
                        class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600 active:scale-[0.98] disabled:opacity-60">
                    <span wire:loading.remove wire:target="crear">Crear usuario</span>
                    <span wire:loading wire:target="crear">Creando...</span>
                </button>
            </div>
        </form>
    </x-form.modal>
</div>
