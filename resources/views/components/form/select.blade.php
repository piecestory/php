{{-- $options: [value => label] --}}
@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id = \App\View\FormField::id($name);
    $error = $errors->first(\App\View\FormField::errorKey($name));
    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
    $selected = (string) old(\App\View\FormField::errorKey($name), $value);
@endphp

<x-form.field :$id :$label :$error :$hint :$required {{ $attributes->only('class') }}>
    <div class="relative">
        <select id="{{ $id }}" name="{{ $name }}"
            @required($required)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('class')->class(['form-control appearance-none pe-10', 'form-control-invalid' => $error]) }}>
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $optionValue === $selected)>{{ $optionLabel }}</option>
            @endforeach
        </select>
        <x-ui.icon name="chevron-down" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-soft" />
    </div>
</x-form.field>
