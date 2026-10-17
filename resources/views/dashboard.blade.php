<x-layouts.app title="Dashboard">
    {{-- Encabezado de la página --}}
    <div class="flex flex-col gap-1">
        <h2 class="text-lg font-bold text-slate-900">Dashboard</h2>
        <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, d \d F \d\e Y') }}</p>
    </div>

    {{-- Saludo --}}
    <div class="mt-5 flex flex-col gap-4 rounded-2xl bg-slate-900 p-6 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between sm:p-7">
        <div>
            <h3 class="text-xl font-bold sm:text-2xl">Hola, {{ auth()->user()->name }}</h3>
            <p class="mt-1.5 max-w-2xl text-sm text-slate-300">
                Estás administrando
                <span class="font-semibold text-amber-400">{{ auth()->user()->empresa->nombre }}</span>.
                Aquí tienes el resumen de tu operación.
            </p>
        </div>
        <span class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-sm font-semibold capitalize text-amber-300 ring-1 ring-white/15">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
            {{ auth()->user()->role }}
        </span>
    </div>

    {{-- KPIs de la empresa --}}
    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3">
        @php
            $iconos = [
                'Proyectos' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
                'APUs' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
                'Materiales' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
                'Equipos' => 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409m4.655 5.653L12.75 7.5',
                'Mano de obra' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                'Proveedores' => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h.375c.621 0 1.125-.504 1.125-1.125V11.25m0 0h-4.125c-.621 0-1.125-.504-1.125-1.125V6.75m0 0h4.125M6.75 11.25V6.375c0-.621.504-1.125 1.125-1.125h9.75c.621 0 1.125.504 1.125 1.125v4.875',
            ];
        @endphp
        @foreach ($kpis as $etiqueta => $valor)
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconos[$etiqueta] ?? '' }}"/>
                        </svg>
                    </span>
                    <p class="truncate text-sm font-medium text-slate-500">{{ $etiqueta }}</p>
                </div>
                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $valor }}</p>
            </div>
        @endforeach
    </div>

    {{-- Proyectos recientes + configuración --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        {{-- Proyectos recientes --}}
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <h3 class="font-semibold text-slate-900">Tus proyectos recientes</h3>
                    <p class="text-sm text-slate-500">Accesos rápidos a tus últimos presupuestos.</p>
                </div>
                @if ($proyectosRecientes->isNotEmpty())
                    <a href="{{ route('proyectos.index') }}" wire:navigate
                       class="text-sm font-semibold text-amber-600 transition hover:text-amber-700">Ver todos</a>
                @endif
            </div>

            @if ($proyectosRecientes->isEmpty())
                <div class="px-5 py-10 text-center sm:px-6">
                    <p class="text-sm text-slate-500">Aún no tienes proyectos creados.</p>
                    <a href="{{ route('proyectos.index') }}" wire:navigate
                       class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800">
                        Crear mi primer proyecto
                    </a>
                </div>
            @else
                @php
                    $colores = ['borrador' => 'bg-slate-100 text-slate-600', 'en_ejecucion' => 'bg-amber-50 text-amber-700', 'cerrado' => 'bg-emerald-50 text-emerald-700'];
                @endphp
                <ul class="divide-y divide-slate-100 px-5 sm:px-6">
                    @foreach ($proyectosRecientes as $proyecto)
                        <li class="flex flex-wrap items-center gap-x-3 gap-y-2 py-4">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('proyectos.editar', $proyecto) }}" wire:navigate
                                   class="block truncate text-sm font-semibold text-slate-900 transition hover:text-amber-600">
                                    {{ $proyecto->nombre }}
                                </a>
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ $proyecto->cliente ?: 'Sin cliente' }} · Creado {{ $proyecto->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $colores[$proyecto->estado] }}">
                                {{ $estados[$proyecto->estado] }}
                            </span>
                            <a href="{{ route('proyectos.editar', $proyecto) }}" wire:navigate
                               class="text-sm font-semibold text-amber-600 transition hover:text-amber-700">Abrir</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Configuración de la empresa --}}
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
            <h3 class="font-semibold text-slate-900">Configuración de la empresa</h3>
            <p class="mt-0.5 text-sm text-slate-500">Porcentajes del AIU</p>

            @php $config = [
                'Administración' => auth()->user()->empresa->pct_administracion,
                'Imprevistos' => auth()->user()->empresa->pct_imprevistos,
                'Utilidad' => auth()->user()->empresa->pct_utilidad,
                'Prestaciones' => auth()->user()->empresa->pct_prestaciones,
            ]; @endphp

            <dl class="mt-4 divide-y divide-slate-100">
                @foreach ($config as $label => $valor)
                    <div class="flex items-center justify-between py-3">
                        <dt class="text-sm text-slate-500">{{ $label }}</dt>
                        <dd class="text-lg font-bold text-slate-900">{{ number_format($valor, 0) }}<span class="text-sm text-slate-400">%</span></dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</x-layouts.app>
