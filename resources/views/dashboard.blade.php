<x-layouts.app title="Dashboard">
    {{-- Saludo --}}
    <div class="rounded-2xl bg-slate-900 p-6 text-white shadow-lg sm:p-8">
        <p class="text-sm text-slate-400">{{ now()->translatedFormat('l, d \d F \d\e Y') }}</p>
        <h2 class="mt-1 text-2xl font-bold sm:text-3xl">Hola, {{ auth()->user()->name }}</h2>
        <p class="mt-2 max-w-2xl text-sm text-slate-300">
            Estás administrando <span class="font-semibold text-amber-400">{{ auth()->user()->empresa->nombre }}</span>.
            La base del sistema está lista; en las próximas fases activaremos presupuestos, APUs y exportación a Excel.
        </p>
    </div>

    {{-- Configuración de la empresa --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        @php $config = [
            'Administración' => auth()->user()->empresa->pct_administracion,
            'Imprevistos' => auth()->user()->empresa->pct_imprevistos,
            'Utilidad' => auth()->user()->empresa->pct_utilidad,
            'Prestaciones' => auth()->user()->empresa->pct_prestaciones,
        ]; @endphp
        @foreach ($config as $label => $valor)
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($valor, 0) }}<span class="text-base text-slate-400">%</span></p>
            </div>
        @endforeach
        <div class="col-span-2 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:col-span-1 sm:p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Rol</p>
            <p class="mt-1 inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold capitalize text-amber-800">
                {{ auth()->user()->role }}
            </p>
        </div>
    </div>

    {{-- Progreso del producto --}}
    <div class="mt-6 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h3 class="font-semibold text-slate-900">Construcción del sistema</h3>
            <p class="text-sm text-slate-500">Fases del MVP</p>
        </div>
        <ul class="divide-y divide-slate-100 px-5 sm:px-6">
            @php
                $fases = [
                    ['Fase 0', 'Base + multientidad + autenticación', true],
                    ['Fase 1', 'Catálogos: materiales, equipos, mano de obra', true],
                    ['Fase 2', 'Constructor de APUs con costos en vivo', true],
                    ['Fase 3', 'Proyectos y memoria de cálculo', false],
                    ['Fase 4', 'Presupuesto general con AIU', false],
                    ['Fase 5', 'Exportación Excel réplica + PDF', false],
                ];
            @endphp
            @foreach ($fases as [$fase, $desc, $hecha])
                <li class="flex items-center gap-4 py-3.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $hecha ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}">
                        {{ $hecha ? '✓' : '·' }}
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $fase }}</p>
                        <p class="text-sm text-slate-500">{{ $desc }}</p>
                    </div>
                    @if ($hecha)
                        <span class="ml-auto hidden rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-emerald-700 sm:block">Completada</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts.app>
