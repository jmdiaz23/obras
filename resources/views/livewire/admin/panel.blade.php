<div class="space-y-6">
    {{-- Encabezado --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Administración global</p>
            <h2 class="mt-0.5 text-xl font-bold text-slate-900">Panel de plataforma</h2>
            <p class="mt-1 text-sm text-slate-500">Empresas, cuentas y proyectos de todo ObraOS.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.empresas') }}" wire:navigate
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 active:scale-[0.98]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva empresa
            </a>
            <a href="{{ route('admin.usuarios') }}" wire:navigate
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 active:scale-[0.98]">
                Crear usuario
            </a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">{{ $kpi['etiqueta'] }}</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $kpi['valor'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $kpi['detalle'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        {{-- Empresas --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h3 class="font-semibold text-slate-900">Empresas recientes</h3>
                    <p class="text-sm text-slate-500">Altas y actividad por organización.</p>
                </div>
                <a href="{{ route('admin.empresas') }}" wire:navigate class="text-sm font-semibold text-amber-600 transition hover:text-amber-700">Gestionar</a>
            </div>

            @if ($empresas->isEmpty())
                <div class="px-5 py-10 text-center sm:px-6">
                    <p class="text-sm text-slate-500">Aún no hay empresas registradas.</p>
                    <a href="{{ route('admin.empresas') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-amber-600 hover:text-amber-700">Crear la primera</a>
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($empresas as $empresa)
                        <li class="flex items-center gap-3 px-5 py-4 sm:px-6">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-sm font-bold text-amber-700">
                                {{ strtoupper(substr($empresa->nombre, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $empresa->nombre }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $empresa->nit ?: 'Sin NIT' }} · {{ $empresa->users_count }} {{ $empresa->users_count === 1 ? 'usuario' : 'usuarios' }} · {{ $empresa->proyectos_count }} {{ $empresa->proyectos_count === 1 ? 'proyecto' : 'proyectos' }}
                                </p>
                            </div>
                            <a href="{{ route('admin.empresas') }}" wire:navigate class="text-xs font-semibold text-amber-600 hover:text-amber-700">Ver</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Proyectos --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h3 class="font-semibold text-slate-900">Proyectos recientes</h3>
                <p class="text-sm text-slate-500">Últimos presupuestos creados en la plataforma.</p>
            </div>

            @if ($proyectos->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-slate-500 sm:px-6">Todavía no hay proyectos.</div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($proyectos as $proyecto)
                        <li class="px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $proyecto->nombre }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ $proyecto->empresa?->nombre ?? 'Sin empresa' }} · {{ $proyecto->cliente ?: 'Sin cliente' }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold capitalize text-slate-600">
                                    {{ \App\Models\Proyecto::ESTADOS[$proyecto->estado] ?? $proyecto->estado }}
                                </span>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-400">Creado {{ $proyecto->created_at->diffForHumans() }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
