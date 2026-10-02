@php
    $rutas = [
        'dashboard' => ['Dashboard', 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
    ];
    $catalogos = [
        'catalogos.proveedores' => 'Proveedores',
        'catalogos.materiales' => 'Materiales',
        'catalogos.equipos' => 'Equipos',
        'catalogos.mano-de-obra' => 'Mano de obra',
    ];
@endphp

<nav class="space-y-1">
    @foreach ($rutas as $ruta => [$texto, $icono])
        <a href="{{ route($ruta) }}" wire:navigate
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($ruta) ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
            <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono }}"/></svg>
            {{ $texto }}
        </a>
    @endforeach

    <div class="px-3 pb-1 pt-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Catálogos</p>
    </div>

    <div class="space-y-0.5">
        @foreach ($catalogos as $ruta => $texto)
            <a href="{{ route($ruta) }}" wire:navigate
               class="flex items-center gap-2 rounded-lg py-2 pl-6 pr-3 text-sm transition {{ request()->routeIs($ruta) ? 'bg-white/10 font-semibold text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ request()->routeIs($ruta) ? 'bg-amber-400' : 'bg-slate-600' }}"></span>
                {{ $texto }}
            </a>
        @endforeach
    </div>

    <div class="px-3 pb-1 pt-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Presupuesto</p>
    </div>

    <a href="{{ route('proyectos.index') }}" wire:navigate
       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('proyectos.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
        <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
        Proyectos
    </a>

    <a href="{{ route('apus.index') }}" wire:navigate
       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('apus.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
        <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z"/></svg>
        APUs
    </a>

    <div class="px-3 pb-1 pt-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Próximamente</p>
    </div>

    <span class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
        Cronograma
        <span class="ml-auto rounded-full bg-amber-400/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-400">Fase 6</span>
    </span>
</nav>
