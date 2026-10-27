@props([
    'dark' => false,
    'tagline' => false,
    'taglineText' => 'Sistema de obra',
    'mark' => 'h-9 w-9',
    'text' => 'text-lg',
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <x-brand.mark :class="$mark.' shrink-0'" />

    <span class="leading-none">
        <span class="block {{ $text }} font-black tracking-tight {{ $dark ? 'text-white' : 'text-slate-900' }}">
            Obra<span class="text-amber-500">OS</span>
        </span>

        @if ($tagline)
            <span class="mt-1.5 block text-[9px] font-bold uppercase tracking-[0.18em] {{ $dark ? 'text-slate-400' : 'text-slate-500' }}">
                {{ $taglineText }}
            </span>
        @endif
    </span>
</div>
