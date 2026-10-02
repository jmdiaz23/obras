<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel' }} | ObraOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 antialiased">
<div x-data="{ sidebarOpen: false }" class="min-h-screen">

    {{-- Sidebar escritorio --}}
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-slate-900 lg:flex">
        <div class="flex h-16 items-center gap-3 border-b border-white/10 px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-400/15">
                <span class="text-lg font-black text-amber-400">O</span>
            </div>
            <span class="text-lg font-bold tracking-tight text-white">Obra<span class="text-amber-400">OS</span></span>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @include('partials.nav-menu')
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="mb-3 truncate">
                <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->empresa->nombre }}</p>
                <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
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
           class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-900 lg:hidden">
        <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
            <span class="text-lg font-bold tracking-tight text-white">Obra<span class="text-amber-400">OS</span></span>
            <button x-on:click="sidebarOpen = false" class="rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @include('partials.nav-menu')
        </nav>

        <div class="border-t border-white/10 p-4">
            <p class="mb-3 truncate text-sm font-semibold text-white">{{ auth()->user()->empresa->nombre }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- Contenido --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button x-on:click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 lg:hidden">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <h1 class="text-base font-semibold text-slate-900 sm:text-lg">{{ $title ?? 'Panel' }}</h1>
            <div class="ml-auto flex items-center gap-3">
                <span class="hidden rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 sm:inline-block">
                    {{ auth()->user()->role }}
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
