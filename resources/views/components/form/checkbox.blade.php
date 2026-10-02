@props(['name', 'label', 'value' => '1', 'checked' => false])

@php
    $id = \App\View\FormField::id($name);
    $error = $errors->first(\App\View\FormField::errorKey($name));
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3 text-sm text-ink">
        <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="{{ $value }}"
            @checked(old(\App\View\FormField::errorKey($name), $checked))
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('class')->class(['mt-0.5 size-4 shrink-0 rounded-xs border-line-strong accent-bronze']) }}>
        <span>{{ $label }}</span>
    </label>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-danger">{{ $error }}</p>
    @endif
</div>
