@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id = \App\View\FormField::id($name);
    $error = $errors->first(\App\View\FormField::errorKey($name));
    $describedBy = $error ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-form.field :$id :$label :$error :$hint :$required {{ $attributes->only('class') }}>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old(\App\View\FormField::errorKey($name), $value) }}"
        @required($required)
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->class(['form-control', 'form-control-invalid' => $error]) }}>
</x-form.field>
