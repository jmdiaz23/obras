<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ObraOS' }} | ObraOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <a href="/" class="mb-8 flex flex-col items-center gap-2" wire:navigate>
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 shadow-lg shadow-slate-900/20">
                <span class="text-2xl font-black text-amber-400">O</span>
            </div>
            <span class="text-xl font-bold tracking-tight text-slate-900">Obra<span class="text-amber-500">OS</span></span>
        </a>

        <main class="w-full max-w-md">
            {{ $slot }}
        </main>

        <p class="mt-8 text-center text-xs text-slate-400">
            Sistema Operativo de la Empresa Constructora &middot; {{ date('Y') }}
        </p>
    </div>

    @livewireScripts
</body>
</html>
