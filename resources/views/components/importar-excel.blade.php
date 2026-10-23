@props([
    'modulo' => 'materiales',
    'mostrar' => false,
    'resultado' => null,
    'importando' => false,
])

@php
    $info = config('importaciones.'.$modulo, config('importaciones.materiales'));
@endphp

<div>
    <button type="button" wire:click="abrirImport"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-[0.98] sm:w-auto">
        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 7.5 7.5 12M12 7.5V21"/>
        </svg>
        Subir Excel
    </button>

    @if ($mostrar)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 backdrop-blur-sm sm:items-center">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Importar {{ mb_strtolower($info['titulo']) }} desde Excel</h3>
                        <p class="mt-1 text-sm text-slate-500">Sube un archivo .xlsx, .xls o .csv (máx. 10 MB).</p>
                    </div>
                    <button type="button" wire:click="cerrarImport"
                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-700">Formato esperado</p>
                        <p class="mt-1 break-words text-xs text-slate-500">
                            Columnas: {{ implode(' · ', $info['columnas']) }}
                        </p>
                        <a href="{{ route('catalogos.plantilla', ['modulo' => $modulo]) }}"
                           class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-600 hover:text-amber-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5 7.5 12M12 16.5V3"/>
                            </svg>
                            Descargar plantilla
                        </a>
                        <p class="mt-2 text-xs text-slate-500">{{ $info['nota'] }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Archivo Excel</label>
                        <input type="file" wire:model="archivoImport" accept=".xlsx,.xls,.csv"
                               class="campo cursor-pointer file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
                        <div wire:loading wire:target="archivoImport" class="mt-1 text-xs text-slate-500">Cargando archivo...</div>
                        @error('archivoImport') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($resultado)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="rounded-lg bg-emerald-50 py-2">
                                    <p class="text-xl font-bold text-emerald-700">{{ $resultado['creados'] }}</p>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700/70">Creados</p>
                                </div>
                                <div class="rounded-lg bg-sky-50 py-2">
                                    <p class="text-xl font-bold text-sky-700">{{ $resultado['actualizados'] }}</p>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-700/70">Actualizados</p>
                                </div>
                                <div class="rounded-lg bg-amber-50 py-2">
                                    <p class="text-xl font-bold text-amber-700">{{ count($resultado['errores']) }}</p>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700/70">Con error</p>
                                </div>
                            </div>

                            @if (count($resultado['errores']))
                                <div class="mt-3 max-h-44 overflow-y-auto rounded-lg border border-red-100">
                                    <table class="min-w-full text-left text-xs">
                                        <thead class="sticky top-0 bg-red-50 text-red-700">
                                            <tr>
                                                <th class="px-3 py-2 font-semibold">Fila</th>
                                                <th class="px-3 py-2 font-semibold">Detalle</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-red-50">
                                            @foreach ($resultado['errores'] as $error)
                                                <tr>
                                                    <td class="px-3 py-1.5 font-mono text-red-600">{{ $error['fila'] ?: '—' }}</td>
                                                    <td class="px-3 py-1.5 text-slate-600">{{ $error['mensaje'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="mt-3 text-center text-sm font-medium text-emerald-700">¡Todo importado sin errores!</p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cerrarImport"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-200">
                        Cerrar
                    </button>
                    <button type="button" wire:click="importar" wire:loading.attr="disabled" wire:target="importar,archivoImport"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/30 transition hover:bg-emerald-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="importar">Importar</span>
                        <span wire:loading wire:target="importar">Importando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
