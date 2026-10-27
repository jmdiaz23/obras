<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ObraOS' }} | ObraOS</title>
    @include('partials.brand-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <a href="/" class="mb-8" wire:navigate>
            <x-brand.logo tagline mark="h-14 w-14" text="text-2xl" />
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
