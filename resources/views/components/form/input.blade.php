@props(['label', 'name', 'type' => 'text', 'placeholder' => '', 'required' => false, 'step' => null, 'hint' => null])

@php
    $clases = 'campo'.($errors->has($name) ? ' campo-error' : '');
@endphp

<div>
    <label for="{{ $name }}" class="mb-2 block text-sm font-medium text-slate-700">
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
        <p class="mt-1.5 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
