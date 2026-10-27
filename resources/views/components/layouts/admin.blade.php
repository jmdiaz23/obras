<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Plataforma' }} | ObraOS</title>
    @include('partials.brand-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 antialiased">
<div x-data="{ sidebarOpen: false }" class="min-h-screen">

    {{-- Sidebar escritorio --}}
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-slate-950 lg:flex">
        <div class="flex h-16 items-center border-b border-white/10 px-5">
            <x-brand.logo dark tagline tagline-text="Plataforma" />
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Administración global</p>

            <a href="{{ route('admin.dashboard') }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                Panel
            </a>

            <a href="{{ route('admin.empresas') }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.empresas') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                Empresas
            </a>

            <a href="{{ route('admin.usuarios') }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.usuarios') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                Usuarios
            </a>
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="mb-3">
                <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-amber-400">Super administrador</p>
                <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- Overlay móvil --}}
    <div x-show="sidebarOpen" x-cloak x-on:click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"
         x-transition.opacity></div>

    {{-- Sidebar móvil --}}
    <aside x-show="sidebarOpen" x-cloak
           x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
           class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-950 lg:hidden">
        <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
            <x-brand.logo dark mark="h-8 w-8" text="text-base" />
            <button x-on:click="sidebarOpen = false" class="rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Administración global</p>
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/5 hover:text-white">Panel</a>
            <a href="{{ route('admin.empresas') }}" wire:navigate class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/5 hover:text-white">Empresas</a>
            <a href="{{ route('admin.usuarios') }}" wire:navigate class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/5 hover:text-white">Usuarios</a>
        </nav>

        <div class="border-t border-white/10 p-4">
            <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-amber-400">Super administrador</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white">Cerrar sesión</button>
            </form>
        </div>
    </aside>

    {{-- Contenido --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button x-on:click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 lg:hidden">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <h1 class="text-base font-semibold text-slate-900 sm:text-lg">{{ $title ?? 'Plataforma' }}</h1>
            <div class="ml-auto flex items-center gap-3">
                <span class="hidden rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-amber-300 ring-1 ring-slate-800 sm:inline-block">
                    Super administrador
                </span>
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
