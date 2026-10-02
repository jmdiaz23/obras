@props(['open' => false, 'titulo' => '', 'maxWidth' => 'max-w-lg'])

<div {{ $attributes->merge(['class' => 'fixed inset-0 z-50 overflow-y-auto']) }} x-show="{{ $open ? 'true' : 'false' }}"
     x-cloak style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" x-on:click="$el.parentElement.style.display = 'none'"
         aria-hidden="true"></div>

    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div class="relative w-full {{ $maxWidth }} rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                <h3 class="text-base font-bold text-slate-900">{{ $titulo }}</h3>
                <button type="button" x-on:click="$el.closest('[x-show]').style.display = 'none'"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-5 py-5 sm:px-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
