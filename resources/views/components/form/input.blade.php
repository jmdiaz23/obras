@props(['label', 'name', 'type' => 'text', 'placeholder' => '', 'required' => false, 'step' => null, 'hint' => null])

@php
    $claseBase = 'block w-full rounded-xl border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm';
    $clases = $errors->has($name) ? $claseBase.' border-red-400 bg-red-50' : $claseBase;
@endphp

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <input {{ $attributes->merge([
        'type' => $type,
        'id' => $name,
        'placeholder' => $placeholder,
        'class' => $clases,
    ]) }}>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
